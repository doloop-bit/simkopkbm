<?php

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Level;
use App\Models\ReportCard;
use App\Models\Score;
use App\Models\ScoreCategory;
use App\Models\StudentProfile;
use App\Models\Subject;
use App\Models\User;
use Livewire\Volt\Volt;

beforeEach(function () {
    $this->withoutVite();
});

test('admin can access report card creation page', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('admin.report-card.create'))
        ->assertSuccessful()
        ->assertSeeLivewire('admin.report-card.create');
});

test('report card can be generated for students', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $academicYear = AcademicYear::factory()->create();
    $level = Level::factory()->create();
    $classroom = Classroom::factory()->create([
        'academic_year_id' => $academicYear->id,
        'level_id' => $level->id,
    ]);

    $student = User::factory()->create();
    $studentProfile = StudentProfile::factory()->create([
        'classroom_id' => $classroom->id,
    ]);

    // Create profile relationship
    $student->profiles()->create([
        'profileable_id' => $studentProfile->id,
        'profileable_type' => StudentProfile::class,
    ]);

    // Create subjects and scores
    $subject = Subject::factory()->create(['level_id' => $level->id]);
    $category = ScoreCategory::factory()->create();

    Score::factory()->create([
        'student_id' => $student->id,
        'subject_id' => $subject->id,
        'classroom_id' => $classroom->id,
        'academic_year_id' => $academicYear->id,
        'score_category_id' => $category->id,
        'score' => 85,
    ]);

    $this->actingAs($admin);

    Volt::test('admin.report-card.create')
        ->set('academicYearId', $academicYear->id)
        ->set('classroomId', $classroom->id)
        ->set('semester', '1')
        ->set('selectedStudents', [$studentProfile->id])
        ->call('generateReportCards')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('report_cards', [
        'student_id' => $student->id,
        'classroom_id' => $classroom->id,
        'academic_year_id' => $academicYear->id,
        'semester' => '1',
        'status' => 'draft',
    ]);
});

test('report card can be exported to pdf', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $student = User::factory()->create();
    $academicYear = AcademicYear::factory()->create();
    $classroom = Classroom::factory()->create(['academic_year_id' => $academicYear->id]);

    $reportCard = ReportCard::factory()->create([
        'student_id' => $student->id,
        'classroom_id' => $classroom->id,
        'academic_year_id' => $academicYear->id,
        'scores' => [
            1 => ['subject_name' => 'Matematika', 'score' => 85],
            2 => ['subject_name' => 'Bahasa Indonesia', 'score' => 90],
        ],
        'gpa' => 87.5,
    ]);

    $this->actingAs($admin);

    Volt::test('admin.report-card.create')
        ->call('exportPdf', $reportCard->id)
        ->assertSuccessful();
});

test('student can view their own report card', function () {
    $student = User::factory()->create();
    $academicYear = AcademicYear::factory()->create();
    $classroom = Classroom::factory()->create(['academic_year_id' => $academicYear->id]);

    $reportCard = ReportCard::factory()->create([
        'student_id' => $student->id,
        'classroom_id' => $classroom->id,
        'academic_year_id' => $academicYear->id,
    ]);

    $this->actingAs($student);

    Volt::test('admin.report-card.create')
        ->call('exportPdf', $reportCard->id)
        ->assertSuccessful();
});

test('non-admin cannot access report card creation page', function () {
    $teacher = User::factory()->create(['role' => 'guru']);

    $this->actingAs($teacher)
        ->get(route('admin.report-card.create'))
        ->assertForbidden();
});
