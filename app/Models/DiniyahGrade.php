<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiniyahGrade extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'diniyah_subject_id',
        'classroom_id',
        'academic_year_id',
        'semester',
        'knowledge_grade',
        'practice_grade',
        'attitude_grade',
        'achievement',
        'grade',
        'notes',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(DiniyahSubject::class, 'diniyah_subject_id');
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
