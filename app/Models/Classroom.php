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
        if ($this->class_level === null || ! $this->level) {
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

    /**
     * Copy classrooms (and optionally homeroom teachers & teacher assignments) from one academic year to another.
     *
     * @return int Number of newly created classrooms
     */
    public static function copyFromYear(
        int $sourceYearId,
        int $targetYearId,
        bool $copyHomeroom = true,
        bool $copyAssignments = false
    ): int {
        if ($sourceYearId === $targetYearId) {
            return 0;
        }

        $sourceClassrooms = self::with('teacherAssignments')
            ->where('academic_year_id', $sourceYearId)
            ->get();

        $copiedCount = 0;

        foreach ($sourceClassrooms as $source) {
            $newClassroom = self::firstOrCreate(
                [
                    'academic_year_id' => $targetYearId,
                    'level_id' => $source->level_id,
                    'name' => $source->name,
                ],
                [
                    'class_level' => $source->class_level,
                    'homeroom_teacher_id' => $copyHomeroom ? $source->homeroom_teacher_id : null,
                ]
            );

            if ($newClassroom->wasRecentlyCreated) {
                $copiedCount++;
            }

            if ($copyAssignments) {
                foreach ($source->teacherAssignments as $assignment) {
                    TeacherAssignment::firstOrCreate([
                        'academic_year_id' => $targetYearId,
                        'classroom_id' => $newClassroom->id,
                        'teacher_id' => $assignment->teacher_id,
                        'subject_id' => $assignment->subject_id,
                        'diniyah_subject_id' => $assignment->diniyah_subject_id,
                        'type' => $assignment->type,
                    ]);
                }
            }
        }

        return $copiedCount;
    }
}
