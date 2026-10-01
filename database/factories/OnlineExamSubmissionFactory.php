<?php

namespace Database\Factories;

use App\Models\OnlineExam;
use App\Models\OnlineExamSubmission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OnlineExamSubmission>
 */
class OnlineExamSubmissionFactory extends Factory
{
    protected $model = OnlineExamSubmission::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'online_exam_id' => OnlineExam::factory(),
            'student_id' => User::factory(),
            'started_at' => now()->subMinutes(30),
            'submitted_at' => now(),
            'status' => 'submitted',
            'total_score' => 85.00,
        ];
    }
}
