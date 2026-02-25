<?php

namespace Database\Factories;

use App\Models\TransactionGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransactionGroup>
 */
class TransactionGroupFactory extends Factory
{
    protected $model = TransactionGroup::class;

    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'description' => fake()->optional()->sentence(),
        ];
    }
}
