<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\PaudReportCard;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PaudReportCardFactory extends Factory
{
    protected $model = PaudReportCard::class;

    public function definition(): array
    {
        return [
            'student_id' => User::factory(),
            'classroom_id' => Classroom::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'semester' => $this->faker->randomElement(['1', '2']),
            'cp_summaries' => [],
            'display_mode' => 'cp',
            'teacher_notes' => $this->faker->paragraph(),
            'parent_reflection' => $this->faker->paragraph(),
            'attendance' => [
                'sick' => $this->faker->numberBetween(0, 5),
                'permission' => $this->faker->numberBetween(0, 5),
                'absent' => $this->faker->numberBetween(0, 5),
            ],
            'physical_data' => [
                'weight' => $this->faker->numberBetween(10, 30),
                'height' => $this->faker->numberBetween(80, 120),
            ],
            'access_token' => Str::random(32),
            'status' => 'draft',
        ];
    }
}
