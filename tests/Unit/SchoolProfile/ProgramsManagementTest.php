<?php

declare(strict_types=1);

use App\Models\Level;
use App\Models\Program;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;

beforeEach(function () {
    $this->withoutVite();
    Storage::fake('public');
    $this->admin = User::factory()->create(['role' => 'admin']);
});

test('admin can access programs management page', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.programs.index'))
        ->assertOk()
        ->assertSeeLivewire('admin.web-content.programs.index');
});

test('non-admin cannot access programs management page', function () {
    $user = User::factory()->create(['role' => 'teacher']);

    $this->actingAs($user)
        ->get(route('admin.programs.index'))
        ->assertForbidden();
});

test('admin can create a program', function () {
    $this->actingAs($this->admin);

    $level = Level::factory()->create(['name' => 'Paket A']);
    $image = UploadedFile::fake()->image('program.jpg', 800, 600);

    Volt::test('admin.web-content.programs.index')
        ->set('level_id', $level->id)
        ->set('description', 'Program setara SD untuk dewasa')
        ->set('duration', '1 tahun')
        ->set('requirements', 'Usia minimal 15 tahun')
        ->set('image', $image)
        ->set('is_active', true)
        ->call('save')
        ->assertHasNoErrors();

    $program = Program::where('level_id', $level->id)->first();
    expect($program)->not->toBeNull();
    expect($program->name)->toBe('Paket A');
    expect($program->description)->toBe('Program setara SD untuk dewasa');
    expect($program->duration)->toBe('1 tahun');
    expect($program->requirements)->toBe('Usia minimal 15 tahun');
    expect($program->is_active)->toBeTrue();
    expect($program->image_path)->not->toBeNull();

    Storage::disk('public')->assertExists($program->image_path);
});

test('admin can edit a program', function () {
    $this->actingAs($this->admin);

    $program = Program::factory()->create([
        'name' => 'Original Name',
        'description' => 'Original Description',
    ]);

    Volt::test('admin.web-content.programs.index')
        ->call('edit', $program->id)
        ->set('description', 'Updated Description')
        ->call('save')
        ->assertHasNoErrors();

    $program->refresh();
    expect($program->description)->toBe('Updated Description');
});

test('admin can delete a program', function () {
    $this->actingAs($this->admin);

    $image = UploadedFile::fake()->image('program.jpg');
    $program = Program::factory()->create([
        'image_path' => $image->store('programs', 'public'),
    ]);

    Volt::test('admin.web-content.programs.index')
        ->call('delete', $program->id);

    expect(Program::find($program->id))->toBeNull();
    Storage::disk('public')->assertMissing($program->image_path);
});

test('admin can reorder programs', function () {
    $this->actingAs($this->admin);

    $program1 = Program::factory()->create(['order' => 1]);
    $program2 = Program::factory()->create(['order' => 2]);

    Volt::test('admin.web-content.programs.index')
        ->call('moveDown', $program1->id);

    $program1->refresh();
    $program2->refresh();

    expect($program1->order)->toBe(2);
    expect($program2->order)->toBe(1);
});

test('program creation requires valid data', function () {
    $this->actingAs($this->admin);

    Volt::test('admin.web-content.programs.index')
        ->set('level_id', null)
        ->set('description', '')
        ->set('duration', '')
        ->call('save')
        ->assertHasErrors(['level_id', 'description', 'duration']);
});

test('program image must be valid image file', function () {
    $this->actingAs($this->admin);

    $level = Level::factory()->create();
    $invalidFile = UploadedFile::fake()->create('document.pdf', 1000);

    Volt::test('admin.web-content.programs.index')
        ->set('level_id', $level->id)
        ->set('description', 'Test Description')
        ->set('duration', 'Test Duration')
        ->set('image', $invalidFile)
        ->call('save')
        ->assertHasErrors(['image']);
});

test('cannot create duplicate program for same level', function () {
    $this->actingAs($this->admin);

    $level = Level::factory()->create(['name' => 'PAUD']);
    Program::factory()->forLevel($level)->create();

    Volt::test('admin.web-content.programs.index')
        ->set('level_id', $level->id)
        ->set('description', 'Another program')
        ->set('duration', '1 tahun')
        ->call('save')
        ->assertHasErrors(['level_id']);
});
