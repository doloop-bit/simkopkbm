<?php

declare(strict_types=1);

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Level;
use App\Models\Profile;
use App\Models\StudentProfile;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->withoutVite();
    $this->admin = User::factory()->create(['role' => 'admin']);

    $this->year = AcademicYear::factory()->create(['is_active' => true]);
    $this->newYear = AcademicYear::factory()->create(['is_active' => false]);
    $this->level = Level::factory()->create();

    $this->classroom1 = Classroom::factory()->create([
        'academic_year_id' => $this->year->id,
        'level_id' => $this->level->id,
    ]);

    $this->classroom2 = Classroom::factory()->create([
        'academic_year_id' => $this->year->id,
        'level_id' => $this->level->id,
    ]);

    $this->newClassroom = Classroom::factory()->create([
        'academic_year_id' => $this->newYear->id,
        'level_id' => $this->level->id,
    ]);

    // Create a student without a classroom
    $user1 = User::factory()->create(['role' => 'siswa']);
    $profile1 = StudentProfile::factory()->create(['classroom_id' => null]);
    Profile::create([
        'user_id' => $user1->id,
        'profileable_type' => StudentProfile::class,
        'profileable_id' => $profile1->id,
    ]);
    $this->unassignedStudent = $profile1;

    // Create a student in classroom1
    $user2 = User::factory()->create(['role' => 'siswa']);
    $profile2 = StudentProfile::factory()->create(['classroom_id' => $this->classroom1->id]);
    Profile::create([
        'user_id' => $user2->id,
        'profileable_type' => StudentProfile::class,
        'profileable_id' => $profile2->id,
    ]);
    $this->assignedStudent = $profile2;
});

it('renders the class placement component for admin', function () {
    $this->actingAs($this->admin)
        ->get(route('students.class-placement'))
        ->assertOk()
        ->assertSee('Penempatan Kelas');
});

it('loads unassigned students when unassigned is selected', function () {
    Livewire::actingAs($this->admin)
        ->test('admin.academic.class-placement')
        ->set('academic_year_id', $this->year->id)
        ->set('level_id', $this->level->id)
        ->set('source_classroom_id', 'unassigned')
        ->assertSee($this->unassignedStudent->profile->user->name)
        ->assertDontSee($this->assignedStudent->profile->user->name);
});

it('loads assigned students when classroom is selected', function () {
    Livewire::actingAs($this->admin)
        ->test('admin.academic.class-placement')
        ->set('academic_year_id', $this->year->id)
        ->set('level_id', $this->level->id)
        ->set('source_classroom_id', (string) $this->classroom1->id)
        ->assertSee($this->assignedStudent->profile->user->name)
        ->assertDontSee($this->unassignedStudent->profile->user->name);
});

it('moves students between classrooms', function () {
    Livewire::actingAs($this->admin)
        ->test('admin.academic.class-placement')
        ->set('academic_year_id', $this->year->id)
        ->set('level_id', $this->level->id)
        ->set('source_classroom_id', (string) $this->classroom1->id)
        ->set('target_classroom_id', (string) $this->classroom2->id)
        ->call('moveStudentsFromAlpine', [$this->assignedStudent->id], (string) $this->classroom2->id);

    expect($this->assignedStudent->fresh()->classroom_id)->toBe($this->classroom2->id);
});

it('unassigns students if target is unassigned', function () {
    Livewire::actingAs($this->admin)
        ->test('admin.academic.class-placement')
        ->set('academic_year_id', $this->year->id)
        ->set('level_id', $this->level->id)
        ->set('source_classroom_id', (string) $this->classroom1->id)
        ->call('moveStudentsFromAlpine', [$this->assignedStudent->id], 'unassigned');

    expect($this->assignedStudent->fresh()->classroom_id)->toBeNull();
});

it('promotes students to a new classroom in a new year', function () {
    Livewire::actingAs($this->admin)
        ->test('admin.academic.class-placement')
        ->set('promo_source_year_id', $this->year->id)
        ->set('promo_source_level_id', $this->level->id)
        ->set('promo_source_classroom_id', (string) $this->classroom1->id)
        ->set('promo_target_year_id', $this->newYear->id)
        ->set('promo_target_level_id', $this->level->id)
        ->set('promo_target_classroom_id', (string) $this->newClassroom->id)
        ->call('promoteStudents', [$this->assignedStudent->id]);

    expect($this->assignedStudent->fresh()->classroom_id)->toBe($this->newClassroom->id)
        ->and($this->assignedStudent->fresh()->status)->toBe('naik_kelas');
});

it('graduates students and nullifies classroom', function () {
    Livewire::actingAs($this->admin)
        ->test('admin.academic.class-placement')
        ->set('grad_year_id', $this->year->id)
        ->set('grad_level_id', $this->level->id)
        ->set('grad_classroom_id', (string) $this->classroom1->id)
        ->call('graduateStudents', [$this->assignedStudent->id]);

    expect($this->assignedStudent->fresh()->classroom_id)->toBeNull()
        ->and($this->assignedStudent->fresh()->status)->toBe('lulus');
});
