<?php

namespace Database\Factories;

use App\Models\StudentProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StudentProfile>
 */
class StudentProfileFactory extends Factory
{
    protected $model = StudentProfile::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nis' => fake()->unique()->numerify('######'),
            'nisn' => fake()->unique()->numerify('##########'),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'dob' => fake()->date(),
            'pob' => fake()->city(),
            'father_name' => fake()->name('male'),
            'mother_name' => fake()->name('female'),
            'status' => 'baru',
        ];
    }
}
