<?php

namespace Database\Factories;

use App\Models\Airport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Airport>
 */
class AirportFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->city() . ' International Airport',
            'code' => strtoupper(fake()->unique()->lexify('???')),
            'city' => fake()->city(),
            'country' => 'Indonesia',
        ];
    }
}
