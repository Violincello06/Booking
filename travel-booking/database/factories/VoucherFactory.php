<?php

namespace Database\Factories;

use App\Models\Voucher;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Voucher>
 */
class VoucherFactory extends Factory
{
    public function definition(): array
    {
        $type = fake()->randomElement(['percentage', 'fixed']);
        $value = $type === 'percentage' ? fake()->numberBetween(5, 50) : fake()->numberBetween(50000, 200000);

        return [
            'code' => 'DISC' . strtoupper(Str::random(6)),
            'name' => fake()->words(3, true) . ' Promo',
            'discount_type' => $type,
            'discount_value' => $value,
            'minimum_transaction' => 200000,
            'maximum_discount' => $type === 'percentage' ? 500000 : null,
            'quota' => 100,
            'used_quota' => 0,
            'start_date' => now()->subDays(5),
            'end_date' => now()->addDays(30),
            'status' => 'active',
        ];
    }
}
