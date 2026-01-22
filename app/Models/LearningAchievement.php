<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningAchievement extends Model
{
    protected $fillable = [
        'subject_id',
        'phase',
        'description',
    ];

    /**
     * Get the subject that owns the learning achievement.
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
