<?php

declare(strict_types=1);

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\DiniyahGrade;
use App\Models\DiniyahSubject;
use App\Models\Level;
use App\Models\Profile;
use App\Models\StudentProfile;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->withoutVite();
});

test('admin can access diniyah subject management page', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $level = Level::factory()->create(['education_level' => 'sd']);
    DiniyahSubject::create([
        'name' => 'Tauhid',
        'level_id' => $level->id,
        'assessment_type' => 'numeric',
    ]);

    $this->actingAs($admin)
        ->get(route('academic.diniyah-subjects'))
        ->assertSuccessful()
        ->assertSeeLivewire('shared.academic.diniyah-subjects');
});

test('admin can access diniyah grading page', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('admin.report-card.diniyah-grading'))
        ->assertSuccessful()
        ->assertSeeLivewire('admin.report-card.diniyah-grading');
});

test('admin can access diniyah report creation page', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('admin.report-card.diniyah'))
        ->assertSuccessful()
        ->assertSeeLivewire('admin.report-card.diniyah-create');
});

test('diniyah report card can be generated', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $academicYear = AcademicYear::factory()->create(['is_active' => true]);
    $level = Level::factory()->create(['education_level' => 'sd']);
    $classroom = Classroom::factory()->create([
        'academic_year_id' => $academicYear->id,
        'level_id' => $level->id,
    ]);

    $student = User::factory()->create(['role' => 'siswa']);
    $studentProfile = StudentProfile::factory()->create([
        'classroom_id' => $classroom->id,
    ]);

    Profile::create([
        'user_id' => $student->id,
        'profileable_id' => $studentProfile->id,
        'profileable_type' => StudentProfile::class,
    ]);

    $subject = DiniyahSubject::create([
        'name' => 'Tauhid',
        'level_id' => $level->id,
        'assessment_type' => 'numeric',
    ]);

    DiniyahGrade::create([
        'student_id' => $student->id,
        'diniyah_subject_id' => $subject->id,
        'classroom_id' => $classroom->id,
        'academic_year_id' => $academicYear->id,
        'semester' => '1',
        'knowledge_grade' => 90,
    ]);

    $this->actingAs($admin);

    Livewire::test('admin.report-card.diniyah-create')
        ->set('academic_year_id', $academicYear->id)
        ->set('classroom_id', $classroom->id)
        ->set('student_id', $student->id)
        ->set('semester', '1')
        ->call('generate')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('diniyah_report_cards', [
        'student_id' => $student->id,
        'classroom_id' => $classroom->id,
        'academic_year_id' => $academicYear->id,
        'semester' => '1',
    ]);
});
