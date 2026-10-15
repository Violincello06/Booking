<?php

namespace App\Models;

use Database\Factories\AirportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Airport extends Model
{
    /** @use HasFactory<AirportFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'city',
        'country',
    ];

    /**
     * Departure flights from this airport.
     */
    public function departureFlights(): HasMany
    {
        return $this->hasMany(Flight::class, 'departure_airport_id');
    }

    /**
     * Arrival flights to this airport.
     */
    public function arrivalFlights(): HasMany
    {
        return $this->hasMany(Flight::class, 'arrival_airport_id');
    }
}
