<?php

namespace Database\Factories;

use App\Models\Hotel;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Room>
 */
class RoomFactory extends Factory
{
    public function definition(): array
    {
        $types = ['Deluxe Room', 'Superior Room', 'Executive Suite', 'Standard Twin', 'Presidential Suite'];
        $facilitiesList = [
            ['Free WiFi', 'Air Conditioning', 'King Bed', 'TV Cable'],
            ['Free WiFi', 'Breakfast Included', 'Swimming Pool View', 'Bathtub'],
            ['Free WiFi', 'City View', 'Mini Bar', 'Coffee Maker'],
        ];

        return [
            'hotel_id' => Hotel::factory(),
            'room_type' => fake()->randomElement($types),
            'description' => fake()->sentence(),
            'capacity' => fake()->numberBetween(1, 4),
            'price_per_night' => fake()->randomFloat(2, 350000, 2500000),
            'total_rooms' => 10,
            'available_rooms' => 10,
            'facilities' => fake()->randomElement($facilitiesList),
            'image' => fake()->imageUrl(640, 480, 'room'),
            'status' => 'available',
        ];
    }
}
