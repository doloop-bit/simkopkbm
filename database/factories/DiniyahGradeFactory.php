<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DiniyahGrade>
 */
class DiniyahGradeFactory extends Factory
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
            'diniyah_subject_id' => \App\Models\DiniyahSubject::factory(),
            'classroom_id' => \App\Models\Classroom::factory(),
            'academic_year_id' => \App\Models\AcademicYear::factory(),
            'semester' => '1',
            'knowledge_grade' => $this->faker->numberBetween(60, 100),
            'practice_grade' => $this->faker->numberBetween(60, 100),
            'attitude_grade' => $this->faker->randomElement(['A', 'B', 'C', 'D']),
            'achievement' => $this->faker->sentence(),
            'target_status' => $this->faker->randomElement(['Tercapai', 'Belum Tercapai']),
            'grade' => $this->faker->numberBetween(60, 100),
        ];
    }
}
