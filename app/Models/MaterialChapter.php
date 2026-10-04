<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaterialChapter extends Model
{
    /** @use HasFactory<\Database\Factories\MaterialChapterFactory> */
    use HasFactory;

    protected $fillable = [
        'subject_id',
        'classroom_id',
        'academic_year_id',
        'semester',
        'title',
        'description',
        'order',
        'is_published',
        'published_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
            'order' => 'integer',
        ];
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function materials(): HasMany
    {
        return $this->hasMany(OnlineMaterial::class, 'chapter_id')->orderBy('order');
    }

    public function exams(): HasMany
    {
        return $this->hasMany(OnlineExam::class, 'chapter_id');
    }
}
