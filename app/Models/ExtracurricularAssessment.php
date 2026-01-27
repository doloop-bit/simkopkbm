<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExtracurricularAssessment extends Model
{
    protected $fillable = [
        'student_id',
        'extracurricular_activity_id',
        'academic_year_id',
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
     * Get the extracurricular activity for this assessment.
     */
    public function extracurricularActivity(): BelongsTo
    {
        return $this->belongsTo(ExtracurricularActivity::class);
    }

    /**
     * Get the academic year for this assessment.
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
