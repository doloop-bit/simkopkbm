<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Classroom extends Model
{
    use HasFactory;

    protected $fillable = ['academic_year_id', 'level_id', 'name', 'class_level', 'homeroom_teacher_id'];

    protected function casts(): array
    {
        return [
            'class_level' => 'integer',
        ];
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function level()
    {
        return $this->belongsTo(Level::class);
    }

    public function homeroomTeacher()
    {
        return $this->belongsTo(User::class, 'homeroom_teacher_id');
    }

    public function teacherAssignments()
    {
        return $this->hasMany(TeacherAssignment::class);
    }

    public function students()
    {
        return $this->hasMany(StudentProfile::class, 'classroom_id');
    }

    /**
     * Get the Kurikulum Merdeka phase for this classroom.
     * Resolves based on the level's phase_map and the classroom's class_level.
     *
     * @return string|null The phase letter (A-F) or null if not determinable
     */
    public function getPhase(): ?string
    {
        if (! $this->class_level || ! $this->level) {
            return null;
        }

        $phaseMap = $this->level->phase_map;

        if (! $phaseMap) {
            return null;
        }

        return $phaseMap[(string) $this->class_level] ?? null;
    }

    /**
     * Get the phase label for display purposes.
     *
     * @return string e.g. "Fase C" or "-"
     */
    public function getPhaseLabelAttribute(): string
    {
        $phase = $this->getPhase();

        return $phase ? "Fase {$phase}" : '-';
    }
}
