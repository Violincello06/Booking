<?php

namespace App\Models;

use Database\Factories\HotelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hotel extends Model
{
    /** @use HasFactory<HotelFactory> */
    use HasFactory;

    protected $fillable = [
        'destination_id',
        'name',
        'description',
        'address',
        'latitude',
        'longitude',
        'star_rating',
        'contact_phone',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'star_rating' => 'integer',
        ];
    }

    /**
     * Destination where hotel is located.
     */
    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class);
    }

    /**
     * Rooms belonging to this hotel.
     */
    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    /**
     * Reviews given for this hotel.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}
