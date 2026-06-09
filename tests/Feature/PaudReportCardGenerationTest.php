<?php

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\PaudReportCard;
use App\Models\Profile;
use App\Models\StudentProfile;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->academicYear = AcademicYear::factory()->create(['is_active' => true]);
    $this->classroom = Classroom::factory()->create();

    $this->student = User::factory()->siswa()->create();
    $studentProfile = StudentProfile::factory()->create([
        'classroom_id' => $this->classroom->id,
    ]);
    Profile::create([
        'user_id' => $this->student->id,
        'profileable_id' => $studentProfile->id,
        'profileable_type' => StudentProfile::class,
    ]);
});

test('admin can access paud report generation page', function () {
    actingAs($this->admin)
        ->get(route('admin.report-card.paud.generate'))
        ->assertOk();
});

test('admin can generate paud report cards', function () {
    actingAs($this->admin);

    Livewire::test('admin.report-card.paud.paud-report-create')
        ->set('academic_year_id', $this->academicYear->id)
        ->set('classroom_id', $this->classroom->id)
        ->set('semester', '1')
        ->set('display_mode', 'cp')
        ->set('selected_students', [$this->student->id])
        ->call('generateReports')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('paud_report_cards', [
        'student_id' => $this->student->id,
        'classroom_id' => $this->classroom->id,
        'academic_year_id' => $this->academicYear->id,
        'semester' => '1',
        'display_mode' => 'cp',
        'status' => 'draft',
    ]);
});

test('admin can publish paud report card', function () {
    $report = PaudReportCard::create([
        'student_id' => $this->student->id,
        'classroom_id' => $this->classroom->id,
        'academic_year_id' => $this->academicYear->id,
        'semester' => '1',
        'display_mode' => 'cp',
        'status' => 'draft',
    ]);

    actingAs($this->admin);

    Livewire::test('admin.report-card.paud.paud-report-create')
        ->call('publishReport', $report->id)
        ->assertHasNoErrors();

    expect($report->fresh()->status)->toBe('published');
    expect($report->fresh()->access_token)->not->toBeNull();
});

test('guest can access published report via token', function () {
    $report = PaudReportCard::create([
        'student_id' => $this->student->id,
        'classroom_id' => $this->classroom->id,
        'academic_year_id' => $this->academicYear->id,
        'semester' => '1',
        'display_mode' => 'cp',
        'status' => 'published',
        'access_token' => 'secure-token-123',
    ]);

    $this->get(route('public.paud-report', ['token' => 'secure-token-123']))
        ->assertOk();
});

test('guest cannot access draft report via token', function () {
    $report = PaudReportCard::create([
        'student_id' => $this->student->id,
        'classroom_id' => $this->classroom->id,
        'academic_year_id' => $this->academicYear->id,
        'semester' => '1',
        'display_mode' => 'cp',
        'status' => 'draft',
        'access_token' => 'secure-token-123',
    ]);

    $this->get(route('public.paud-report', ['token' => 'secure-token-123']))
        ->assertNotFound();
});
