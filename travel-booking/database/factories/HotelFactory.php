<?php

namespace Database\Factories;

use App\Models\Destination;
use App\Models\Hotel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Hotel>
 */
class HotelFactory extends Factory
{
    public function definition(): array
    {
        return [
            'destination_id' => Destination::factory(),
            'name' => fake()->company() . ' Grand Hotel',
            'description' => fake()->paragraph(),
            'address' => fake()->address(),
            'latitude' => fake()->latitude(-8.9, -6.0),
            'longitude' => fake()->longitude(106.0, 115.5),
            'star_rating' => fake()->numberBetween(1, 5),
            'contact_phone' => fake()->phoneNumber(),
            'status' => 'active',
        ];
    }
}
