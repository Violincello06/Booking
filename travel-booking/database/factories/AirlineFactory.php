<?php

namespace Database\Factories;

use App\Models\Airline;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Airline>
 */
class AirlineFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company() . ' Airways',
            'code' => strtoupper(fake()->unique()->lexify('??')),
            'logo' => fake()->imageUrl(200, 200, 'airline'),
            'status' => 'active',
        ];
    }
}
