<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SalaryTemplate>
 */
class SalaryTemplateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'base_salary' => fake()->numberBetween(1_500_000, 5_000_000),
            'effective_date' => fake()->date(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
