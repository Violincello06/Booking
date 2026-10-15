<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HotelBooking extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'room_id',
        'check_in',
        'check_out',
        'guest_count',
        'room_count',
        'price',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'check_in' => 'date',
            'check_out' => 'date',
            'guest_count' => 'integer',
            'room_count' => 'integer',
            'price' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    /**
     * Parent booking.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Booked room.
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
