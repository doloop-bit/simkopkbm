<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportAttendance extends Model
{
    protected $fillable = [
        'student_id',
        'academic_year_id',
        'classroom_id',
        'semester',
        'sick',
        'permission',
        'absent',
    ];

    /**
     * Get the student for this attendance record.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Get the academic year for this attendance record.
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Get the classroom for this attendance record.
     */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }
}
