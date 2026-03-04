<?php

namespace Database\Factories;

use App\Models\Level;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Level>
 */
class LevelFactory extends Factory
{
    protected $model = Level::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['PAUD', 'TK', 'Paket A', 'Paket B', 'Paket C']),
            'type' => fake()->randomElement(['class_teacher', 'subject_teacher']),
            'education_level' => fake()->randomElement(['paud', 'sd', 'smp', 'sma']),
        ];
    }
}
