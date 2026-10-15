<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Flight;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookingService
{
    /**
     * Create a new hotel booking with concurrency lock and room decrement.
     */
    public function createHotelBooking(User $user, array $data): Booking
    {
        return DB::transaction(function () use ($user, $data) {
            // Pessimistic Locking to avoid race conditions/overbooking
            $room = Room::where('id', $data['room_id'])->lockForUpdate()->firstOrFail();

            $roomCount = (int) $data['room_count'];

            if ($room->status !== 'available' || $room->available_rooms < $roomCount) {
                throw new DomainException("Ketersediaan kamar {$room->room_type} tidak mencukupi (Tersisa: {$room->available_rooms} kamar).");
            }

            // Calculate duration in nights
            $checkIn = Carbon::parse($data['check_in']);
            $checkOut = Carbon::parse($data['check_out']);
            $nights = max(1, $checkIn->diffInDays($checkOut));
            $subtotal = (float) $room->price_per_night * $nights * $roomCount;

            // Decrement inventory
            $room->decrement('available_rooms', $roomCount);
            if ($room->fresh()->available_rooms <= 0) {
                $room->update(['status' => 'unavailable']);
            }

            // Create Master Booking
            $booking = Booking::create([
                'user_id' => $user->id,
                'booking_code' => $this->generateBookingCode('HTL'),
                'booking_type' => 'hotel',
                'booking_date' => now(),
                'total_amount' => $subtotal,
                'status' => 'pending',
                'expired_at' => now()->addHours(2),
            ]);

            // Create Hotel Booking Detail
            $booking->hotelBooking()->create([
                'room_id' => $room->id,
                'check_in' => $data['check_in'],
                'check_out' => $data['check_out'],
                'guest_count' => $data['guest_count'],
                'room_count' => $roomCount,
                'price' => $room->price_per_night,
                'subtotal' => $subtotal,
            ]);

            // Register Guests/Passengers
            foreach ($data['guests'] as $guest) {
                $booking->passengers()->create([
                    'full_name' => $guest['full_name'],
                    'identity_number' => $guest['identity_number'] ?? null,
                    'date_of_birth' => $guest['date_of_birth'] ?? null,
                    'gender' => $guest['gender'],
                    'nationality' => $guest['nationality'] ?? 'Indonesia',
                ]);
            }

            return $booking->load([
                'hotelBooking.room.hotel.destination',
                'passengers',
                'user',
            ]);
        });
    }

    /**
     * Create a new flight booking with concurrency lock and seat decrement.
     */
    public function createFlightBooking(User $user, array $data): Booking
    {
        return DB::transaction(function () use ($user, $data) {
            // Pessimistic Locking to prevent seat overbooking
            $flight = Flight::where('id', $data['flight_id'])->lockForUpdate()->firstOrFail();

            $passengerCount = (int) $data['passenger_count'];

            if ($flight->status !== 'scheduled' || $flight->available_seats < $passengerCount) {
                throw new DomainException("Kursi penerbangan {$flight->flight_number} tidak mencukupi (Tersisa: {$flight->available_seats} kursi).");
            }

            $subtotal = (float) $flight->price * $passengerCount;

            // Decrement seat inventory
            $flight->decrement('available_seats', $passengerCount);

            // Create Master Booking
            $booking = Booking::create([
                'user_id' => $user->id,
                'booking_code' => $this->generateBookingCode('FLT'),
                'booking_type' => 'flight',
                'booking_date' => now(),
                'total_amount' => $subtotal,
                'status' => 'pending',
                'expired_at' => now()->addHours(2),
            ]);

            // Create Flight Booking Detail
            $booking->flightBooking()->create([
                'flight_id' => $flight->id,
                'passenger_count' => $passengerCount,
                'price' => $flight->price,
                'subtotal' => $subtotal,
            ]);

            // Register Passengers
            foreach ($data['passengers'] as $passenger) {
                $booking->passengers()->create([
                    'full_name' => $passenger['full_name'],
                    'identity_number' => $passenger['identity_number'],
                    'date_of_birth' => $passenger['date_of_birth'] ?? null,
                    'gender' => $passenger['gender'],
                    'nationality' => $passenger['nationality'] ?? 'Indonesia',
                ]);
            }

            return $booking->load([
                'flightBooking.flight.airline',
                'flightBooking.flight.departureAirport',
                'flightBooking.flight.arrivalAirport',
                'passengers',
                'user',
            ]);
        });
    }

    /**
     * Cancel an active booking and restore inventory.
     */
    public function cancelBooking(User $user, string $codeOrId): Booking
    {
        return DB::transaction(function () use ($user, $codeOrId) {
            $booking = Booking::where('user_id', $user->id)
                ->where(function ($q) use ($codeOrId) {
                    $q->where('booking_code', $codeOrId)
                      ->orWhere('id', $codeOrId);
                })
                ->lockForUpdate()
                ->firstOrFail();

            if ($booking->status !== 'pending') {
                throw new DomainException("Hanya pemesanan dengan status 'pending' yang dapat dibatalkan (Status saat ini: {$booking->status}).");
            }

            // Restore Hotel inventory
            if ($booking->booking_type === 'hotel' && $booking->hotelBooking) {
                $hotelBooking = $booking->hotelBooking;
                $room = Room::where('id', $hotelBooking->room_id)->lockForUpdate()->first();
                if ($room) {
                    $room->increment('available_rooms', $hotelBooking->room_count);
                    if ($room->status === 'unavailable') {
                        $room->update(['status' => 'available']);
                    }
                }
            }

            // Restore Flight seat inventory
            if ($booking->booking_type === 'flight' && $booking->flightBooking) {
                $flightBooking = $booking->flightBooking;
                $flight = Flight::where('id', $flightBooking->flight_id)->lockForUpdate()->first();
                if ($flight) {
                    $flight->increment('available_seats', $flightBooking->passenger_count);
                }
            }

            $booking->update(['status' => 'cancelled']);

            return $booking->fresh([
                'hotelBooking.room.hotel',
                'flightBooking.flight',
                'passengers',
            ]);
        });
    }

    /**
     * Get paginated bookings for a specific user.
     */
    public function getUserBookings(User $user, array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Booking::where('user_id', $user->id)
            ->with([
                'hotelBooking.room.hotel.destination',
                'flightBooking.flight.airline',
                'flightBooking.flight.departureAirport',
                'flightBooking.flight.arrivalAirport',
                'passengers',
                'payment',
            ]);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['booking_type'])) {
            $query->where('booking_type', $filters['booking_type']);
        }

        return $query->latest('booking_date')->paginate($perPage);
    }

    /**
     * Find a user's booking by code or id.
     */
    public function findUserBooking(User $user, string $codeOrId): Booking
    {
        return Booking::where('user_id', $user->id)
            ->where(function ($q) use ($codeOrId) {
                $q->where('booking_code', $codeOrId)
                  ->orWhere('id', $codeOrId);
            })
            ->with([
                'hotelBooking.room.hotel.destination',
                'flightBooking.flight.airline',
                'flightBooking.flight.departureAirport',
                'flightBooking.flight.arrivalAirport',
                'passengers',
                'payment',
            ])
            ->firstOrFail();
    }

    /**
     * Generate unique booking code e.g. BK-HTL-20261015-9A8B
     */
    public function generateBookingCode(string $type = 'RES'): string
    {
        do {
            $date = Carbon::now()->format('Ymd');
            $random = strtoupper(Str::random(5));
            $code = "BK-{$type}-{$date}-{$random}";
        } while (Booking::where('booking_code', $code)->exists());

        return $code;
    }
}
