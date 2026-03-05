<?php

namespace Database\Factories;

use App\Enums\PaymentType;
use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'transaction_type' => TransactionType::EXPENSE,
            'total_amount' => fake()->randomFloat(2, 10, 5000),
            'transaction_date' => fake()->date(),
            'payment_type' => PaymentType::SINGLE,
            'recurrency_type' => null,
            'transaction_category_id' => TransactionCategory::factory(),
        ];
    }

    public function expense(): static
    {
        return $this->state(fn (array $attributes) => [
            'transaction_type' => TransactionType::EXPENSE,
            'total_amount' => fake()->randomFloat(2, 10, 5000),
        ]);
    }

    public function revenue(): static
    {
        return $this->state(fn (array $attributes) => [
            'transaction_type' => TransactionType::REVENUE,
            'total_amount' => fake()->randomFloat(2, 10, 5000),
        ]);
    }
}
