<?php

declare(strict_types=1);

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Level;
use App\Models\MaterialChapter;
use App\Models\OnlineExam;
use App\Models\OnlineExamQuestion;
use App\Models\OnlineExamSubmission;
use App\Models\OnlineMaterial;
use App\Models\Subject;
use App\Models\User;
use App\Services\ElearningCourseCloneService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

test('admin can switch between ganjil and genap semesters and manage materials independently', function () {
    $admin = User::factory()->admin()->create();
    $level = Level::factory()->create();
    $academicYear = AcademicYear::factory()->create(['is_active' => true, 'active_semester' => 'Ganjil']);
    $classroom = Classroom::factory()->create(['level_id' => $level->id, 'academic_year_id' => $academicYear->id]);
    $subject = Subject::factory()->create(['level_id' => $level->id]);

    $component = Livewire::actingAs($admin)
        ->test('admin.elearning.course')
        ->set('classroomId', $classroom->id)
        ->call('openSubject', $subject->id)
        ->assertSet('selectedSemester', null);

    // 1. Create chapter in Semester Ganjil
    $component
        ->call('setSemester', 'Ganjil')
        ->assertSet('selectedSemester', 'Ganjil')
        ->call('createChapter')
        ->set('chapterTitle', 'Bab 1 Ganjil')
        ->call('saveChapter')
        ->assertHasNoErrors();

    $ganjilChapter = MaterialChapter::firstWhere('title', 'Bab 1 Ganjil');
    expect($ganjilChapter)->not->toBeNull()
        ->and($ganjilChapter->semester)->toBe('Ganjil');

    // 2. Switch to Semester Genap and check that Bab 1 Ganjil is not present in Genap
    $component
        ->call('setSemester', 'Genap')
        ->assertSet('selectedSemester', 'Genap');

    // Create chapter in Semester Genap
    $component
        ->call('createChapter')
        ->set('chapterTitle', 'Bab 1 Genap')
        ->call('saveChapter')
        ->assertHasNoErrors();

    $genapChapter = MaterialChapter::firstWhere('title', 'Bab 1 Genap');
    expect($genapChapter)->not->toBeNull()
        ->and($genapChapter->semester)->toBe('Genap');

    expect(MaterialChapter::where('semester', 'Ganjil')->count())->toBe(1)
        ->and(MaterialChapter::where('semester', 'Genap')->count())->toBe(1);
});

test('course clone service duplicates chapters, materials, and quizzes without copying student submissions', function () {
    $teacher = User::factory()->create();
    $student = User::factory()->create();
    $level = Level::factory()->create();
    $year2024 = AcademicYear::factory()->create(['name' => '2024/2025', 'is_active' => false, 'active_semester' => 'Ganjil']);
    $year2025 = AcademicYear::factory()->create(['name' => '2025/2026', 'is_active' => true, 'active_semester' => 'Ganjil']);

    $class2024 = Classroom::factory()->create(['level_id' => $level->id, 'academic_year_id' => $year2024->id]);
    $class2025 = Classroom::factory()->create(['level_id' => $level->id, 'academic_year_id' => $year2025->id]);
    $subject = Subject::factory()->create(['level_id' => $level->id]);

    // Setup source course in 2024/2025 Semester Ganjil
    $chapter = MaterialChapter::create([
        'subject_id' => $subject->id,
        'classroom_id' => $class2024->id,
        'academic_year_id' => $year2024->id,
        'semester' => 'Ganjil',
        'title' => 'Bab Asal 2024',
        'order' => 1,
        'created_by' => $teacher->id,
    ]);

    $material = OnlineMaterial::create([
        'subject_id' => $subject->id,
        'chapter_id' => $chapter->id,
        'classroom_id' => $class2024->id,
        'academic_year_id' => $year2024->id,
        'semester' => 'Ganjil',
        'title' => 'Materi Dokumen',
        'type' => 'text',
        'content' => 'Penjelasan Aljabar',
        'attachments' => [['path' => 'elearning/materials/demo.pdf', 'name' => 'demo.pdf']],
        'order' => 1,
        'created_by' => $teacher->id,
    ]);

    $exam = OnlineExam::create([
        'subject_id' => $subject->id,
        'chapter_id' => $chapter->id,
        'classroom_id' => $class2024->id,
        'academic_year_id' => $year2024->id,
        'semester' => 'Ganjil',
        'title' => 'Kuis Bab 1',
        'exam_type' => 'quiz',
        'passing_grade' => 75,
        'created_by' => $teacher->id,
    ]);

    $question = OnlineExamQuestion::create([
        'online_exam_id' => $exam->id,
        'question_type' => 'multiple_choice',
        'question_text' => 'Berapa 1+1?',
        'options' => ['A' => '1', 'B' => '2'],
        'correct_answer' => 'B',
        'points' => 10,
        'order' => 1,
    ]);

    // Student from 2024 already submitted exam
    $submission = OnlineExamSubmission::create([
        'online_exam_id' => $exam->id,
        'student_id' => $student->id,
        'status' => 'graded',
        'total_score' => 90,
    ]);

    // Now clone into 2025/2026 Semester Ganjil
    $service = new ElearningCourseCloneService;
    $stats = $service->cloneCourse(
        source: [
            'subject_id' => $subject->id,
            'classroom_id' => $class2024->id,
            'academic_year_id' => $year2024->id,
            'semester' => 'Ganjil',
        ],
        target: [
            'subject_id' => $subject->id,
            'classroom_id' => $class2025->id,
            'academic_year_id' => $year2025->id,
            'semester' => 'Ganjil',
            'created_by' => $teacher->id,
        ]
    );

    expect($stats['chapters_count'])->toBe(1)
        ->and($stats['materials_count'])->toBe(1)
        ->and($stats['exams_count'])->toBe(1)
        ->and($stats['questions_count'])->toBe(1);

    // Verify cloned target data
    $clonedChapter = MaterialChapter::where('academic_year_id', $year2025->id)->first();
    expect($clonedChapter)->not->toBeNull()
        ->and($clonedChapter->title)->toBe('Bab Asal 2024')
        ->and($clonedChapter->classroom_id)->toBe($class2025->id);

    $clonedMaterial = OnlineMaterial::where('academic_year_id', $year2025->id)->first();
    expect($clonedMaterial)->not->toBeNull()
        ->and($clonedMaterial->attachments[0]['path'])->toBe('elearning/materials/demo.pdf'); // Reuses file path without new file duplication

    $clonedExam = OnlineExam::where('academic_year_id', $year2025->id)->first();
    expect($clonedExam)->not->toBeNull()
        ->and($clonedExam->id)->not->toBe($exam->id)
        ->and($clonedExam->title)->toBe('Kuis Bab 1');

    $clonedQuestion = OnlineExamQuestion::where('online_exam_id', $clonedExam->id)->first();
    expect($clonedQuestion)->not->toBeNull()
        ->and($clonedQuestion->question_text)->toBe('Berapa 1+1?');

    // CRITICAL: Ensure old submissions were NOT copied to new exam
    expect(OnlineExamSubmission::where('online_exam_id', $clonedExam->id)->count())->toBe(0)
        ->and(OnlineExamSubmission::where('online_exam_id', $exam->id)->count())->toBe(1);
});

test('exam deletion is blocked if student submissions exist to protect assessment history', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->create();
    $level = Level::factory()->create();
    $year = AcademicYear::factory()->create(['is_active' => true, 'active_semester' => 'Ganjil']);
    $classroom = Classroom::factory()->create(['level_id' => $level->id, 'academic_year_id' => $year->id]);
    $subject = Subject::factory()->create(['level_id' => $level->id]);

    $exam = OnlineExam::create([
        'subject_id' => $subject->id,
        'classroom_id' => $classroom->id,
        'academic_year_id' => $year->id,
        'semester' => 'Ganjil',
        'title' => 'Ulangan Penting',
        'exam_type' => 'midterm',
        'passing_grade' => 70,
        'created_by' => $admin->id,
    ]);

    OnlineExamSubmission::create([
        'online_exam_id' => $exam->id,
        'student_id' => $student->id,
        'status' => 'graded',
        'total_score' => 85,
    ]);

    Livewire::actingAs($admin)
        ->test('admin.elearning.course')
        ->set('classroomId', $classroom->id)
        ->call('openSubject', $subject->id)
        ->call('deleteExam', $exam->id);

    // Exam still exists because submission exists!
    expect(OnlineExam::find($exam->id))->not->toBeNull();
});

test('grade recap filters by academic year and semester', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->create();
    $level = Level::factory()->create();
    $year = AcademicYear::factory()->create(['is_active' => true, 'active_semester' => 'Ganjil']);
    $classroom = Classroom::factory()->create(['level_id' => $level->id, 'academic_year_id' => $year->id]);
    $subject = Subject::factory()->create(['level_id' => $level->id]);

    $examGanjil = OnlineExam::create([
        'subject_id' => $subject->id,
        'classroom_id' => $classroom->id,
        'academic_year_id' => $year->id,
        'semester' => 'Ganjil',
        'title' => 'UTS Ganjil',
        'exam_type' => 'midterm',
        'passing_grade' => 70,
        'created_by' => $admin->id,
    ]);

    $examGenap = OnlineExam::create([
        'subject_id' => $subject->id,
        'classroom_id' => $classroom->id,
        'academic_year_id' => $year->id,
        'semester' => 'Genap',
        'title' => 'UAS Genap',
        'exam_type' => 'final',
        'passing_grade' => 70,
        'created_by' => $admin->id,
    ]);

    OnlineExamSubmission::create([
        'online_exam_id' => $examGanjil->id,
        'student_id' => $student->id,
        'status' => 'graded',
        'total_score' => 88,
    ]);

    OnlineExamSubmission::create([
        'online_exam_id' => $examGenap->id,
        'student_id' => $student->id,
        'status' => 'graded',
        'total_score' => 92,
    ]);

    // Test with Ganjil filter
    Livewire::actingAs($admin)
        ->test('admin.elearning.grade-recap')
        ->set('filterSemester', 'Ganjil')
        ->assertSee('UTS Ganjil')
        ->assertDontSee('UAS Genap')
        ->set('filterSemester', 'Genap')
        ->assertSee('UAS Genap')
        ->assertDontSee('UTS Ganjil');
});
