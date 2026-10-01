<?php

namespace Database\Factories;

use App\Models\OnlineExam;
use App\Models\OnlineExamQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OnlineExamQuestion>
 */
class OnlineExamQuestionFactory extends Factory
{
    protected $model = OnlineExamQuestion::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'online_exam_id' => OnlineExam::factory(),
            'question_type' => 'multiple_choice',
            'question_text' => fake()->sentence(6).'?',
            'options' => [
                'A' => fake()->word(),
                'B' => fake()->word(),
                'C' => fake()->word(),
                'D' => fake()->word(),
            ],
            'correct_answer' => 'A',
            'points' => 10,
            'order' => 1,
        ];
    }

    public function essay(): static
    {
        return $this->state(fn (array $attributes) => [
            'question_type' => 'essay',
            'options' => null,
            'correct_answer' => null,
            'points' => 20,
        ]);
    }
}
