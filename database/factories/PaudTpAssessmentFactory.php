<?php

namespace Database\Factories;

use App\Models\PaudTp;
use App\Models\PaudTpAssessment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaudTpAssessmentFactory extends Factory
{
    protected $model = PaudTpAssessment::class;

    public function definition(): array
    {
        return [
            'paud_tp_id' => PaudTp::factory(),
            'student_id' => User::factory(),
            'level' => $this->faker->randomElement(['BB', 'MB', 'BSH', 'BSB']),
            'notes' => $this->faker->paragraph(),
            'photos' => [],
            'assessed_by' => User::factory(),
        ];
    }
}
