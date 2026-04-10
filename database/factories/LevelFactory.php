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
        $educationLevel = $this->faker->randomElement(array_keys($levels));
        
        return [
            'name' => strtoupper($educationLevel),
            'type' => $educationLevel === 'paud' || $educationLevel === 'sd' ? 'class_teacher' : 'subject_teacher',
            'education_level' => $educationLevel,
            'phase_map' => $levels[$educationLevel],
        ];
    }
}
