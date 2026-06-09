<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaudTpAssessment extends Model
{
    use HasFactory;

    protected $fillable = [
        'paud_tp_id',
        'student_id',
        'level',
        'notes',
        'photos',
        'assessed_by',
    ];

    protected function casts(): array
    {
        return [
            'photos' => 'array',
        ];
    }

    public function tp(): BelongsTo
    {
        return $this->belongsTo(PaudTp::class, 'paud_tp_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function assessor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }
}
