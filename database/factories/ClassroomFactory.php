<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Level;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Classroom>
 */
class ClassroomFactory extends Factory
{
    protected $model = Classroom::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Kelas '.fake()->randomLetter(),
            'academic_year_id' => AcademicYear::factory(),
            'level_id' => Level::factory(),
        ];
    }
}
