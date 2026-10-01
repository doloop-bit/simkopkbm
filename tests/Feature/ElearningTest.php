<?php

declare(strict_types=1);

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Level;
use App\Models\OnlineExam;
use App\Models\OnlineExamAnswer;
use App\Models\OnlineExamQuestion;
use App\Models\OnlineExamSubmission;
use App\Models\OnlineMaterial;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

test('admin can access elearning materials page', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.elearning.materials'))
        ->assertOk()
        ->assertSee('Materi Online');
});

test('admin can create online material via livewire', function () {
    $admin = User::factory()->admin()->create();
    $level = Level::factory()->create();
    $classroom = Classroom::factory()->create(['level_id' => $level->id]);
    $subject = Subject::factory()->create(['level_id' => $level->id]);
    $academicYear = AcademicYear::factory()->create(['is_active' => true]);

    Livewire::actingAs($admin)
        ->test('admin.elearning.materials')
        ->set('subject_id', $subject->id)
        ->set('classroom_id', $classroom->id)
        ->set('academic_year_id', $academicYear->id)
        ->set('semester', '1')
        ->set('title', 'Materi Aljabar Linear')
        ->set('content', '<p>Pembahasan matriks dan vektor.</p>')
        ->set('is_published', true)
        ->call('save')
        ->assertHasNoErrors();

    expect(OnlineMaterial::where('title', 'Materi Aljabar Linear')->exists())->toBeTrue();
});

test('admin can access elearning exams page and create exam', function () {
    $admin = User::factory()->admin()->create();
    $level = Level::factory()->create();
    $classroom = Classroom::factory()->create(['level_id' => $level->id]);
    $subject = Subject::factory()->create(['level_id' => $level->id]);
    $academicYear = AcademicYear::factory()->create(['is_active' => true]);

    $this->actingAs($admin)
        ->get(route('admin.elearning.exams'))
        ->assertOk()
        ->assertSee('Ulangan Online');

    Livewire::actingAs($admin)
        ->test('admin.elearning.exams')
        ->set('subject_id', $subject->id)
        ->set('classroom_id', $classroom->id)
        ->set('academic_year_id', $academicYear->id)
        ->set('semester', '1')
        ->set('title', 'Ulangan Harian Bab 1')
        ->set('exam_type', 'daily')
        ->set('duration_minutes', 60)
        ->set('passing_grade', 75)
        ->set('is_published', true)
        ->call('save')
        ->assertHasNoErrors();

    expect(OnlineExam::where('title', 'Ulangan Harian Bab 1')->exists())->toBeTrue();
});

test('admin can add questions to an exam', function () {
    $admin = User::factory()->admin()->create();
    $exam = OnlineExam::factory()->create(['created_by' => $admin->id]);

    Livewire::actingAs($admin)
        ->test('admin.elearning.exam-questions', ['examId' => $exam->id])
        ->set('question_type', 'multiple_choice')
        ->set('question_text', 'Berapakah 2 + 2?')
        ->set('options', ['3', '4', '5', '6'])
        ->set('correct_answer', 'B')
        ->set('points', 25)
        ->call('save')
        ->assertHasNoErrors();

    expect($exam->questions()->count())->toBe(1);
    expect($exam->questions()->first()->correct_answer)->toBe('B');
});

test('admin can view grade recap page', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.elearning.grade-recap'))
        ->assertOk()
        ->assertSee('Rekap Nilai');
});

test('student can access student dashboard', function () {
    $student = User::factory()->siswa()->create();

    $this->actingAs($student)
        ->get(route('student.dashboard'))
        ->assertOk()
        ->assertSee('Dashboard Siswa');
});

test('student can access materials list and detail', function () {
    $student = User::factory()->siswa()->create();
    $material = OnlineMaterial::factory()->create([
        'title' => 'Fisika Dasar Gelombang',
        'is_published' => true,
    ]);

    $this->actingAs($student)
        ->get(route('student.materials'))
        ->assertOk()
        ->assertSee('Materi Pembelajaran')
        ->assertSee('Fisika Dasar Gelombang');

    $this->actingAs($student)
        ->get(route('student.material-detail', ['materialId' => $material->id]))
        ->assertOk()
        ->assertSee('Fisika Dasar Gelombang');
});

test('student can access exams list', function () {
    $student = User::factory()->siswa()->create();
    OnlineExam::factory()->create([
        'title' => 'Ujian Tengah Semester',
        'is_published' => true,
        'start_time' => now()->subDay(),
        'end_time' => now()->addDays(2),
    ]);

    $this->actingAs($student)
        ->get(route('student.exams'))
        ->assertOk()
        ->assertSee('Ulangan Online')
        ->assertSee('Ujian Tengah Semester');
});

test('student can take exam and submit answers via livewire', function () {
    $student = User::factory()->siswa()->create();
    $exam = OnlineExam::factory()->create([
        'title' => 'Kuis Matematika',
        'is_published' => true,
    ]);

    $q1 = OnlineExamQuestion::factory()->create([
        'online_exam_id' => $exam->id,
        'question_type' => 'multiple_choice',
        'correct_answer' => 'A',
        'points' => 100,
    ]);

    Livewire::actingAs($student)
        ->test('student.take-exam', ['examId' => $exam->id])
        ->set("answers.{$q1->id}.selected_option", 'A')
        ->call('submitExam')
        ->assertRedirect();

    $submission = OnlineExamSubmission::where('online_exam_id', $exam->id)
        ->where('student_id', $student->id)
        ->first();

    expect($submission)->not->toBeNull();
    expect($submission->status)->toBe('graded');
    expect((float) $submission->total_score)->toBe(100.0);
});

test('student can view exam result', function () {
    $student = User::factory()->siswa()->create();
    $exam = OnlineExam::factory()->create(['title' => 'Ujian Akhir Semester']);
    $submission = OnlineExamSubmission::factory()->create([
        'online_exam_id' => $exam->id,
        'student_id' => $student->id,
        'status' => 'graded',
        'total_score' => 90.0,
    ]);

    Livewire::actingAs($student)
        ->test('student.exam-result', ['submissionId' => $submission->id])
        ->assertSee('Hasil Ulangan')
        ->assertSee('90.0')
        ->assertSee('LULUS');
});

test('admin can grade essay answers and finalize submission', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->siswa()->create();
    $exam = OnlineExam::factory()->create(['title' => 'Ujian Bahasa']);

    $qEssay = OnlineExamQuestion::factory()->essay()->create([
        'online_exam_id' => $exam->id,
        'points' => 100,
    ]);

    $submission = OnlineExamSubmission::factory()->create([
        'online_exam_id' => $exam->id,
        'student_id' => $student->id,
        'status' => 'submitted',
        'total_score' => null,
    ]);

    $answer = OnlineExamAnswer::factory()->create([
        'submission_id' => $submission->id,
        'question_id' => $qEssay->id,
        'answer_text' => 'Ini jawaban essay yang sangat lengkap.',
        'selected_option' => null,
        'score' => null,
    ]);

    Livewire::actingAs($admin)
        ->test('admin.elearning.submission-detail', ['submissionId' => $submission->id])
        ->set("grades.{$answer->id}.score", 85)
        ->set("grades.{$answer->id}.feedback", 'Bagus sekali!')
        ->call('finalizeGrading');

    $submission->refresh();
    $answer->refresh();

    expect($submission->status)->toBe('graded');
    expect((float) $submission->total_score)->toBe(85.0);
    expect((float) $answer->score)->toBe(85.0);
    expect($answer->teacher_feedback)->toBe('Bagus sekali!');
});

test('auto-grading calculates score correctly for multiple choice answers', function () {
    $exam = OnlineExam::factory()->create();

    $q1 = OnlineExamQuestion::factory()->create([
        'online_exam_id' => $exam->id,
        'question_type' => 'multiple_choice',
        'correct_answer' => 'A',
        'points' => 50,
    ]);

    $q2 = OnlineExamQuestion::factory()->create([
        'online_exam_id' => $exam->id,
        'question_type' => 'multiple_choice',
        'correct_answer' => 'B',
        'points' => 50,
    ]);

    $submission = OnlineExamSubmission::factory()->create([
        'online_exam_id' => $exam->id,
        'status' => 'in_progress',
        'total_score' => null,
    ]);

    OnlineExamAnswer::factory()->create([
        'submission_id' => $submission->id,
        'question_id' => $q1->id,
        'selected_option' => 'A', // correct (50 pts)
        'score' => null,
    ]);

    OnlineExamAnswer::factory()->create([
        'submission_id' => $submission->id,
        'question_id' => $q2->id,
        'selected_option' => 'C', // wrong (0 pts)
        'score' => null,
    ]);

    $submission->autoGrade();

    $submission->refresh();
    expect($submission->status)->toBe('graded');
    expect((float) $submission->total_score)->toBe(50.0);
});
