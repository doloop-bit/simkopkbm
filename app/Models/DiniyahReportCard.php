<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiniyahReportCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'classroom_id',
        'academic_year_id',
        'semester',
        'scores',
        'teacher_notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'scores' => 'array',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function isFinal(): bool
    {
        return $this->status === 'final';
    }
}
