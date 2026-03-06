<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'amount' => fake()->randomFloat(2, 10, 5000),
            'paid_amount' => null,
            'status' => PaymentStatus::PENDING,
            'billing_date' => fake()->dateTimeBetween('-1 year', '+1 year')->format('Y-m-d'),
            'payment_date' => null,
            'payment_number' => 1,
            'billable_type' => null,
            'billable_id' => null,
            'transaction_id' => Transaction::factory(),
            'user_id' => User::factory(),
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::PAID,
            'paid_amount' => $attributes['amount'],
            'payment_date' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
        ]);
    }

    public function pending(): static
    {
        return $this->state([
            'status' => PaymentStatus::PENDING,
            'paid_amount' => null,
            'payment_date' => null,
        ]);
    }
}
