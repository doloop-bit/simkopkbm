<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DevelopmentalAssessment extends Model
{
    protected $fillable = [
        'student_id',
        'developmental_aspect_id',
        'academic_year_id',
        'classroom_id',
        'semester',
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
     * Get the developmental aspect for this assessment.
     */
    public function developmentalAspect(): BelongsTo
    {
        return $this->belongsTo(DevelopmentalAspect::class);
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
