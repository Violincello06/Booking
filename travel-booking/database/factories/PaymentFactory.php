<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'payment_code' => 'PAY-' . strtoupper(Str::random(10)),
            'payment_method' => fake()->randomElement(['bank_transfer', 'credit_card', 'e_wallet', 'mock_gateway']),
            'amount' => fake()->randomFloat(2, 500000, 5000000),
            'paid_at' => now(),
            'status' => 'paid',
            'transaction_reference' => 'TX-' . strtoupper(Str::random(12)),
        ];
    }
}
