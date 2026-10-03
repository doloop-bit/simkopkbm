<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OnlineExam extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject_id',
        'classroom_id',
        'academic_year_id',
        'semester',
        'title',
        'description',
        'exam_type',
        'duration_minutes',
        'start_time',
        'end_time',
        'is_published',
        'passing_grade',
        'shuffle_questions',
        'attachment_path',
        'attachment_name',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'shuffle_questions' => 'boolean',
            'start_time' => 'datetime',
            'end_time' => 'datetime',
        ];
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(OnlineExamQuestion::class)->orderBy('order');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(OnlineExamSubmission::class);
    }

    public function isActive(): bool
    {
        if (! $this->is_published) {
            return false;
        }

        $now = now();

        if ($this->start_time && $now->lt($this->start_time)) {
            return false;
        }

        if ($this->end_time && $now->gt($this->end_time)) {
            return false;
        }

        return true;
    }
}
