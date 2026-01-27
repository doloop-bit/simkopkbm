<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class P5Project extends Model
{
    protected $fillable = [
        'name',
        'description',
        'academic_year_id',
        'semester',
        'dimension',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Get the academic year for this project.
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Get the assessments for this project.
     */
    public function assessments(): HasMany
    {
        return $this->hasMany(P5Assessment::class);
    }
}
