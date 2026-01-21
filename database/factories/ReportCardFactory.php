<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\ReportCard;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ReportCard>
 */
class ReportCardFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => User::factory(),
            'classroom_id' => Classroom::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'scores' => [
                1 => ['subject_name' => 'Matematika', 'score' => $this->faker->numberBetween(60, 100)],
                2 => ['subject_name' => 'Bahasa Indonesia', 'score' => $this->faker->numberBetween(60, 100)],
                3 => ['subject_name' => 'Ilmu Pengetahuan Alam', 'score' => $this->faker->numberBetween(60, 100)],
            ],
            'gpa' => $this->faker->numberBetween(60, 100),
            'semester' => $this->faker->randomElement(['1', '2']),
            'teacher_notes' => $this->faker->sentence(),
            'principal_notes' => $this->faker->sentence(),
            'status' => 'draft',
        ];
    }
}
