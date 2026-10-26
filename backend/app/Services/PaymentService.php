<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentService
{
    public function __construct(
        protected NotificationService $notificationService = new NotificationService()
    ) {}

    /**
     * Checkout & initialize payment for a booking.
     */
    public function checkout(User $user, array $data): Payment
    {
        return DB::transaction(function () use ($user, $data) {
            $booking = Booking::where('user_id', $user->id)
                ->where('booking_code', $data['booking_code'])
                ->lockForUpdate()
                ->firstOrFail();

            // Validate booking status
            if ($booking->status !== 'pending') {
                throw new DomainException("Hanya pemesanan berstatus 'pending' yang dapat dibayar (Status saat ini: {$booking->status}).");
            }

            // Validate expiration time
            if ($booking->expired_at && Carbon::now()->isAfter($booking->expired_at)) {
                $booking->update(['status' => 'expired']);
                throw new DomainException('Batas waktu pembayaran untuk pemesanan ini telah kedaluwarsa.');
            }

            // Find existing pending payment or initialize a new one
            $payment = Payment::firstOrNew([
                'booking_id' => $booking->id,
                'status' => 'pending',
            ]);

            if (! $payment->exists) {
                $payment->payment_code = $this->generatePaymentCode();
            }

            $payment->payment_method = $data['payment_method'];
            $payment->amount = $booking->total_amount;
            $payment->save();

            return $payment->load('booking');
        });
    }

    /**
     * Simulate payment execution (Mock Payment Gateway).
     */
    public function simulatePayment(User $user, string $paymentCode, string $action = 'success'): Payment
    {
        return DB::transaction(function () use ($user, $paymentCode, $action) {
            $payment = Payment::where('payment_code', $paymentCode)
                ->whereHas('booking', fn ($q) => $q->where('user_id', $user->id))
                ->lockForUpdate()
                ->firstOrFail();

            if ($payment->status === 'paid') {
                throw new DomainException('Pembayaran ini sudah berstatus berhasil (paid) sebelumnya.');
            }

            if ($action === 'success') {
                $payment->update([
                    'status' => 'paid',
                    'paid_at' => Carbon::now(),
                    'transaction_reference' => 'TRX-' . Carbon::now()->format('Ymd') . '-' . strtoupper(Str::random(8)),
                ]);

                // Transition Booking status to confirmed
                $payment->booking->update([
                    'status' => 'confirmed',
                ]);

                // Create notification
                $this->notificationService->send(
                    $user,
                    'Pembayaran Berhasil Dikonfirmasi',
                    "Pembayaran untuk pemesanan {$payment->booking->booking_code} sebesar Rp " . number_format((float) $payment->amount, 0, ',', '.') . ' telah berhasil diverifikasi.',
                    'payment'
                );
            } else {
                $payment->update([
                    'status' => 'failed',
                ]);
            }

            return $payment->fresh([
                'booking.hotelBooking.room.hotel',
                'booking.flightBooking.flight.airline',
                'booking.passengers',
            ]);
        });
    }

    /**
     * Find payment status by payment code.
     */
    public function findUserPayment(User $user, string $paymentCode): Payment
    {
        return Payment::where('payment_code', $paymentCode)
            ->whereHas('booking', fn ($q) => $q->where('user_id', $user->id))
            ->with('booking')
            ->firstOrFail();
    }

    /**
     * Get booking confirmation (E-Ticket / E-Voucher).
     */
    public function getBookingConfirmation(User $user, string $bookingCode): Booking
    {
        $booking = Booking::where('user_id', $user->id)
            ->where('booking_code', $bookingCode)
            ->with([
                'hotelBooking.room.hotel.destination',
                'flightBooking.flight.airline',
                'flightBooking.flight.departureAirport',
                'flightBooking.flight.arrivalAirport',
                'passengers',
                'payment',
            ])
            ->firstOrFail();

        if ($booking->status !== 'confirmed') {
            throw new DomainException("Konfirmasi pemesanan (E-Ticket / Voucher) hanya tersedia untuk pemesanan yang telah dikonfirmasi (Status saat ini: {$booking->status}).");
        }

        return $booking;
    }

    /**
     * Generate unique payment code e.g. PAY-20261015-ABC123
     */
    public function generatePaymentCode(): string
    {
        do {
            $date = Carbon::now()->format('Ymd');
            $random = strtoupper(Str::random(6));
            $code = "PAY-{$date}-{$random}";
        } while (Payment::where('payment_code', $code)->exists());

        return $code;
    }
}
