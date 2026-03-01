<?php

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Level;
use App\Models\Profile;
use App\Models\StudentProfile;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

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

    // Student without a classroom
    $user1 = User::factory()->create(['role' => 'siswa']);
    $profile1 = StudentProfile::factory()->create(['classroom_id' => null]);
    Profile::create([
        'user_id' => $user1->id,
        'profileable_type' => StudentProfile::class,
        'profileable_id' => $profile1->id,
    ]);
    $this->unassignedStudent = $profile1;

    // Student in classroom1
    $user2 = User::factory()->create(['role' => 'siswa']);
    $profile2 = StudentProfile::factory()->create(['classroom_id' => $this->classroom1->id]);
    Profile::create([
        'user_id' => $user2->id,
        'profileable_type' => StudentProfile::class,
        'profileable_id' => $profile2->id,
    ]);
    $this->assignedStudent = $profile2;
});

it('renders the class placement page for admin', function () {
    actingAs($this->admin)
        ->get(route('students.class-placement'))
        ->assertOk()
        ->assertSee('Penempatan Kelas')
        ->assertSeeLivewire('admin.academic.class-placement');
});

it('redirects guests away from class placement', function () {
    $this->get(route('students.class-placement'))
        ->assertRedirect(route('login'));
});

// ── Placement (moveStudentsFromAlpine) ────────────────────────────────────

it('moves students between classrooms', function () {
    actingAs($this->admin);

    Livewire::test('admin.academic.class-placement')
        ->set('academic_year_id', $this->year->id)
        ->set('level_id', $this->level->id)
        ->set('source_classroom_id', (string) $this->classroom1->id)
        ->set('target_classroom_id', (string) $this->classroom2->id)
        ->call('moveStudentsFromAlpine', [$this->assignedStudent->id], (string) $this->classroom2->id);

    expect($this->assignedStudent->fresh()->classroom_id)->toBe($this->classroom2->id);
});

it('unassigns students when target is "unassigned"', function () {
    actingAs($this->admin);

    Livewire::test('admin.academic.class-placement')
        ->set('source_classroom_id', (string) $this->classroom1->id)
        ->call('moveStudentsFromAlpine', [$this->assignedStudent->id], 'unassigned');

    expect($this->assignedStudent->fresh()->classroom_id)->toBeNull();
});

it('aborts move when no target is provided', function () {
    actingAs($this->admin);

    Livewire::test('admin.academic.class-placement')
        ->set('source_classroom_id', (string) $this->classroom1->id)
        ->call('moveStudentsFromAlpine', [$this->assignedStudent->id], null);

    expect($this->assignedStudent->fresh()->classroom_id)->toBe($this->classroom1->id);
});

// ── Promotion ─────────────────────────────────────────────────────────────

it('promotes students to a new classroom in a new year', function () {
    actingAs($this->admin);

    Livewire::test('admin.academic.class-placement')
        ->set('promo_source_classroom_id', (string) $this->classroom1->id)
        ->set('promo_target_classroom_id', (string) $this->newClassroom->id)
        ->call('promoteStudents', [$this->assignedStudent->id]);

    expect($this->assignedStudent->fresh()->classroom_id)->toBe($this->newClassroom->id);
});

it('sets status to naik_kelas after promotion', function () {
    actingAs($this->admin);

    Livewire::test('admin.academic.class-placement')
        ->set('promo_source_classroom_id', (string) $this->classroom1->id)
        ->set('promo_target_classroom_id', (string) $this->newClassroom->id)
        ->call('promoteStudents', [$this->assignedStudent->id]);

    expect($this->assignedStudent->fresh()->status)->toBe('naik_kelas');
});

it('aborts promotion when no target classroom is set', function () {
    actingAs($this->admin);

    Livewire::test('admin.academic.class-placement')
        ->set('promo_source_classroom_id', (string) $this->classroom1->id)
        ->call('promoteStudents', [$this->assignedStudent->id]);

    expect($this->assignedStudent->fresh()->classroom_id)->toBe($this->classroom1->id);
});

// ── Graduation ────────────────────────────────────────────────────────────

it('graduates students and nullifies their classroom', function () {
    actingAs($this->admin);

    Livewire::test('admin.academic.class-placement')
        ->set('grad_classroom_id', (string) $this->classroom1->id)
        ->call('graduateStudents', [$this->assignedStudent->id]);

    expect($this->assignedStudent->fresh()->classroom_id)->toBeNull();
});

it('sets status to lulus after graduation', function () {
    actingAs($this->admin);

    Livewire::test('admin.academic.class-placement')
        ->set('grad_classroom_id', (string) $this->classroom1->id)
        ->call('graduateStudents', [$this->assignedStudent->id]);

    expect($this->assignedStudent->fresh()->status)->toBe('lulus');
});

it('aborts graduation when no students are selected', function () {
    actingAs($this->admin);

    Livewire::test('admin.academic.class-placement')
        ->set('grad_classroom_id', (string) $this->classroom1->id)
        ->call('graduateStudents', []);

    expect($this->assignedStudent->fresh()->classroom_id)->toBe($this->classroom1->id);
    expect($this->assignedStudent->fresh()->status)->not->toBe('lulus');
});
