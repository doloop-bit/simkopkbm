<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\PaudCpElement;
use App\Models\PaudSklItem;
use App\Models\PaudTp;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaudTpFactory extends Factory
{
    protected $model = PaudTp::class;

    public function definition(): array
    {
        return [
            'paud_cp_element_id' => PaudCpElement::factory(),
            'paud_skl_item_id' => PaudSklItem::factory(),
            'classroom_id' => Classroom::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'semester' => $this->faker->randomElement(['1', '2']),
            'code' => 'TP-'.$this->faker->unique()->numberBetween(1, 100),
            'description' => $this->faker->sentence(10),
            'order' => $this->faker->randomDigit(),
        ];
    }
}
