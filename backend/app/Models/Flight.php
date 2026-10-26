<?php

namespace App\Models;

use Database\Factories\FlightFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Flight extends Model
{
    /** @use HasFactory<FlightFactory> */
    use HasFactory;

    protected $fillable = [
        'airline_id',
        'departure_airport_id',
        'arrival_airport_id',
        'flight_number',
        'departure_time',
        'arrival_time',
        'duration',
        'price',
        'total_seats',
        'available_seats',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'departure_time' => 'datetime',
            'arrival_time' => 'datetime',
            'duration' => 'integer',
            'price' => 'decimal:2',
            'total_seats' => 'integer',
            'available_seats' => 'integer',
        ];
    }

    /**
     * Airline operating this flight.
     */
    public function airline(): BelongsTo
    {
        return $this->belongsTo(Airline::class);
    }

    /**
     * Departure airport.
     */
    public function departureAirport(): BelongsTo
    {
        return $this->belongsTo(Airport::class, 'departure_airport_id');
    }

    /**
     * Arrival airport.
     */
    public function arrivalAirport(): BelongsTo
    {
        return $this->belongsTo(Airport::class, 'arrival_airport_id');
    }

    /**
     * Flight bookings for this flight.
     */
    public function flightBookings(): HasMany
    {
        return $this->hasMany(FlightBooking::class);
    }
}
