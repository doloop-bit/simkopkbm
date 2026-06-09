<?php

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\PaudTp;
use App\Models\PaudTpAssessment;
use App\Models\Profile;
use App\Models\StudentProfile;
use App\Models\User;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('unauthorized users cannot access PAUD TP grading', function () {
    $user = User::factory()->create(['role' => 'siswa']);
    $this->actingAs($user);

    Livewire::test('admin.report-card.paud.paud-tp-grading')
        ->assertStatus(403);
});

test('admin can input grades for student per TP', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    $classroom = Classroom::factory()->create();
    $academicYear = AcademicYear::factory()->create();
    $tp = PaudTp::factory()->create([
        'classroom_id' => $classroom->id,
        'academic_year_id' => $academicYear->id,
        'semester' => '1',
    ]);

    // Create a student and assign profile to classroom
    $student = User::factory()->create(['role' => 'siswa']);
    $studentProfile = StudentProfile::factory()->create([
        'classroom_id' => $classroom->id,
    ]);
    Profile::factory()->create([
        'user_id' => $student->id,
        'profileable_id' => $studentProfile->id,
        'profileable_type' => StudentProfile::class,
    ]);

    // Verify initial state
    expect(PaudTpAssessment::count())->toBe(0);

    // Livewire component test
    Livewire::test('admin.report-card.paud.paud-tp-grading')
        ->set('academic_year_id', $academicYear->id)
        ->set('classroom_id', $classroom->id)
        ->set('semester', '1')
        ->set('paud_tp_id', $tp->id)
        ->set('grades.'.$student->id, [
            'level' => 'BSB',
            'notes' => 'Catatan kemajuan anak.',
        ])
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee('Penilaian Tujuan Pembelajaran PAUD berhasil disimpan');

    expect(PaudTpAssessment::count())->toBe(1);
    $assessment = PaudTpAssessment::first();
    expect($assessment->level)->toBe('BSB');
    expect($assessment->notes)->toBe('Catatan kemajuan anak.');
    expect($assessment->student_id)->toBe($student->id);
    expect($assessment->paud_tp_id)->toBe($tp->id);
});
