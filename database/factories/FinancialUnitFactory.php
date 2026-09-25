<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FinancialUnit>
 */
class FinancialUnitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Kas '.fake()->words(2, true),
            'code' => strtoupper(fake()->unique()->lexify('UNIT_???')),
            'description' => fake()->sentence(),
        ];
    }
}
