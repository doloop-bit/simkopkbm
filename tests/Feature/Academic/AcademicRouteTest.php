<?php

use App\Models\ExtracurricularActivity;
use App\Models\Level;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\withoutVite;

beforeEach(fn () => withoutVite());

test('admin can access academic years page', function () {
    $user = User::factory()->create(['role' => 'admin']);

    actingAs($user)
        ->get(route('academic.years'))
        ->assertOk()
        ->assertSeeLivewire('admin.academic.academic-years');
});

test('admin can access levels page', function () {
    $user = User::factory()->create(['role' => 'admin']);

    actingAs($user)
        ->get(route('academic.levels'))
        ->assertOk()
        ->assertSeeLivewire('admin.academic.levels');
});

test('admin can access classrooms page', function () {
    $user = User::factory()->create(['role' => 'admin']);

    actingAs($user)
        ->get(route('academic.classrooms'))
        ->assertOk()
        ->assertSeeLivewire('admin.academic.classrooms');
});

test('admin can access subjects page', function () {
    $user = User::factory()->create(['role' => 'admin']);

    actingAs($user)
        ->get(route('academic.subjects'))
        ->assertOk()
        ->assertSeeLivewire('shared.academic.subjects');
});

test('admin can access assignments page', function () {
    $user = User::factory()->create(['role' => 'admin']);

    actingAs($user)
        ->get(route('academic.assignments'))
        ->assertOk()
        ->assertSeeLivewire('admin.academic.teacher-assignments');
});

test('admin can access extracurriculars page with existing activities', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $level = Level::create([
        'name' => 'Paket B',
        'education_level' => 'smp',
        'type' => 'subject_teacher',
    ]);
    ExtracurricularActivity::create([
        'name' => 'Pramuka',
        'instructor' => 'Pak Budi',
        'level_id' => $level->id,
        'is_active' => true,
    ]);

    actingAs($user)
        ->get(route('academic.extracurriculars'))
        ->assertOk()
        ->assertSee('Pramuka');
});

test('non-admin cannot access academic routes', function (string $role) {
    $user = User::factory()->create(['role' => $role]);

    actingAs($user)
        ->get(route('academic.years'))
        ->assertForbidden();
})->with(['siswa', 'guru', 'staf']);
