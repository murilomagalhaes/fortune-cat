<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TransactionPayment>
 */
class TransactionPaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'amount' => fake()->randomFloat(2, 10, 5000),
            'status' => PaymentStatus::PENDING,
            'billing_date' => fake()->date(),
            'payment_number' => 1,
            'transaction_id' => Transaction::factory(),
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::PAID,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::PENDING,
        ]);
    }
}