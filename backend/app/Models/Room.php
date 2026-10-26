<?php

namespace App\Models;

use Database\Factories\RoomFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    /** @use HasFactory<RoomFactory> */
    use HasFactory;

    protected $fillable = [
        'hotel_id',
        'room_type',
        'description',
        'capacity',
        'price_per_night',
        'total_rooms',
        'available_rooms',
        'facilities',
        'image',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'price_per_night' => 'decimal:2',
            'total_rooms' => 'integer',
            'available_rooms' => 'integer',
            'facilities' => 'array',
        ];
    }

    /**
     * Hotel where this room belongs.
     */
    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    /**
     * Bookings made for this room.
     */
    public function hotelBookings(): HasMany
    {
        return $this->hasMany(HotelBooking::class);
    }
}
