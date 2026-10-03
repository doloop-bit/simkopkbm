<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OnlineExamQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'online_exam_id',
        'question_type',
        'question_text',
        'options',
        'correct_answer',
        'points',
        'order',
        'attachment_path',
        'attachment_name',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
        ];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(OnlineExam::class, 'online_exam_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(OnlineExamAnswer::class, 'question_id');
    }

    public function isMultipleChoice(): bool
    {
        return $this->question_type === 'multiple_choice';
    }

    public function isEssay(): bool
    {
        return $this->question_type === 'essay';
    }

    public function isFileUpload(): bool
    {
        return $this->question_type === 'file_upload';
    }
}
