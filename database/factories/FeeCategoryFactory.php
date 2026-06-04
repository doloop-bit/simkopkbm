<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FeeCategory>
 */
class FeeCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'code' => $this->faker->unique()->lexify('FEE-????'),
            'default_amount' => 100000,
            'billing_type' => 'monthly',
            'level_id' => \App\Models\Level::factory(),
        ];
    }
}
