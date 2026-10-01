<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\OnlineExam;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OnlineExam>
 */
class OnlineExamFactory extends Factory
{
    protected $model = OnlineExam::class;

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
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'exam_type' => fake()->randomElement(['daily', 'midterm', 'final']),
            'duration_minutes' => 60,
            'start_time' => now()->subDay(),
            'end_time' => now()->addDays(7),
            'is_published' => true,
            'passing_grade' => 75,
            'shuffle_questions' => false,
            'created_by' => User::factory(),
        ];
    }
}
