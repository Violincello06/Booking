<?php

namespace Database\Factories;

use App\Models\Destination;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Destination>
 */
class DestinationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->city() . ' Tour Area',
            'country' => 'Indonesia',
            'province' => fake()->state(),
            'city' => fake()->city(),
            'description' => fake()->paragraph(),
            'image' => fake()->imageUrl(800, 600, 'travel'),
            'status' => 'active',
        ];
    }
}
