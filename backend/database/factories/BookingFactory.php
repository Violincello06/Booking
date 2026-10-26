<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    public function definition(): array
    {
        $type = fake()->randomElement(['hotel', 'flight']);
        $prefix = $type === 'hotel' ? 'HTL' : 'FLT';
        $code = sprintf('%s-%s-%s', $prefix, now()->format('Ymd'), strtoupper(Str::random(6)));

        return [
            'user_id' => User::factory(),
            'booking_code' => $code,
            'booking_type' => $type,
            'booking_date' => now(),
            'total_amount' => fake()->randomFloat(2, 500000, 5000000),
            'status' => 'confirmed',
            'expired_at' => now()->addHours(2),
        ];
    }
}
