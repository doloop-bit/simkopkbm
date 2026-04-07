<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Subject;
use App\Models\TeacherAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TeacherAssignment>
 */
class TeacherAssignmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'academic_year_id' => \App\Models\AcademicYear::where('is_active', true)->first() ?? \App\Models\AcademicYear::factory(),
            'classroom_id' => \App\Models\Classroom::inRandomOrder()->first() ?? Classroom::factory(),
            'teacher_id' => \App\Models\User::factory()->guru(),
            'subject_id' => \App\Models\Subject::inRandomOrder()->first() ?? Subject::factory(),
            'type' => 'subject_teacher',
        ];
    }
}
