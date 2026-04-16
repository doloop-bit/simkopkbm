<?php

use App\Models\ModulAjar;
use App\Models\User;
use Livewire\Livewire;

// No class-based imports for anonymous components

use function Pest\Laravel\actingAs;
use function Pest\Laravel\withoutVite;

beforeEach(fn () => withoutVite());

test('teacher can access modul ajar index page', function () {
    $user = User::factory()->create(['role' => 'guru']);

    actingAs($user)
        ->get(route('teacher.modul-ajar.index'))
        ->assertOk()
        ->assertSeeLivewire('teacher.modul-ajar.index');
});

test('admin can access modul ajar index page', function () {
    $user = User::factory()->create(['role' => 'admin']);

    actingAs($user)
        ->get(route('admin.modul-ajar.index'))
        ->assertOk()
        ->assertSeeLivewire('teacher.modul-ajar.index');
});

test('teacher can see their own modules on index page', function () {
    $teacher = User::factory()->create(['role' => 'guru']);
    $otherTeacher = User::factory()->create(['role' => 'guru']);

    $myModule = ModulAjar::create([
        'user_id' => $teacher->id,
        'title' => 'My Lesson Plan',
        'status' => 'completed',
    ]);

    $otherModule = ModulAjar::create([
        'user_id' => $otherTeacher->id,
        'title' => 'Other Lesson Plan',
        'status' => 'completed',
    ]);

    actingAs($teacher);

    Livewire::test('teacher.modul-ajar.index')
        ->assertSee('My Lesson Plan')
        ->assertDontSee('Other Lesson Plan');
});

test('teacher can access create modul ajar page', function () {
    $user = User::factory()->create(['role' => 'guru']);

    actingAs($user)
        ->get(route('teacher.modul-ajar.create'))
        ->assertOk()
        ->assertSeeLivewire('teacher.modul-ajar.create');
});

test('teacher can start a chat and create a record', function () {
    \Illuminate\Support\Facades\Http::fake([
        'generativelanguage.googleapis.com/*' => \Illuminate\Support\Facades\Http::response([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => 'Halo! Saya asisten AI. Ada yang bisa dibantu?'],
                        ],
                    ],
                ],
            ],
        ], 200),
    ]);

    $user = User::factory()->create(['role' => 'guru']);
    actingAs($user);

    Livewire::test('teacher.modul-ajar.create')
        ->set('theme', 'Ekosistem Laut')
        ->set('subject', 'IPAS')
        ->call('startChat')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('modul_ajars', [
        'user_id' => $user->id,
        'title' => 'Ekosistem Laut',
        'subject' => 'IPAS',
    ]);
});

test('teacher can view a module', function () {
    $user = User::factory()->create(['role' => 'guru']);
    $module = ModulAjar::create([
        'user_id' => $user->id,
        'title' => 'Testing Module',
        'generated_content' => '# My Cool Lesson\nContent here',
        'status' => 'completed',
    ]);

    actingAs($user)
        ->get(route('teacher.modul-ajar.show', $module->id))
        ->assertOk()
        ->assertSee('Testing Module')
        ->assertSee('My Cool Lesson');
});

test('teacher cannot view others module', function () {
    $user = User::factory()->create(['role' => 'guru']);
    $otherUser = User::factory()->create(['role' => 'guru']);
    $module = ModulAjar::create([
        'user_id' => $otherUser->id,
        'title' => 'Secret Module',
        'status' => 'completed',
    ]);

    actingAs($user)
        ->get(route('teacher.modul-ajar.show', $module->id))
        ->assertNotFound();
});
