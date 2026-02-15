<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BudgetPlan>
 */
class BudgetPlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'level_id' => \App\Models\Level::factory(),
            'academic_year_id' => \App\Models\AcademicYear::factory(),
            'title' => $this->faker->sentence,
            'total_amount' => $this->faker->numberBetween(100000, 10000000),
            'status' => 'draft',
            'submitted_by' => \App\Models\User::factory(),
        ];
    }
}
