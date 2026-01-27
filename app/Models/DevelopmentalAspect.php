<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DevelopmentalAspect extends Model
{
    protected $fillable = [
        'aspect_type',
        'name',
        'description',
    ];

    /**
     * Get the assessments for this aspect.
     */
    public function assessments(): HasMany
    {
        return $this->hasMany(DevelopmentalAssessment::class);
    }
}
