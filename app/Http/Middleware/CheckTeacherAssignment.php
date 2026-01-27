<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTeacherAssignment
{
    /**
     * Handle an incoming request.
     *
     * Validates that teachers can only access data for their assigned classrooms/subjects.
     */
    public function handle(Request $request, Closure $next, string $type = 'any'): Response
    {
        $user = $request->user();

        // Only apply to teachers
        if ($user->role !== 'guru') {
            return $next($request);
        }

        // Get classroom_id and subject_id from request
        $classroomId = $request->input('classroom_id') ?? $request->route('classroom_id');
        $subjectId = $request->input('subject_id') ?? $request->route('subject_id');

        // Check based on type
        if ($type === 'classroom' && $classroomId) {
            if (!$user->hasAccessToClassroom($classroomId)) {
                abort(403, 'Anda tidak memiliki akses ke kelas ini.');
            }
        }

        if ($type === 'subject' && $subjectId) {
            if (!$user->hasAccessToSubject($subjectId)) {
                abort(403, 'Anda tidak memiliki akses ke mata pelajaran ini.');
            }
        }

        if ($type === 'any' && ($classroomId || $subjectId)) {
            $hasAccess = false;
            
            if ($classroomId && $user->hasAccessToClassroom($classroomId)) {
                $hasAccess = true;
            }
            
            if ($subjectId && $user->hasAccessToSubject($subjectId)) {
                $hasAccess = true;
            }

            if (!$hasAccess) {
                abort(403, 'Anda tidak memiliki akses ke data ini.');
            }
        }

        return $next($request);
    }
}
