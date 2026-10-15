<?php

namespace Database\Factories;

use App\Models\Airline;
use App\Models\Airport;
use App\Models\Flight;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Flight>
 */
class FlightFactory extends Factory
{
    public function definition(): array
    {
        $depTime = fake()->dateTimeBetween('+1 days', '+30 days');
        $duration = fake()->numberBetween(60, 240); // 1 - 4 hours
        $arrTime = (clone $depTime)->modify("+{$duration} minutes");

        return [
            'airline_id' => Airline::factory(),
            'departure_airport_id' => Airport::factory(),
            'arrival_airport_id' => Airport::factory(),
            'flight_number' => strtoupper(fake()->bothify('??-###')),
            'departure_time' => $depTime,
            'arrival_time' => $arrTime,
            'duration' => $duration,
            'price' => fake()->randomFloat(2, 600000, 3500000),
            'total_seats' => 150,
            'available_seats' => 150,
            'status' => 'scheduled',
        ];
    }
}
