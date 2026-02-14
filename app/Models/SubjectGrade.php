<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubjectGrade extends Model
{
    protected $fillable = [
        'student_id',
        'subject_id',
        'classroom_id',
        'academic_year_id',
        'semester',
        'grade',
        'best_tp_id',
        'improvement_tp_id',
        'teacher_notes',
    ];

    protected function casts(): array
    {
        return [
            'grade' => 'decimal:2',
        ];
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function bestTp()
    {
        return $this->belongsTo(SubjectTp::class, 'best_tp_id');
    }

    public function improvementTp()
    {
        return $this->belongsTo(SubjectTp::class, 'improvement_tp_id');
    }
}
