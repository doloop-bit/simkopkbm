<?php

declare(strict_types=1);

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Level;
use App\Models\Subject;
use App\Models\TeacherAssignment;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->withoutVite();
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->level = Level::create([
        'name' => 'Paket A',
        'education_level' => 'sd',
        'type' => 'class_teacher',
        'phase_map' => ['1' => 'A', '2' => 'A'],
    ]);
    $this->sourceYear = AcademicYear::create([
        'name' => '2025/2026',
        'start_date' => '2025-07-01',
        'end_date' => '2026-06-30',
        'is_active' => true,
        'status' => 'open',
    ]);
    $this->teacher = User::factory()->create(['role' => 'guru']);
    $this->subject = Subject::factory()->create();

    $this->sourceClassroom = Classroom::create([
        'academic_year_id' => $this->sourceYear->id,
        'level_id' => $this->level->id,
        'name' => 'Kelas 1 - Paket A',
        'class_level' => 1,
        'homeroom_teacher_id' => $this->teacher->id,
    ]);

    TeacherAssignment::create([
        'academic_year_id' => $this->sourceYear->id,
        'classroom_id' => $this->sourceClassroom->id,
        'teacher_id' => $this->teacher->id,
        'subject_id' => $this->subject->id,
        'type' => 'subject_teacher',
    ]);

    actingAs($this->admin);
});

it('auto fills next academic year name and dates when opening createNew modal', function () {
    Livewire::test('admin.academic.academic-years')
        ->call('createNew')
        ->assertSet('name', '2026/2027')
        ->assertSet('start_date', '2026-07-01')
        ->assertSet('end_date', '2027-06-30')
        ->assertSet('copy_classrooms', true)
        ->assertSet('copy_from_year_id', $this->sourceYear->id);
});

it('auto updates start_date and end_date when typing academic year name', function () {
    Livewire::test('admin.academic.academic-years')
        ->call('createNew')
        ->set('name', '2028/2029')
        ->assertSet('start_date', '2028-07-01')
        ->assertSet('end_date', '2029-06-30');
});

it('can create a new academic year and automatically copy classrooms and assignments', function () {
    Livewire::test('admin.academic.academic-years')
        ->call('createNew')
        ->set('is_active', true)
        ->set('copy_classrooms', true)
        ->set('copy_homeroom', true)
        ->set('copy_assignments', true)
        ->call('save')
        ->assertHasNoErrors();

    $newYear = AcademicYear::where('name', '2026/2027')->first();
    expect($newYear)->not->toBeNull()
        ->and($newYear->is_active)->toBeTrue();

    $copiedClassroom = Classroom::where('academic_year_id', $newYear->id)->first();
    expect($copiedClassroom)->not->toBeNull()
        ->and($copiedClassroom->name)->toBe('Kelas 1 - Paket A')
        ->and($copiedClassroom->level_id)->toBe($this->level->id)
        ->and($copiedClassroom->class_level)->toBe(1)
        ->and($copiedClassroom->homeroom_teacher_id)->toBe($this->teacher->id);

    $copiedAssignment = TeacherAssignment::where('academic_year_id', $newYear->id)
        ->where('classroom_id', $copiedClassroom->id)
        ->first();

    expect($copiedAssignment)->not->toBeNull()
        ->and($copiedAssignment->teacher_id)->toBe($this->teacher->id)
        ->and($copiedAssignment->subject_id)->toBe($this->subject->id);
});

it('can copy classrooms from classrooms management modal without duplicates', function () {
    $targetYear = AcademicYear::create([
        'name' => '2026/2027',
        'start_date' => '2026-07-01',
        'end_date' => '2027-06-30',
        'is_active' => false,
        'status' => 'open',
    ]);

    Livewire::test('admin.academic.classrooms')
        ->call('openCopyModal')
        ->set('copy_source_year_id', $this->sourceYear->id)
        ->set('copy_target_year_id', $targetYear->id)
        ->set('copy_homeroom', false)
        ->set('copy_assignments', false)
        ->call('copyClassrooms')
        ->assertHasNoErrors();

    $copiedClassrooms = Classroom::where('academic_year_id', $targetYear->id)->get();
    expect($copiedClassrooms)->toHaveCount(1)
        ->and($copiedClassrooms->first()->homeroom_teacher_id)->toBeNull();

    // Run copy again to verify idempotency (no duplicate classrooms)
    Livewire::test('admin.academic.classrooms')
        ->set('copy_source_year_id', $this->sourceYear->id)
        ->set('copy_target_year_id', $targetYear->id)
        ->call('copyClassrooms')
        ->assertHasNoErrors();

    expect(Classroom::where('academic_year_id', $targetYear->id)->count())->toBe(1);
});

it('can view classroom detail modal with students and teachers', function () {
    $student = User::factory()->siswa()->create(['name' => 'Siswa Teladan']);
    $student->studentProfile->update(['classroom_id' => $this->sourceClassroom->id]);

    Livewire::test('admin.academic.classrooms')
        ->call('viewDetail', $this->sourceClassroom->id)
        ->assertSet('detailModal', true)
        ->assertSee('Kelas 1 - Paket A')
        ->assertSee($this->teacher->name)
        ->assertSee($this->subject->name)
        ->assertSee('Siswa Teladan');
});

it('only shows classrooms from active academic year in students index filter', function () {
    $oldYear = AcademicYear::create([
        'name' => '2024/2025',
        'start_date' => '2024-07-01',
        'end_date' => '2025-06-30',
        'is_active' => false,
        'status' => 'closed',
    ]);

    $oldClassroom = Classroom::create([
        'academic_year_id' => $oldYear->id,
        'level_id' => $this->level->id,
        'name' => 'Kelas Lama 2024',
        'class_level' => 1,
    ]);

    $component = Livewire::test('admin.data-master.students.index');
    $filterClassrooms = $component->viewData('filter_classrooms');

    expect($filterClassrooms->pluck('id'))->toContain($this->sourceClassroom->id)
        ->and($filterClassrooms->pluck('id'))->not->toContain($oldClassroom->id);
});

it('orders classrooms by class_level numerically so Kelas 2 comes before Kelas 10', function () {
    $class10 = Classroom::create([
        'academic_year_id' => $this->sourceYear->id,
        'level_id' => $this->level->id,
        'name' => 'Kelas 10 - Paket C',
        'class_level' => 10,
    ]);

    $class2 = Classroom::create([
        'academic_year_id' => $this->sourceYear->id,
        'level_id' => $this->level->id,
        'name' => 'Kelas 2 - Paket A',
        'class_level' => 2,
    ]);

    $component = Livewire::test('admin.academic.classrooms');
    $ids = $component->viewData('classrooms')->pluck('id')->values()->all();

    expect($ids)->toBe([
        $this->sourceClassroom->id, // class_level 1
        $class2->id,                // class_level 2
        $class10->id,               // class_level 10
    ]);
});
