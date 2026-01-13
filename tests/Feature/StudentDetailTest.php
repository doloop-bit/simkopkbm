<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\StudentProfile;
use App\Models\Classroom;
use Livewire\Volt\Volt;

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

    $student->profiles()->create([
        'profileable_id' => $studentProfile->id,
        'profileable_type' => StudentProfile::class,
    ]);

    Volt::test('students.index')
        ->actingAs($admin)
        ->assertSee('Test Student')
        ->call('viewDetails', $student->id)
        ->assertDispatched('open-modal', 'detail-modal')
        ->assertSet('viewing.id', $student->id);
});

test('edit button opens student modal correctly', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $student = User::factory()->create(['role' => 'siswa']);

    $studentProfile = StudentProfile::factory()->create();

    $student->profiles()->create([
        'profileable_id' => $studentProfile->id,
        'profileable_type' => StudentProfile::class,
    ]);

    Volt::test('students.index')
        ->actingAs($admin)
        ->call('edit', $student->id)
        ->assertDispatched('open-modal', 'student-modal')
        ->assertSet('editing.id', $student->id)
        ->assertSet('name', $student->name)
        ->assertSet('email', $student->email);
});

test('periodic button opens periodic modal correctly', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $student = User::factory()->create(['role' => 'siswa']);

    $studentProfile = StudentProfile::factory()->create();

    $student->profiles()->create([
        'profileable_id' => $studentProfile->id,
        'profileable_type' => StudentProfile::class,
    ]);

    Volt::test('students.index')
        ->actingAs($admin)
        ->call('openPeriodic', $student->id)
        ->assertDispatched('open-modal', 'periodic-modal')
        ->assertSet('editing.id', $student->id);
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

    $student->profiles()->create([
        'profileable_id' => $studentProfile->id,
        'profileable_type' => StudentProfile::class,
    ]);

    Volt::test('students.index')
        ->actingAs($admin)
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

    $student->profiles()->create([
        'profileable_id' => $studentProfile->id,
        'profileable_type' => StudentProfile::class,
    ]);

    $component = Volt::test('students.index')
        ->actingAs($admin);

    // Open detail modal
    $component->call('viewDetails', $student->id)
        ->assertSet('viewing.id', $student->id)
        ->assertSet('editing', null);

    // Open edit modal
    $component->call('edit', $student->id)
        ->assertSet('editing.id', $student->id);

    // Open periodic modal
    $component->call('openPeriodic', $student->id)
        ->assertSet('editing.id', $student->id);
});
