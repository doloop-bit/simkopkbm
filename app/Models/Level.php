<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Level extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'type', 'education_level', 'phase_map'];

    protected function casts(): array
    {
        return [
            'phase_map' => 'array',
        ];
    }

    public function classrooms()
    {
        return $this->hasMany(Classroom::class);
    }

    public function subjects()
    {
        return $this->hasMany(Subject::class);
    }

    public function isClassTeacherSystem(): bool
    {
        return $this->type === 'class_teacher';
    }

    /**
     * Get all unique phases available for this level.
     *
     * @return array<string>
     */
    public function getAvailablePhases(): array
    {
        if (!$this->phase_map) {
            return [];
        }

        $phases = array_unique(array_values($this->phase_map));
        sort($phases);

        return $phases;
    }
}
