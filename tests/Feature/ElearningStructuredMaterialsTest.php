<?php

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\MaterialChapter;
use App\Models\MaterialProgress;
use App\Models\OnlineExam;
use App\Models\OnlineExamSubmission;
use App\Models\OnlineMaterial;
use App\Models\Profile;
use App\Models\StudentProfile;
use App\Models\Subject;
use App\Models\User;
use App\Services\ElearningCourseOutlineService;

beforeEach(function () {
    $this->academicYear = AcademicYear::factory()->create([
        'is_active' => true,
        'active_semester' => 'Ganjil',
    ]);

    $this->classroom = Classroom::factory()->create([
        'academic_year_id' => $this->academicYear->id,
    ]);

    $this->subject = Subject::factory()->create();

    $this->studentUser = User::factory()->create();
    $this->studentProfile = StudentProfile::factory()->create([
        'classroom_id' => $this->classroom->id,
    ]);

    Profile::create([
        'user_id' => $this->studentUser->id,
        'profileable_id' => $this->studentProfile->id,
        'profileable_type' => StudentProfile::class,
    ]);
});

test('course outline service groups items by chapters correctly', function () {
    $chapter1 = MaterialChapter::create([
        'subject_id' => $this->subject->id,
        'classroom_id' => $this->classroom->id,
        'academic_year_id' => $this->academicYear->id,
        'semester' => 'Ganjil',
        'title' => 'Bab 1 Aljabar',
        'order' => 1,
        'is_published' => true,
        'created_by' => $this->studentUser->id,
    ]);

    $material1 = OnlineMaterial::create([
        'subject_id' => $this->subject->id,
        'chapter_id' => $chapter1->id,
        'classroom_id' => $this->classroom->id,
        'academic_year_id' => $this->academicYear->id,
        'semester' => 'Ganjil',
        'title' => 'Teks Pengenalan Aljabar',
        'type' => 'text',
        'is_published' => true,
        'order' => 1,
        'created_by' => $this->studentUser->id,
    ]);

    $quiz1 = OnlineExam::create([
        'subject_id' => $this->subject->id,
        'chapter_id' => $chapter1->id,
        'classroom_id' => $this->classroom->id,
        'academic_year_id' => $this->academicYear->id,
        'semester' => 'Ganjil',
        'title' => 'Kuis Bab 1',
        'exam_type' => 'quiz',
        'is_published' => true,
        'passing_grade' => 70,
        'created_by' => $this->studentUser->id,
    ]);

    $service = app(ElearningCourseOutlineService::class);
    $outline = $service->getSubjectOutlineForStudent(
        $this->studentUser,
        $this->subject->id,
        $this->classroom->id,
        $this->academicYear->id,
        'Ganjil'
    );

    expect($outline['chapters'])->toHaveCount(1);
    expect($outline['chapters'][0]['title'])->toBe('Bab 1 Aljabar');
    expect($outline['chapters'][0]['is_locked'])->toBeFalse();
    expect($outline['chapters'][0]['materials'])->toHaveCount(1);
    expect($outline['chapters'][0]['quizzes'])->toHaveCount(1);
});

test('subsequent chapter is locked until previous chapter materials and quiz are completed', function () {
    $chapter1 = MaterialChapter::create([
        'subject_id' => $this->subject->id,
        'classroom_id' => $this->classroom->id,
        'academic_year_id' => $this->academicYear->id,
        'semester' => 'Ganjil',
        'title' => 'Bab 1',
        'order' => 1,
        'is_published' => true,
        'created_by' => $this->studentUser->id,
    ]);

    $material1 = OnlineMaterial::create([
        'subject_id' => $this->subject->id,
        'chapter_id' => $chapter1->id,
        'classroom_id' => $this->classroom->id,
        'academic_year_id' => $this->academicYear->id,
        'semester' => 'Ganjil',
        'title' => 'Materi Bab 1',
        'type' => 'text',
        'is_published' => true,
        'order' => 1,
        'created_by' => $this->studentUser->id,
    ]);

    $quiz1 = OnlineExam::create([
        'subject_id' => $this->subject->id,
        'chapter_id' => $chapter1->id,
        'classroom_id' => $this->classroom->id,
        'academic_year_id' => $this->academicYear->id,
        'semester' => 'Ganjil',
        'title' => 'Kuis 1',
        'exam_type' => 'quiz',
        'is_published' => true,
        'passing_grade' => 70,
        'created_by' => $this->studentUser->id,
    ]);

    $chapter2 = MaterialChapter::create([
        'subject_id' => $this->subject->id,
        'classroom_id' => $this->classroom->id,
        'academic_year_id' => $this->academicYear->id,
        'semester' => 'Ganjil',
        'title' => 'Bab 2',
        'order' => 2,
        'is_published' => true,
        'created_by' => $this->studentUser->id,
    ]);

    $service = app(ElearningCourseOutlineService::class);

    // Initial state: Chapter 2 is locked
    $outline = $service->getSubjectOutlineForStudent($this->studentUser, $this->subject->id, $this->classroom->id, $this->academicYear->id, 'Ganjil');
    expect($outline['chapters'][1]['is_locked'])->toBeTrue();

    // Mark material 1 completed
    MaterialProgress::create(['user_id' => $this->studentUser->id, 'material_id' => $material1->id, 'completed_at' => now()]);

    // Still locked because quiz 1 not attempted
    $outline = $service->getSubjectOutlineForStudent($this->studentUser, $this->subject->id, $this->classroom->id, $this->academicYear->id, 'Ganjil');
    expect($outline['chapters'][1]['is_locked'])->toBeTrue();

    // Complete quiz 1
    OnlineExamSubmission::create([
        'online_exam_id' => $quiz1->id,
        'student_id' => $this->studentUser->id,
        'started_at' => now(),
        'submitted_at' => now(),
        'status' => 'submitted',
    ]);

    // Now Chapter 2 is unlocked
    $outline = $service->getSubjectOutlineForStudent($this->studentUser, $this->subject->id, $this->classroom->id, $this->academicYear->id, 'Ganjil');
    expect($outline['chapters'][1]['is_locked'])->toBeFalse();
});
