<?php

namespace Database\Factories;

use App\Models\BankAccount;
use App\Models\CreditCard;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CreditCard>
 */
class CreditCardFactory extends Factory
{
    protected $model = CreditCard::class;

    public function definition(): array
    {
        return [
            'name' => fake()->creditCardType(),
            'total_limit' => fake()->randomFloat(2, 1000, 20000),
            'used_limit' => 0,
            'billing_cycle_end_date' => fake()->numberBetween(1, 28),
            'due_date' => fake()->numberBetween(1, 28),
            'bank_account_id' => BankAccount::factory(),
            'color' => fake()->hexColor(),
        ];
    }
}
