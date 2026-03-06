<?php

namespace Database\Factories;

use App\Enums\PaymentType;
use App\Enums\RecurrencyType;
use App\Enums\TransactionType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'transaction_type' => fake()->randomElement(TransactionType::cases()),
            'total_amount' => fake()->randomFloat(2, 10, 5000),
            'transaction_date' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'payment_type' => PaymentType::SINGLE,
            'recurrency_type' => null,
            'recurring_day' => null,
            'recurring_month' => null,
            'billable_type' => null,
            'billable_id' => null,
            'transaction_category_id' => null,
            'notes' => null,
            'user_id' => User::factory(),
        ];
    }

    public function recurrent(RecurrencyType $recurrencyType = RecurrencyType::MONTHLY): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_type' => PaymentType::RECURRENT,
            'recurrency_type' => $recurrencyType,
            'recurring_day' => fake()->numberBetween(1, 28),
            'recurring_month' => $recurrencyType === RecurrencyType::YEARLY
                ? fake()->numberBetween(1, 12)
                : null,
        ]);
    }

    public function installments(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_type' => PaymentType::INSTALLMENTS,
            'recurrency_type' => null,
        ]);
    }

    public function expense(): static
    {
        return $this->state(['transaction_type' => TransactionType::EXPENSE]);
    }

    public function revenue(): static
    {
        return $this->state(['transaction_type' => TransactionType::REVENUE]);
    }
}
