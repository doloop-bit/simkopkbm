<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaudTp extends Model
{
    use HasFactory;

    protected $fillable = [
        'paud_cp_element_id',
        'paud_skl_item_id',
        'classroom_id',
        'academic_year_id',
        'semester',
        'code',
        'description',
        'order',
    ];

    public function cpElement(): BelongsTo
    {
        return $this->belongsTo(PaudCpElement::class, 'paud_cp_element_id');
    }

    public function sklItem(): BelongsTo
    {
        return $this->belongsTo(PaudSklItem::class, 'paud_skl_item_id');
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(PaudTpAssessment::class, 'paud_tp_id');
    }
}
