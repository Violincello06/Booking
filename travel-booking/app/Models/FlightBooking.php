<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlightBooking extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'flight_id',
        'passenger_count',
        'price',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'passenger_count' => 'integer',
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
     * Booked flight.
     */
    public function flight(): BelongsTo
    {
        return $this->belongsTo(Flight::class);
    }
}
