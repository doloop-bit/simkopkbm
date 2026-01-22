<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class P5Assessment extends Model
{
    protected $fillable = [
        'student_id',
        'p5_project_id',
        'academic_year_id',
        'classroom_id',
        'semester',
        'achievement_level',
        'description',
    ];

    /**
     * Get the student that owns the assessment.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Get the P5 project for this assessment.
     */
    public function p5Project(): BelongsTo
    {
        return $this->belongsTo(P5Project::class);
    }

    /**
     * Get the academic year for this assessment.
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Get the classroom for this assessment.
     */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }
}
