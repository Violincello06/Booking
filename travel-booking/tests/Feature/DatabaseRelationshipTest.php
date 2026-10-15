<?php

namespace Tests\Feature;

use App\Models\Airline;
use App\Models\Airport;
use App\Models\Booking;
use App\Models\Destination;
use App\Models\Flight;
use App\Models\FlightBooking;
use App\Models\Hotel;
use App\Models\HotelBooking;
use App\Models\Passenger;
use App\Models\Payment;
use App\Models\Review;
use App\Models\Room;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherUsage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseRelationshipTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test Destination to Hotel relationship.
     */
    public function test_destination_has_many_hotels(): void
    {
        $destination = Destination::factory()->create();
        $hotel1 = Hotel::factory()->create(['destination_id' => $destination->id]);
        $hotel2 = Hotel::factory()->create(['destination_id' => $destination->id]);

        $this->assertCount(2, $destination->hotels);
        $this->assertTrue($hotel1->destination->is($destination));
    }

    /**
     * Test Hotel to Rooms and Reviews relationship.
     */
    public function test_hotel_has_rooms_and_reviews(): void
    {
        $hotel = Hotel::factory()->create();
        $room1 = Room::factory()->create(['hotel_id' => $hotel->id]);
        $room2 = Room::factory()->create(['hotel_id' => $hotel->id]);

        $user = User::factory()->create();
        $booking = Booking::factory()->create(['user_id' => $user->id]);
        $review = Review::factory()->create([
            'user_id' => $user->id,
            'hotel_id' => $hotel->id,
            'booking_id' => $booking->id,
        ]);

        $this->assertCount(2, $hotel->rooms);
        $this->assertCount(1, $hotel->reviews);
        $this->assertTrue($room1->hotel->is($hotel));
        $this->assertTrue($review->hotel->is($hotel));
    }

    /**
     * Test Flight relationships to Airline and Departure/Arrival Airports.
     */
    public function test_flight_relationships(): void
    {
        $airline = Airline::factory()->create();
        $depAirport = Airport::factory()->create(['code' => 'CGK']);
        $arrAirport = Airport::factory()->create(['code' => 'DPS']);

        $flight = Flight::factory()->create([
            'airline_id' => $airline->id,
            'departure_airport_id' => $depAirport->id,
            'arrival_airport_id' => $arrAirport->id,
        ]);

        $this->assertTrue($flight->airline->is($airline));
        $this->assertTrue($flight->departureAirport->is($depAirport));
        $this->assertTrue($flight->arrivalAirport->is($arrAirport));

        $this->assertTrue($depAirport->departureFlights->contains($flight));
        $this->assertTrue($arrAirport->arrivalFlights->contains($flight));
    }

    /**
     * Test Hotel Booking structure with Payment and Passenger.
     */
    public function test_hotel_booking_with_payment_and_passenger(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create();

        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'booking_type' => 'hotel',
        ]);

        $hotelBooking = HotelBooking::create([
            'booking_id' => $booking->id,
            'room_id' => $room->id,
            'check_in' => now()->addDays(2)->toDateString(),
            'check_out' => now()->addDays(4)->toDateString(),
            'guest_count' => 2,
            'room_count' => 1,
            'price' => $room->price_per_night,
            'subtotal' => $room->price_per_night * 2,
        ]);

        $passenger = Passenger::create([
            'booking_id' => $booking->id,
            'full_name' => 'Alice Smith',
            'identity_number' => '1234567890123456',
            'gender' => 'female',
            'nationality' => 'Indonesia',
        ]);

        $payment = Payment::factory()->create([
            'booking_id' => $booking->id,
            'amount' => $booking->total_amount,
        ]);

        $this->assertTrue($booking->user->is($user));
        $this->assertTrue($booking->hotelBooking->is($hotelBooking));
        $this->assertTrue($booking->payment->is($payment));
        $this->assertCount(1, $booking->passengers);
        $this->assertTrue($booking->passengers->first()->is($passenger));
    }

    /**
     * Test Flight Booking structure.
     */
    public function test_flight_booking_relationships(): void
    {
        $user = User::factory()->create();
        $flight = Flight::factory()->create();

        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'booking_type' => 'flight',
        ]);

        $flightBooking = FlightBooking::create([
            'booking_id' => $booking->id,
            'flight_id' => $flight->id,
            'passenger_count' => 1,
            'price' => $flight->price,
            'subtotal' => $flight->price,
        ]);

        $this->assertTrue($booking->flightBooking->is($flightBooking));
        $this->assertTrue($flightBooking->flight->is($flight));
    }

    /**
     * Test Voucher and VoucherUsage relationship.
     */
    public function test_voucher_and_usage_relationship(): void
    {
        $user = User::factory()->create();
        $booking = Booking::factory()->create(['user_id' => $user->id]);
        $voucher = Voucher::factory()->create(['code' => 'PROMO10']);

        $usage = VoucherUsage::create([
            'voucher_id' => $voucher->id,
            'user_id' => $user->id,
            'booking_id' => $booking->id,
            'discount_amount' => 50000,
        ]);

        $this->assertCount(1, $voucher->usages);
        $this->assertTrue($usage->voucher->is($voucher));
        $this->assertTrue($usage->user->is($user));
        $this->assertTrue($usage->booking->is($booking));
    }

    /**
     * Test Cascade Delete on foreign keys (e.g. deleting Hotel deletes its Rooms).
     */
    public function test_cascade_delete_hotel_deletes_rooms(): void
    {
        $hotel = Hotel::factory()->create();
        $room = Room::factory()->create(['hotel_id' => $hotel->id]);

        $this->assertDatabaseHas('rooms', ['id' => $room->id]);

        $hotel->delete();

        $this->assertDatabaseMissing('hotels', ['id' => $hotel->id]);
        $this->assertDatabaseMissing('rooms', ['id' => $room->id]);
    }
}
