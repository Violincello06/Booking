<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherUsage;
use Carbon\Carbon;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class VoucherService
{
    /**
     * Admin: Paginate all vouchers.
     */
    public function paginate(int $perPage = 10): LengthAwarePaginator
    {
        return Voucher::withCount('usages')->latest()->paginate($perPage);
    }

    /**
     * Public: Get currently active, valid, and unexhausted vouchers.
     */
    public function getActiveVouchers(int $perPage = 10): LengthAwarePaginator
    {
        $now = Carbon::now();

        return Voucher::where('status', 'active')
            ->where('start_date', '<=', $now)
            ->where('end_date', '>=', $now)
            ->whereColumn('used_quota', '<', 'quota')
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Apply voucher to an active pending booking.
     */
    public function applyVoucher(User $user, string $voucherCode, string $bookingCode): array
    {
        return DB::transaction(function () use ($user, $voucherCode, $bookingCode) {
            $now = Carbon::now();

            // Lock Voucher row
            $voucher = Voucher::where('code', $voucherCode)->lockForUpdate()->firstOrFail();

            // Check Active Status
            if ($voucher->status !== 'active') {
                throw new DomainException('Voucher promo ini sedang tidak aktif.');
            }

            // Check Expiration
            if ($now->isBefore($voucher->start_date) || $now->isAfter($voucher->end_date)) {
                throw new DomainException('Voucher promo ini sudah kedaluwarsa atau belum dapat digunakan.');
            }

            // Check Quota
            if ($voucher->used_quota >= $voucher->quota) {
                throw new DomainException('Kuota penukaran voucher promo ini telah habis.');
            }

            // Lock Booking row
            $booking = Booking::where('user_id', $user->id)
                ->where('booking_code', $bookingCode)
                ->lockForUpdate()
                ->firstOrFail();

            if ($booking->status !== 'pending') {
                throw new DomainException("Voucher hanya dapat diterapkan pada pemesanan berstatus 'pending'.");
            }

            // Check if already claimed on this booking
            $alreadyUsed = VoucherUsage::where('voucher_id', $voucher->id)
                ->where('booking_id', $booking->id)
                ->exists();

            if ($alreadyUsed) {
                throw new DomainException('Voucher promo ini sudah pernah diterapkan pada pemesanan ini.');
            }

            // Check Minimum Transaction
            if ((float) $booking->total_amount < (float) $voucher->minimum_transaction) {
                $minFormatted = number_format($voucher->minimum_transaction, 0, ',', '.');
                throw new DomainException("Total transaksi pemesanan kurang dari batas minimum penggunaan voucher (Min: Rp {$minFormatted}).");
            }

            // Calculate Discount Amount
            $discount = $this->calculateDiscount($voucher, (float) $booking->total_amount);

            // Increment Voucher Usage Quota
            $voucher->increment('used_quota');

            // Record Usage
            $usage = VoucherUsage::create([
                'voucher_id' => $voucher->id,
                'user_id' => $user->id,
                'booking_id' => $booking->id,
                'discount_amount' => $discount,
            ]);

            // Deduct total_amount from Booking
            $originalTotal = (float) $booking->total_amount;
            $newTotal = max(0, $originalTotal - $discount);
            $booking->update(['total_amount' => $newTotal]);

            // If pending payment exists, update payment amount
            if ($booking->payment && $booking->payment->status === 'pending') {
                $booking->payment->update(['amount' => $newTotal]);
            }

            return [
                'booking' => $booking->fresh(['hotelBooking.room', 'flightBooking.flight', 'passengers']),
                'voucher' => $voucher->fresh(),
                'discount_amount' => $discount,
                'original_amount' => $originalTotal,
                'final_amount' => $newTotal,
                'usage_id' => $usage->id,
            ];
        });
    }

    /**
     * Calculate discount amount according to percentage / fixed rules.
     */
    public function calculateDiscount(Voucher $voucher, float $amount): float
    {
        if ($voucher->discount_type === 'percentage') {
            $discount = ($amount * (float) $voucher->discount_value) / 100;
            if ($voucher->maximum_discount !== null && (float) $voucher->maximum_discount > 0) {
                $discount = min($discount, (float) $voucher->maximum_discount);
            }
        } else {
            // Fixed discount
            $discount = min($amount, (float) $voucher->discount_value);
        }

        return round($discount, 2);
    }

    /**
     * Get voucher usage history for a user.
     */
    public function getUserVoucherUsages(User $user, int $perPage = 10): LengthAwarePaginator
    {
        return VoucherUsage::where('user_id', $user->id)
            ->with(['voucher', 'booking'])
            ->latest()
            ->paginate($perPage);
    }

    public function find(int $id): Voucher
    {
        return Voucher::withCount('usages')->findOrFail($id);
    }

    public function create(array $data): Voucher
    {
        return Voucher::create($data);
    }

    public function update(Voucher $voucher, array $data): Voucher
    {
        $voucher->update($data);
        return $voucher->fresh();
    }

    public function delete(Voucher $voucher): bool
    {
        return $voucher->delete();
    }
}
