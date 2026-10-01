<?php

namespace Database\Factories;

use App\Models\OnlineExamAnswer;
use App\Models\OnlineExamQuestion;
use App\Models\OnlineExamSubmission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OnlineExamAnswer>
 */
class OnlineExamAnswerFactory extends Factory
{
    protected $model = OnlineExamAnswer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'submission_id' => OnlineExamSubmission::factory(),
            'question_id' => OnlineExamQuestion::factory(),
            'answer_text' => null,
            'answer_file' => null,
            'selected_option' => 'A',
            'is_correct' => true,
            'score' => 10,
            'teacher_feedback' => null,
        ];
    }
}
