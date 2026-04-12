<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code', 'level_id', 'phase', 'type'];

    protected function casts(): array
    {
        return [
            'type' => 'string',
        ];
    }

    public function level()
    {
        return $this->belongsTo(Level::class);
    }

    public function assignments()
    {
        return $this->hasMany(TeacherAssignment::class);
    }

    public function scopeReguler($query)
    {
        return $query->where('type', 'reguler');
    }

    public function scopeDiniyah($query)
    {
        return $query->where('type', 'diniyah');
    }

    /**
     * Get all learning achievements (CP) for this subject.
     */
    public function learningAchievements()
    {
        return $this->hasMany(LearningAchievement::class);
    }

    /**
     * Get all TPs for this subject through learning achievements (CP).
     */
    public function tps()
    {
        return $this->hasManyThrough(SubjectTp::class, LearningAchievement::class);
    }

    /**
     * Get TPs for a specific phase.
     *
     * @param  string  $phase  The phase letter (A-F)
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function tpsForPhase(string $phase)
    {
        return SubjectTp::whereHas('learningAchievement', function ($q) use ($phase) {
            $q->where('subject_id', $this->id)->where('phase', $phase);
        })->orderBy('code')->get();
    }
}
