<?php

namespace App\Traits;

use App\Models\Classroom;

trait HasAssessmentLogic
{
    /**
     * Get classroom list filtered by user's role and assigned classrooms.
     */
    public function getFilteredClassrooms()
    {
        $user = auth()->user();

        return Classroom::query()
            ->when($this->academic_year_id, fn ($q) => $q->where('academic_year_id', $this->academic_year_id))
            ->when($user->isGuru(), fn ($q) => $q->whereIn('id', $user->getAssignedClassroomIds()))
            ->orderBy('name')
            ->get();
    }

    /**
     * Get subject list filtered by user's role and assigned subjects.
     */
    public function getFilteredSubjects(?int $classroomId = null)
    {
        $user = auth()->user();

        return \App\Models\Subject::query()
            ->when($classroomId, function ($query) use ($classroomId) {
                $classroom = Classroom::find($classroomId);
                if ($classroom) {
                    $phase = $classroom->getPhase();
                    $query->where(function ($q) use ($phase) {
                        $q->whereNull('phase');
                        if ($phase) {
                            $q->orWhere('phase', $phase);
                        }
                    });
                }
            })
            ->when($user->isGuru(), fn ($q) => $q->whereIn('id', $user->getAssignedSubjectIds()))
            ->orderBy('name')
            ->get();
    }

    /**
     * Determine if current user can save/edit assessments.
     * Roles like 'kepala_sekolah' or 'yayasan' would return false.
     */
    public function canEditAssessments(): bool
    {
        $user = auth()->user();

        if (in_array($user->role, ['kepala_sekolah', 'yayasan'])) {
            return false;
        }

        return true;
    }

    /**
     * Get the appropriate layout based on current route or user role.
     */
    public function getLayout(): string
    {
        // 1. Priority: URL Path detection (Most robust)
        if (request()->is('teacher*')) {
            return 'components.teacher.layouts.app';
        }

        if (request()->is('admin*')) {
            return 'components.admin.layouts.app';
        }

        // 2. Route Name detection
        if (request()->routeIs('teacher.*')) {
            return 'components.teacher.layouts.app';
        }

        if (request()->routeIs('admin.*')) {
            return 'components.admin.layouts.app';
        }

        // 3. Fallback: User Role-based
        $role = strtolower(trim(auth()->user()->role ?? ''));

        return ($role === 'guru' || $role === 'teacher')
            ? 'components.teacher.layouts.app'
            : 'components.admin.layouts.app';
    }
}
