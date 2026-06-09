<?php

namespace Database\Factories;

use App\Models\PaudCpElement;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaudCpElementFactory extends Factory
{
    protected $model = PaudCpElement::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'code' => $this->faker->unique()->slug,
            'description' => $this->faker->sentence(),
            'order' => $this->faker->randomDigit(),
        ];
    }
}
