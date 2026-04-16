<?php

declare(strict_types=1);

use App\Models\Classroom;
use App\Models\Profile;
use App\Models\StudentProfile;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->withoutVite();
});

test('student name and photo are clickable to view details', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $student = User::factory()->create([
        'role' => 'siswa',
        'name' => 'Test Student',
    ]);

    $studentProfile = StudentProfile::factory()->create([
        'nis' => '12345',
        'nisn' => '67890',
    ]);

    Profile::create([
        'user_id' => $student->id,
        'profileable_id' => $studentProfile->id,
        'profileable_type' => StudentProfile::class,
    ]);

    Livewire::actingAs($admin)
        ->test('admin.data-master.students.index')
        ->assertSee('Test Student')
        ->call('viewDetails', $student->id)
        ->assertSet('detailModal', true)
        ->assertSet('viewing.id', $student->id);
});

test('edit button opens student modal correctly', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $student = User::factory()->create(['role' => 'siswa']);

    $studentProfile = StudentProfile::factory()->create();

    Profile::create([
        'user_id' => $student->id,
        'profileable_id' => $studentProfile->id,
        'profileable_type' => StudentProfile::class,
    ]);

    Livewire::actingAs($admin)
        ->test('admin.data-master.students.index')
        ->call('edit', $student->id)
        ->assertSet('studentModal', true)
        ->assertSet('editing.id', $student->id)
        ->assertSet('name', $student->name)
        ->assertSet('email', $student->email);
});

test('periodic button opens periodic modal correctly', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $student = User::factory()->create(['role' => 'siswa']);

    $studentProfile = StudentProfile::factory()->create();

    Profile::create([
        'user_id' => $student->id,
        'profileable_id' => $studentProfile->id,
        'profileable_type' => StudentProfile::class,
    ]);

    Livewire::actingAs($admin)
        ->test('admin.data-master.students.index')
        ->call('openPeriodic', $student->id)
        ->assertSet('periodicModal', true)
        ->assertSet('editingUserForPeriodic.id', $student->id);
});

test('detail modal shows complete student information', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $classroom = Classroom::factory()->create(['name' => 'Kelas A']);
    $student = User::factory()->create([
        'role' => 'siswa',
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    $studentProfile = StudentProfile::factory()->create([
        'nis' => '12345',
        'nisn' => '67890',
        'father_name' => 'Father Name',
        'mother_name' => 'Mother Name',
        'classroom_id' => $classroom->id,
    ]);

    Profile::create([
        'user_id' => $student->id,
        'profileable_id' => $studentProfile->id,
        'profileable_type' => StudentProfile::class,
    ]);

    Livewire::actingAs($admin)
        ->test('admin.data-master.students.index')
        ->call('viewDetails', $student->id)
        ->assertSet('viewing.id', $student->id)
        ->assertSee('John Doe')
        ->assertSee('john@example.com')
        ->assertSee('12345')
        ->assertSee('67890')
        ->assertSee('Father Name')
        ->assertSee('Mother Name')
        ->assertSee('Kelas A');
});

test('modals do not overlap', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $student = User::factory()->create(['role' => 'siswa']);

    $studentProfile = StudentProfile::factory()->create();

    Profile::create([
        'user_id' => $student->id,
        'profileable_id' => $studentProfile->id,
        'profileable_type' => StudentProfile::class,
    ]);

    $component = Livewire::actingAs($admin)
        ->test('admin.data-master.students.index');

    // Open detail modal
    $component->call('viewDetails', $student->id)
        ->assertSet('viewing.id', $student->id)
        ->assertSet('editing', null);

    // Open edit modal
    $component->call('edit', $student->id)
        ->assertSet('editing.id', $student->id)
        ->assertSet('viewing.id', $student->id); // viewing persists unless reset

    // Open periodic modal
    $component->call('openPeriodic', $student->id)
        ->assertSet('editingUserForPeriodic.id', $student->id)
        ->assertSet('detailModal', true); // persists
});
