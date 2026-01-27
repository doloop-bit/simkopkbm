<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExtracurricularActivity extends Model
{
    protected $fillable = [
        'name',
        'description',
        'instructor',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the assessments for this activity.
     */
    public function assessments(): HasMany
    {
        return $this->hasMany(ExtracurricularAssessment::class);
    }
}
