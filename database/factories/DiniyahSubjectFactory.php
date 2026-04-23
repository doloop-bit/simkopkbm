<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DiniyahSubject>
 */
class DiniyahSubjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'code' => $this->faker->unique()->bothify('DIN###'),
            'assessment_type' => $this->faker->randomElement(['numeric', 'target_achievement']),
            'kkm' => $this->faker->numberBetween(65, 80),
            'target' => $this->faker->sentence(),
            'has_practice' => $this->faker->boolean(),
            'level_id' => \App\Models\Level::factory(),
        ];
    }
}
