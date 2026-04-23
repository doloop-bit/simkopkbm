<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StudentBilling>
 */
class StudentBillingFactory extends Factory
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
            'fee_category_id' => \App\Models\FeeCategory::factory(),
            'academic_year_id' => \App\Models\AcademicYear::factory(),
            'month' => now()->format('F'),
            'amount' => 100000,
            'paid_amount' => 0,
            'status' => 'unpaid',
        ];
    }
}
