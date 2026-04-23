<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiniyahSubject extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'assessment_type',
        'kkm',
        'target',
        'has_practice',
        'level_id',
    ];

    protected function casts(): array
    {
        return [
            'has_practice' => 'boolean',
        ];
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    public function grades(): HasMany
    {
        return $this->hasMany(DiniyahGrade::class);
    }
}
