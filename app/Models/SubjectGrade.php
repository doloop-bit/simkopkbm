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
        'best_tp_ids',
        'improvement_tp_ids',
        'teacher_notes',
    ];

    protected function casts(): array
    {
        return [
            'grade' => 'decimal:2',
            'best_tp_ids' => 'array',
            'improvement_tp_ids' => 'array',
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

    /**
     * Get distinct best TPs for display.
     * Note: This is not a relationship but a helper.
     */
    public function getBestTpsAttribute()
    {
        if (empty($this->best_tp_ids)) {
            return collect();
        }

        return SubjectTp::whereIn('id', $this->best_tp_ids)->get();
    }

    public function getImprovementTpsAttribute()
    {
        if (empty($this->improvement_tp_ids)) {
            return collect();
        }

        return SubjectTp::whereIn('id', $this->improvement_tp_ids)->get();
    }
}
