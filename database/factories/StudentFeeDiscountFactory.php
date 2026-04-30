<?php

namespace Database\Factories;

use App\Models\FeeCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StudentFeeDiscount>
 */
class StudentFeeDiscountFactory extends Factory
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
            'fee_category_id' => FeeCategory::factory(),
            'name' => $this->faker->word(),
            'discount_type' => $this->faker->randomElement(['fixed', 'percentage']),
            'amount' => $this->faker->numberBetween(1000, 50000),
            'frequency' => 'recurring',
            'is_applied' => false,
        ];
    }
}
