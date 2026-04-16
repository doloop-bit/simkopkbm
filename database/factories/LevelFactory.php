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
        $levels = Level::defaultPhases();
        $key = $this->faker->randomElement(array_keys($levels));

        $educationLevel = match ($key) {
            'Paket A' => 'sd',
            'Paket B' => 'smp',
            'Paket C' => 'sma',
            default => $key,
        };

        return [
            'name' => strtoupper($key),
            'type' => in_array($educationLevel, ['paud', 'sd']) ? 'class_teacher' : 'subject_teacher',
            'education_level' => $educationLevel,
            'phase_map' => $levels[$key],
        ];
    }
}
