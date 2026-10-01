<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\OnlineMaterial;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OnlineMaterial>
 */
class OnlineMaterialFactory extends Factory
{
    protected $model = OnlineMaterial::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subject_id' => Subject::factory(),
            'classroom_id' => Classroom::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'semester' => fake()->randomElement(['1', '2']),
            'title' => fake()->sentence(4),
            'content' => '<p>'.fake()->paragraph().'</p>',
            'attachments' => [],
            'is_published' => true,
            'published_at' => now(),
            'order' => fake()->numberBetween(1, 10),
            'created_by' => User::factory(),
        ];
    }
}
