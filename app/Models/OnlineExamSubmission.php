<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OnlineExamSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'online_exam_id',
        'student_id',
        'started_at',
        'submitted_at',
        'status',
        'total_score',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'total_score' => 'decimal:2',
        ];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(OnlineExam::class, 'online_exam_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(OnlineExamAnswer::class, 'submission_id');
    }

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    public function isSubmitted(): bool
    {
        return $this->status === 'submitted';
    }

    public function isGraded(): bool
    {
        return $this->status === 'graded';
    }

    /**
     * Auto-grade multiple choice answers and calculate total score.
     */
    public function autoGrade(): void
    {
        $totalScore = 0;
        $allGraded = true;

        foreach ($this->answers()->with('question')->get() as $answer) {
            if ($answer->question->isMultipleChoice()) {
                $isCorrect = $answer->selected_option === $answer->question->correct_answer;
                $score = $isCorrect ? $answer->question->points : 0;

                $answer->update([
                    'is_correct' => $isCorrect,
                    'score' => $score,
                ]);

                $totalScore += $score;
            } elseif ($answer->score !== null) {
                $totalScore += $answer->score;
            } else {
                $allGraded = false;
            }
        }

        $maxScore = $this->exam->questions()->sum('points');
        $percentageScore = $maxScore > 0 ? ($totalScore / $maxScore) * 100 : 0;

        $this->update([
            'total_score' => round($percentageScore, 2),
            'status' => $allGraded ? 'graded' : 'submitted',
        ]);
    }
}
