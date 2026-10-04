<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\MaterialChapter;
use App\Models\MaterialProgress;
use App\Models\OnlineExam;
use App\Models\OnlineExamSubmission;
use App\Models\OnlineMaterial;
use App\Models\Subject;
use App\Models\User;

class ElearningCourseOutlineService
{
    /**
     * Get active academic year and active semester.
     */
    public function getActiveAcademicYearAndSemester(): array
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        if (! $activeYear) {
            return [null, 'Ganjil'];
        }

        return [$activeYear, $activeYear->active_semester ?? 'Ganjil'];
    }

    /**
     * Get list of subjects for a student with progress summary for active semester.
     */
    public function getStudentSubjectsSummary(User $student, ?string $semester = null): array
    {
        $classroomId = $student->latestProfile?->profileable?->classroom_id;
        if (! $classroomId) {
            return [];
        }

        [$activeYear, $defaultSemester] = $this->getActiveAcademicYearAndSemester();
        if (! $activeYear) {
            return [];
        }

        $activeSemester = $semester ?? $defaultSemester;

        // Get subjects that have chapters/materials/exams for student's classroom
        $subjectIds = MaterialChapter::where('classroom_id', $classroomId)
            ->where('academic_year_id', $activeYear->id)
            ->where('semester', $activeSemester)
            ->where('is_published', true)
            ->pluck('subject_id')
            ->concat(
                OnlineMaterial::where('classroom_id', $classroomId)
                    ->where('academic_year_id', $activeYear->id)
                    ->where('semester', $activeSemester)
                    ->where('is_published', true)
                    ->pluck('subject_id')
            )
            ->concat(
                OnlineExam::where('classroom_id', $classroomId)
                    ->where('academic_year_id', $activeYear->id)
                    ->where('semester', $activeSemester)
                    ->where('is_published', true)
                    ->pluck('subject_id')
            )
            ->unique()
            ->filter();

        $subjects = Subject::whereIn('id', $subjectIds)->get();

        $summary = [];
        foreach ($subjects as $subject) {
            $outline = $this->getSubjectOutlineForStudent($student, $subject->id, $classroomId, $activeYear->id, $activeSemester);
            $summary[] = [
                'subject' => $subject,
                'total_items' => $outline['total_items'],
                'completed_items' => $outline['completed_items'],
                'progress_percentage' => $outline['total_items'] > 0 ? round(($outline['completed_items'] / $outline['total_items']) * 100) : 0,
                'next_unlocked_item' => $outline['next_unlocked_item'],
            ];
        }

        return $summary;
    }

    /**
     * Build full course outline for a specific subject with progression lock status.
     */
    public function getSubjectOutlineForStudent(User $student, int $subjectId, int $classroomId, int $academicYearId, string $semester): array
    {
        // 1. Get completed material IDs for this student
        $completedMaterialIds = MaterialProgress::where('user_id', $student->id)
            ->pluck('material_id')
            ->toArray();

        // 2. Get submitted exam IDs for this student
        $completedExamIds = OnlineExamSubmission::where('student_id', $student->id)
            ->whereIn('status', ['submitted', 'graded'])
            ->pluck('online_exam_id')
            ->toArray();

        // 3. Fetch Handbooks (Materials without chapter_id, type = handbook or any unassigned)
        $handbooks = OnlineMaterial::where('subject_id', $subjectId)
            ->where('classroom_id', $classroomId)
            ->where('academic_year_id', $academicYearId)
            ->where('semester', $semester)
            ->where('is_published', true)
            ->whereNull('chapter_id')
            ->orderBy('order')
            ->get()
            ->map(function ($material) use ($completedMaterialIds) {
                return [
                    'id' => $material->id,
                    'title' => $material->title,
                    'type' => $material->type ?? 'handbook',
                    'is_completed' => in_array($material->id, $completedMaterialIds),
                    'is_locked' => false, // Handbooks are always unlocked
                    'model' => $material,
                ];
            });

        // 4. Fetch Chapters with their Materials & Quizzes
        $chaptersRaw = MaterialChapter::where('subject_id', $subjectId)
            ->where('classroom_id', $classroomId)
            ->where('academic_year_id', $academicYearId)
            ->where('semester', $semester)
            ->where('is_published', true)
            ->with([
                'materials' => fn ($q) => $q->where('is_published', true)->orderBy('order'),
                'exams' => fn ($q) => $q->where('is_published', true)->where('exam_type', 'quiz'),
            ])
            ->orderBy('order')
            ->get();

        $chapters = [];
        $previousChapterCompleted = true; // First chapter is always unlocked
        $nextUnlockedItem = null;
        $totalItems = count($handbooks);
        $completedItems = $handbooks->where('is_completed', true)->count();

        foreach ($chaptersRaw as $chapter) {
            $chapterMaterials = [];
            $allMaterialsCompleted = true;

            foreach ($chapter->materials as $material) {
                $isCompleted = in_array($material->id, $completedMaterialIds);
                $isLocked = ! $previousChapterCompleted;

                $totalItems++;
                if ($isCompleted) {
                    $completedItems++;
                } elseif (! $isLocked && ! $nextUnlockedItem) {
                    $nextUnlockedItem = ['type' => 'material', 'id' => $material->id];
                }

                if (! $isCompleted) {
                    $allMaterialsCompleted = false;
                }

                $chapterMaterials[] = [
                    'id' => $material->id,
                    'title' => $material->title,
                    'type' => $material->type ?? 'text',
                    'is_completed' => $isCompleted,
                    'is_locked' => $isLocked,
                    'model' => $material,
                ];
            }

            // Quizzes for this chapter
            $chapterQuizzes = [];
            $allQuizzesAttempted = true;

            foreach ($chapter->exams as $exam) {
                $isCompleted = in_array($exam->id, $completedExamIds);
                // Quiz is locked if chapter is locked OR materials in this chapter not all completed
                $isLocked = ! $previousChapterCompleted || ! $allMaterialsCompleted;

                $totalItems++;
                if ($isCompleted) {
                    $completedItems++;
                } elseif (! $isLocked && ! $nextUnlockedItem) {
                    $nextUnlockedItem = ['type' => 'exam', 'id' => $exam->id];
                }

                if (! $isCompleted) {
                    $allQuizzesAttempted = false;
                }

                $chapterQuizzes[] = [
                    'id' => $exam->id,
                    'title' => $exam->title,
                    'type' => 'quiz',
                    'is_completed' => $isCompleted,
                    'is_locked' => $isLocked,
                    'model' => $exam,
                ];
            }

            // Current chapter is completed if all materials and quizzes are completed/attempted
            $chapterCompleted = $allMaterialsCompleted && $allQuizzesAttempted;

            $chapters[] = [
                'id' => $chapter->id,
                'title' => $chapter->title,
                'description' => $chapter->description,
                'order' => $chapter->order,
                'is_locked' => ! $previousChapterCompleted,
                'is_completed' => $chapterCompleted,
                'materials' => $chapterMaterials,
                'quizzes' => $chapterQuizzes,
            ];

            // Next chapter unlocked only if current chapter completed
            $previousChapterCompleted = $chapterCompleted;
        }

        // 5. Midterm and Final Exams (UTS & UAS)
        $termExams = OnlineExam::where('subject_id', $subjectId)
            ->where('classroom_id', $classroomId)
            ->where('academic_year_id', $academicYearId)
            ->where('semester', $semester)
            ->where('is_published', true)
            ->whereIn('exam_type', ['midterm', 'final'])
            ->get()
            ->map(function ($exam) use ($completedExamIds, $previousChapterCompleted, &$totalItems, &$completedItems, &$nextUnlockedItem) {
                $isCompleted = in_array($exam->id, $completedExamIds);
                // UTS/UAS unlocked if all chapters are completed
                $isLocked = ! $previousChapterCompleted;

                $totalItems++;
                if ($isCompleted) {
                    $completedItems++;
                } elseif (! $isLocked && ! $nextUnlockedItem) {
                    $nextUnlockedItem = ['type' => 'exam', 'id' => $exam->id];
                }

                return [
                    'id' => $exam->id,
                    'title' => $exam->title,
                    'exam_type' => $exam->exam_type,
                    'is_completed' => $isCompleted,
                    'is_locked' => $isLocked,
                    'model' => $exam,
                ];
            });

        return [
            'handbooks' => $handbooks,
            'chapters' => $chapters,
            'term_exams' => $termExams,
            'total_items' => $totalItems,
            'completed_items' => $completedItems,
            'next_unlocked_item' => $nextUnlockedItem,
        ];
    }

    /**
     * Check if a specific material is accessible for a student (not locked).
     */
    public function isMaterialAccessible(User $student, OnlineMaterial $material): bool
    {
        // Handbooks without chapter are always accessible
        if (! $material->chapter_id) {
            return true;
        }

        $outline = $this->getSubjectOutlineForStudent(
            $student,
            $material->subject_id,
            $material->classroom_id,
            $material->academic_year_id,
            $material->semester
        );

        foreach ($outline['chapters'] as $ch) {
            foreach ($ch['materials'] as $mat) {
                if ($mat['id'] === $material->id) {
                    return ! $mat['is_locked'];
                }
            }
        }

        return true;
    }

    /**
     * Check if a specific exam is accessible for a student (not locked).
     */
    public function isExamAccessible(User $student, OnlineExam $exam): bool
    {
        $outline = $this->getSubjectOutlineForStudent(
            $student,
            $exam->subject_id,
            $exam->classroom_id,
            $exam->academic_year_id,
            $exam->semester
        );

        foreach ($outline['chapters'] as $ch) {
            foreach ($ch['quizzes'] as $qz) {
                if ($qz['id'] === $exam->id) {
                    return ! $qz['is_locked'];
                }
            }
        }

        foreach ($outline['term_exams'] as $te) {
            if ($te['id'] === $exam->id) {
                return ! $te['is_locked'];
            }
        }

        return true;
    }

    /**
     * Mark a material as completed for student.
     */
    public function markMaterialAsCompleted(User $student, int $materialId): void
    {
        MaterialProgress::firstOrCreate([
            'user_id' => $student->id,
            'material_id' => $materialId,
        ], [
            'completed_at' => now(),
        ]);
    }
}
