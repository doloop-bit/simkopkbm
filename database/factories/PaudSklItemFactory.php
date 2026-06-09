<?php

namespace Database\Factories;

use App\Models\PaudSklItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaudSklItemFactory extends Factory
{
    protected $model = PaudSklItem::class;

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
