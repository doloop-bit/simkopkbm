<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AcademicYear>
 */
class AcademicYearFactory extends Factory
{
    protected $model = AcademicYear::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startYear = fake()->year();
        $endYear = $startYear + 1;

        return [
            'name' => $startYear.'/'.$endYear,
            'start_date' => $startYear.'-07-01',
            'end_date' => $endYear.'-06-30',
            'is_active' => true,
            'status' => 'active',
        ];
    }
}
