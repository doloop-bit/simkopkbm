<?php

declare(strict_types=1);

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Subject;
use App\Models\TeacherAssignment;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->withoutVite();
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->academicYear = AcademicYear::factory()->create(['is_active' => true]);
    $this->classroom = Classroom::factory()->create(['academic_year_id' => $this->academicYear->id]);
    $this->teacher = User::factory()->create(['role' => 'guru']);
    $this->subject = Subject::factory()->create();
    
    actingAs($this->admin);
});

it('can render', function () {
    Livewire::test('admin.academic.teacher-assignments')
        ->assertSee('Penugasan Guru')
        ->assertSee('Tahun Ajaran')
        ->assertSee('Semua Kelas');
});

it('can create a teacher assignment', function () {
    Livewire::test('admin.academic.teacher-assignments')
        ->set('academic_year_id', $this->academicYear->id)
        ->set('classroom_id', $this->classroom->id)
        ->set('teacher_id', $this->teacher->id)
        ->set('subject_id', $this->subject->id)
        ->set('type', 'subject_teacher')
        ->call('save')
        ->assertHasNoErrors();

    expect(TeacherAssignment::count())->toBe(1);
    $assignment = TeacherAssignment::first();
    expect($assignment->teacher_id)->toBe($this->teacher->id);
    expect($assignment->classroom_id)->toBe($this->classroom->id);
    expect($assignment->subject_id)->toBe($this->subject->id);
});

it('validates required fields', function () {
    Livewire::test('admin.academic.teacher-assignments')
        ->set('academic_year_id', null)
        ->set('classroom_id', null)
        ->set('teacher_id', null)
        ->call('save')
        ->assertHasErrors(['academic_year_id', 'classroom_id', 'teacher_id']);
});

it('can edit a teacher assignment', function () {
    $assignment = TeacherAssignment::factory()->create([
        'academic_year_id' => $this->academicYear->id,
        'classroom_id' => $this->classroom->id,
        'teacher_id' => $this->teacher->id,
        'type' => 'class_teacher'
    ]);

    $newTeacher = User::factory()->create(['role' => 'guru']);

    Livewire::test('admin.academic.teacher-assignments')
        ->call('edit', $assignment->id)
        ->set('teacher_id', $newTeacher->id)
        ->call('save')
        ->assertHasNoErrors();

    expect($assignment->refresh()->teacher_id)->toBe($newTeacher->id);
});

it('can delete a teacher assignment', function () {
    $assignment = TeacherAssignment::factory()->create([
        'academic_year_id' => $this->academicYear->id,
        'classroom_id' => $this->classroom->id,
        'teacher_id' => $this->teacher->id,
    ]);

    Livewire::test('admin.academic.teacher-assignments')
        ->call('delete', $assignment->id)
        ->assertHasNoErrors();

    expect(TeacherAssignment::find($assignment->id))->toBeNull();
});
