<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaudReportCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'classroom_id',
        'academic_year_id',
        'semester',
        'cp_summaries',
        'display_mode',
        'teacher_notes',
        'parent_reflection',
        'attendance',
        'physical_data',
        'access_token',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'cp_summaries' => 'array',
            'attendance' => 'array',
            'physical_data' => 'array',
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
}
