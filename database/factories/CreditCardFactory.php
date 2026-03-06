<?php

namespace Database\Factories;

use App\Enums\ColorPalette;
use App\Helpers\ColorHelper;
use App\Models\BankAccount;
use App\Models\User;
use Filament\Support\Colors\Color;
use Illuminate\Database\Eloquent\Factories\Factory;

class CreditCardFactory extends Factory
{
    public function definition(): array
    {
        $colors = Color::all();

        $colorPalette = fake()->randomElement(array_keys($colors));
        $color = fake()->randomElement(array_values($colors[$colorPalette]));

        return [
            'name' => fake()->word(),
            'total_limit' => fake()->randomFloat(2, 1000, 20000),
            'used_limit' => fake()->randomFloat(2, 0, 1000),
            'billing_cycle_end_date' => fake()->numberBetween(1, 6),
            'due_date' => fake()->numberBetween(10, 17),
            'color_palette' => $colorPalette,
            'color' => $color,
            'bank_account_id' => null,
            'user_id' => User::factory(),
        ];
    }

    public function withBankAccount(): static
    {
        return $this->state(fn(array $attributes) => [
            'bank_account_id' => BankAccount::factory()->state([
                'user_id' => $attributes['user_id'],
            ]),
        ]);
    }
}
