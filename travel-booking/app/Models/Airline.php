<?php

namespace App\Models;

use Database\Factories\AirlineFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Airline extends Model
{
    /** @use HasFactory<AirlineFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'logo',
        'status',
    ];

    /**
     * Flights operated by this airline.
     */
    public function flights(): HasMany
    {
        return $this->hasMany(Flight::class);
    }
}
