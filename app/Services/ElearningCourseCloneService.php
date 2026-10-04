<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MaterialChapter;
use App\Models\OnlineExam;
use App\Models\OnlineExamQuestion;
use App\Models\OnlineMaterial;
use Illuminate\Support\Facades\DB;

class ElearningCourseCloneService
{
    /**
     * Clone course structure (handbooks, chapters, materials, and exams) from source to target scope.
     * Note: Student submissions, answers, and progress are NOT copied, preserving historical assessment integrity.
     *
     * @param  array{subject_id: int, classroom_id: int, academic_year_id: int, semester: string}  $source
     * @param  array{subject_id: int, classroom_id: int, academic_year_id: int, semester: string, created_by: int}  $target
     * @return array{handbooks_count: int, chapters_count: int, materials_count: int, exams_count: int, questions_count: int}
     */
    public function cloneCourse(array $source, array $target): array
    {
        return DB::transaction(function () use ($source, $target) {
            $stats = [
                'handbooks_count' => 0,
                'chapters_count' => 0,
                'materials_count' => 0,
                'exams_count' => 0,
                'questions_count' => 0,
            ];

            $targetScope = [
                'subject_id' => $target['subject_id'],
                'classroom_id' => $target['classroom_id'],
                'academic_year_id' => $target['academic_year_id'],
                'semester' => $target['semester'],
            ];

            // 1. Clone Handbooks (materials without chapter)
            $sourceHandbooks = OnlineMaterial::where($source)
                ->whereNull('chapter_id')
                ->orderBy('order')
                ->get();

            $maxHandbookOrder = (int) OnlineMaterial::where($targetScope)->whereNull('chapter_id')->max('order');

            foreach ($sourceHandbooks as $hb) {
                $maxHandbookOrder++;
                OnlineMaterial::create($targetScope + [
                    'chapter_id' => null,
                    'title' => $hb->title,
                    'type' => $hb->type ?? 'handbook',
                    'content' => $hb->content,
                    'video_url' => $hb->video_url,
                    'transcript' => $hb->transcript,
                    'attachments' => $hb->attachments ?? [],
                    'order' => $maxHandbookOrder,
                    'is_published' => $hb->is_published,
                    'published_at' => $hb->is_published ? now() : null,
                    'created_by' => $target['created_by'],
                ]);
                $stats['handbooks_count']++;
            }

            // 2. Clone Chapters (with materials and quizzes)
            $sourceChapters = MaterialChapter::where($source)
                ->with([
                    'materials' => fn ($q) => $q->orderBy('order'),
                    'exams' => fn ($q) => $q->with(['questions' => fn ($qq) => $qq->orderBy('order')]),
                ])
                ->orderBy('order')
                ->get();

            $maxChapterOrder = (int) MaterialChapter::where($targetScope)->max('order');

            foreach ($sourceChapters as $ch) {
                $maxChapterOrder++;
                $newChapter = MaterialChapter::create($targetScope + [
                    'title' => $ch->title,
                    'description' => $ch->description,
                    'order' => $maxChapterOrder,
                    'is_published' => $ch->is_published,
                    'published_at' => $ch->is_published ? now() : null,
                    'created_by' => $target['created_by'],
                ]);
                $stats['chapters_count']++;

                // Clone materials inside chapter
                foreach ($ch->materials as $mat) {
                    OnlineMaterial::create($targetScope + [
                        'chapter_id' => $newChapter->id,
                        'title' => $mat->title,
                        'type' => $mat->type ?? 'text',
                        'content' => $mat->content,
                        'video_url' => $mat->video_url,
                        'transcript' => $mat->transcript,
                        'attachments' => $mat->attachments ?? [],
                        'order' => $mat->order,
                        'is_published' => $mat->is_published,
                        'published_at' => $mat->is_published ? now() : null,
                        'created_by' => $target['created_by'],
                    ]);
                    $stats['materials_count']++;
                }

                // Clone quizzes inside chapter
                foreach ($ch->exams as $exam) {
                    $newExam = OnlineExam::create($targetScope + [
                        'chapter_id' => $newChapter->id,
                        'title' => $exam->title,
                        'description' => $exam->description,
                        'exam_type' => $exam->exam_type ?? 'quiz',
                        'duration_minutes' => $exam->duration_minutes,
                        'passing_grade' => $exam->passing_grade,
                        'shuffle_questions' => $exam->shuffle_questions,
                        'attachment_path' => $exam->attachment_path,
                        'attachment_name' => $exam->attachment_name,
                        'is_published' => $exam->is_published,
                        'created_by' => $target['created_by'],
                    ]);
                    $stats['exams_count']++;

                    foreach ($exam->questions as $question) {
                        OnlineExamQuestion::create([
                            'online_exam_id' => $newExam->id,
                            'question_type' => $question->question_type,
                            'question_text' => $question->question_text,
                            'options' => $question->options,
                            'correct_answer' => $question->correct_answer,
                            'points' => $question->points,
                            'order' => $question->order,
                            'attachment_path' => $question->attachment_path,
                            'attachment_name' => $question->attachment_name,
                        ]);
                        $stats['questions_count']++;
                    }
                }
            }

            // 3. Clone Term Exams (UTS / UAS without chapter)
            $sourceTermExams = OnlineExam::where($source)
                ->whereNull('chapter_id')
                ->whereIn('exam_type', ['midterm', 'final'])
                ->with(['questions' => fn ($q) => $q->orderBy('order')])
                ->get();

            foreach ($sourceTermExams as $termExam) {
                $newTermExam = OnlineExam::create($targetScope + [
                    'chapter_id' => null,
                    'title' => $termExam->title,
                    'description' => $termExam->description,
                    'exam_type' => $termExam->exam_type,
                    'duration_minutes' => $termExam->duration_minutes,
                    'passing_grade' => $termExam->passing_grade,
                    'shuffle_questions' => $termExam->shuffle_questions,
                    'attachment_path' => $termExam->attachment_path,
                    'attachment_name' => $termExam->attachment_name,
                    'is_published' => $termExam->is_published,
                    'created_by' => $target['created_by'],
                ]);
                $stats['exams_count']++;

                foreach ($termExam->questions as $question) {
                    OnlineExamQuestion::create([
                        'online_exam_id' => $newTermExam->id,
                        'question_type' => $question->question_type,
                        'question_text' => $question->question_text,
                        'options' => $question->options,
                        'correct_answer' => $question->correct_answer,
                        'points' => $question->points,
                        'order' => $question->order,
                        'attachment_path' => $question->attachment_path,
                        'attachment_name' => $question->attachment_name,
                    ]);
                    $stats['questions_count']++;
                }
            }

            return $stats;
        });
    }
}
