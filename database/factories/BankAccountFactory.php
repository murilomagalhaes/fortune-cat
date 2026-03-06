<?php

namespace Database\Factories;

use App\Enums\BankAccountType;
use App\Enums\ColorPalette;
use App\Helpers\ColorHelper;
use App\Models\User;
use Filament\Support\Colors\Color;
use Illuminate\Database\Eloquent\Factories\Factory;

class BankAccountFactory extends Factory
{
    public function definition(): array
    {
        $colors = Color::all();

        $colorPalette = fake()->randomElement(array_keys($colors));
        $color = fake()->randomElement(array_values($colors[$colorPalette]));

        return [
            'name' => fake()->word(),
            'type' => fake()->randomElement(BankAccountType::cases()),
            'balance' => fake()->randomFloat(2, 0, 10000),
            'color_palette' => $colorPalette,
            'color' => $color,
            'user_id' => User::factory(),
        ];
    }
}
