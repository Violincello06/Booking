<?php

namespace App\Models;

use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'booking_code',
        'booking_type',
        'booking_date',
        'total_amount',
        'status',
        'expired_at',
    ];

    protected function casts(): array
    {
        return [
            'booking_date' => 'datetime',
            'expired_at' => 'datetime',
            'total_amount' => 'decimal:2',
        ];
    }

    /**
     * User who made the booking.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Associated hotel booking detail (if hotel booking).
     */
    public function hotelBooking(): HasOne
    {
        return $this->hasOne(HotelBooking::class);
    }

    /**
     * Associated flight booking detail (if flight booking).
     */
    public function flightBooking(): HasOne
    {
        return $this->hasOne(FlightBooking::class);
    }

    /**
     * Passengers registered for this booking.
     */
    public function passengers(): HasMany
    {
        return $this->hasMany(Passenger::class);
    }

    /**
     * Payment record for this booking.
     */
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    /**
     * Voucher usages associated with this booking.
     */
    public function voucherUsages(): HasMany
    {
        return $this->hasMany(VoucherUsage::class);
    }

    /**
     * Review left for this booking.
     */
    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }
}
