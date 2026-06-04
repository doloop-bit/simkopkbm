<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CalendarEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'type',
        'scope',
        'level_id',
        'academic_year_id',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'location',
        'color',
        'is_all_day',
        'recurrence_type',
        'recurrence_config',
        'recurrence_end_date',
        'parent_event_id',
        'attachment',
        'created_by',
    ];

    /**
     * Default colors per event type.
     *
     * @var array<string, string>
     */
    public const TYPE_COLORS = [
        'rapat_jenjang' => '#3B82F6',
        'rapat_gabungan' => '#8B5CF6',
        'rapat_yayasan' => '#F59E0B',
        'ujian_dinas' => '#EF4444',
        'ujian_sekolah' => '#F97316',
        'kegiatan' => '#10B981',
        'lainnya' => '#6B7280',
    ];

    /**
     * Human-readable labels for event types.
     *
     * @var array<string, string>
     */
    public const TYPE_LABELS = [
        'rapat_jenjang' => 'Rapat Jenjang',
        'rapat_gabungan' => 'Rapat Gabungan',
        'rapat_yayasan' => 'Rapat Yayasan',
        'ujian_dinas' => 'Ujian Dinas',
        'ujian_sekolah' => 'Ujian Sekolah',
        'kegiatan' => 'Kegiatan',
        'lainnya' => 'Lainnya',
    ];

    /**
     * Human-readable labels for scopes.
     *
     * @var array<string, string>
     */
    public const SCOPE_LABELS = [
        'level' => 'Jenjang',
        'pkbm' => 'PKBM',
        'yayasan' => 'Yayasan',
    ];

    /**
     * Mapping of auto-scope types.
     *
     * @var array<string, string>
     */
    public const AUTO_SCOPE_TYPES = [
        'rapat_jenjang' => 'level',
        'rapat_gabungan' => 'pkbm',
        'rapat_yayasan' => 'yayasan',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'recurrence_end_date' => 'date',
            'recurrence_config' => 'array',
            'is_all_day' => 'boolean',
        ];
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function parentEvent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_event_id');
    }

    public function childEvents(): HasMany
    {
        return $this->hasMany(self::class, 'parent_event_id');
    }

    /**
     * Get the display color, falling back to the type default.
     */
    public function getDisplayColorAttribute(): string
    {
        return $this->color ?? (self::TYPE_COLORS[$this->type] ?? '#6B7280');
    }

    /**
     * Get the human-readable type label.
     */
    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->type] ?? $this->type;
    }

    /**
     * Get the human-readable scope label.
     */
    public function getScopeLabelAttribute(): string
    {
        return self::SCOPE_LABELS[$this->scope] ?? $this->scope;
    }

    /**
     * Check if this event is a recurring parent.
     */
    public function isRecurring(): bool
    {
        return $this->recurrence_type !== 'none' && $this->parent_event_id === null;
    }

    /**
     * Check if this event is a child of a recurring event.
     */
    public function isRecurrenceChild(): bool
    {
        return $this->parent_event_id !== null;
    }

    /**
     * Scope: events visible to a specific level (includes pkbm and yayasan scoped events).
     */
    public function scopeForLevel(Builder $query, int $levelId): Builder
    {
        return $query->where(function (Builder $q) use ($levelId) {
            $q->where(function (Builder $inner) use ($levelId) {
                $inner->where('scope', 'level')->where('level_id', $levelId);
            })
                ->orWhereIn('scope', ['pkbm', 'yayasan']);
        });
    }

    /**
     * Scope: events for a specific academic year.
     */
    public function scopeForAcademicYear(Builder $query, int $yearId): Builder
    {
        return $query->where('academic_year_id', $yearId);
    }

    /**
     * Scope: events by type.
     */
    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: events within a date range (for calendar month/week views).
     */
    public function scopeInDateRange(Builder $query, string $start, string $end): Builder
    {
        return $query->where(function (Builder $q) use ($start, $end) {
            $q->whereBetween('start_date', [$start, $end])
                ->orWhere(function (Builder $inner) use ($start, $end) {
                    $inner->whereNotNull('end_date')
                        ->where('start_date', '<=', $end)
                        ->where('end_date', '>=', $start);
                });
        });
    }

    /**
     * Scope: only parent/standalone events (exclude recurrence children).
     */
    public function scopeParentsOnly(Builder $query): Builder
    {
        return $query->whereNull('parent_event_id');
    }
}
