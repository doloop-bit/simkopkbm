<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Subject>
 */
class SubjectFactory extends Factory
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
            'code' => $this->faker->unique()->lexify('SUBJ-????'),
            'level_id' => \App\Models\Level::factory(),
            'phase' => $this->faker->randomElement(['A', 'B', 'C', 'D', 'E', 'F']),
        ];
    }
}
