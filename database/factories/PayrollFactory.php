<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payroll>
 */
class PayrollFactory extends Factory
{
    public function definition(): array
    {
        $baseSalary = fake()->numberBetween(1_500_000, 5_000_000);
        $totalAllowances = fake()->numberBetween(200_000, 1_000_000);
        $totalDeductions = fake()->numberBetween(50_000, 300_000);

        return [
            'user_id' => User::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'month' => now()->format('Y-m'),
            'base_salary' => $baseSalary,
            'components' => [],
            'total_allowances' => $totalAllowances,
            'total_deductions' => $totalDeductions,
            'net_salary' => $baseSalary + $totalAllowances - $totalDeductions,
            'status' => 'draft',
            'notes' => null,
        ];
    }

    public function finalized(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'finalized',
        ]);
    }
}
