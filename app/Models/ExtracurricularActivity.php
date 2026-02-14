<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExtracurricularActivity extends Model
{
    protected $fillable = [
        'level_id',
        'name',
        'description',
        'instructor',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the level that owns the activity.
     */
    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    /**
     * Get the assessments for this activity.
     */
    public function assessments(): HasMany
    {
        return $this->hasMany(ExtracurricularAssessment::class, 'extracurricular_activity_id');
    }
}
