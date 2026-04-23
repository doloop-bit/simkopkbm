<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Score>
 */
class ScoreFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => \App\Models\User::factory(),
            'subject_id' => \App\Models\Subject::factory(),
            'classroom_id' => \App\Models\Classroom::factory(),
            'academic_year_id' => \App\Models\AcademicYear::factory(),
            'score_category_id' => \App\Models\ScoreCategory::factory(),
            'score' => $this->faker->randomFloat(2, 0, 100),
        ];
    }
}
