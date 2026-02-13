<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LearningAchievement extends Model
{
    protected $fillable = [
        'subject_id',
        'phase',
        'description',
    ];

    /**
     * Get the subject that owns the learning achievement (CP).
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Get the TPs (Tujuan Pembelajaran) under this CP.
     */
    public function tps(): HasMany
    {
        return $this->hasMany(SubjectTp::class);
    }

    /**
     * Get the phase label for display.
     */
    public function getPhaseLabelAttribute(): string
    {
        return "Fase {$this->phase}";
    }
}
