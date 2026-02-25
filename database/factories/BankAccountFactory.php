<?php

namespace Database\Factories;

use App\Enums\BankAccountType;
use App\Models\BankAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BankAccount>
 */
class BankAccountFactory extends Factory
{
    protected $model = BankAccount::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'type' => fake()->randomElement(BankAccountType::cases()),
            'balance' => fake()->randomFloat(2, 100, 50000),
            'color' => fake()->hexColor(),
        ];
    }
}
