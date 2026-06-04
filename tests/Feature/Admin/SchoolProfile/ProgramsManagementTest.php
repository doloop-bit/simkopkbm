<?php

declare(strict_types=1);

use App\Models\Level;
use App\Models\Program;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

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

test('admin can create a program with custom slug', function () {
    $this->actingAs($this->admin);

    $level = Level::factory()->create(['name' => 'Paket A']);
    $image = UploadedFile::fake()->image('program.jpg', 800, 600);

    Livewire::test('admin.web-content.programs.index')
        ->set('level_id', $level->id)
        ->set('slug', 'paket-a-unggulan')
        ->set('description', 'Program setara SD untuk dewasa')
        ->set('duration', '1 tahun')
        ->set('requirements', 'Usia minimal 15 tahun')
        ->set('image', $image)
        ->set('is_active', true)
        ->call('save')
        ->assertHasNoErrors();

    $program = Program::where('level_id', $level->id)->first();
    expect($program)->not->toBeNull();
    expect($program->slug)->toBe('paket-a-unggulan');
    expect($program->name)->toBe('Paket A');
});

test('slug is auto-generated when level is selected', function () {
    $this->actingAs($this->admin);

    $level = Level::factory()->create(['name' => 'Paket B']);

    Livewire::test('admin.web-content.programs.index')
        ->set('level_id', $level->id)
        ->assertSet('slug', 'paket-b');
});

test('admin can edit a program slug', function () {
    $this->actingAs($this->admin);

    $program = Program::factory()->create([
        'name' => 'Original Name',
        'slug' => 'original-slug',
        'description' => 'Original Description',
    ]);

    Livewire::test('admin.web-content.programs.index')
        ->call('edit', $program->id)
        ->assertSet('slug', 'original-slug')
        ->set('slug', 'updated-slug')
        ->call('save')
        ->assertHasNoErrors();

    $program->refresh();
    expect($program->slug)->toBe('updated-slug');
});

test('program creation requires valid data including slug', function () {
    $this->actingAs($this->admin);

    Livewire::test('admin.web-content.programs.index')
        ->set('level_id', null)
        ->set('slug', '')
        ->set('description', '')
        ->set('duration', '')
        ->call('save')
        ->assertHasErrors(['level_id', 'slug', 'description', 'duration']);
});

test('program slug must be unique', function () {
    $this->actingAs($this->admin);

    Program::factory()->create(['slug' => 'existing-slug']);
    $level = Level::factory()->create();

    Livewire::test('admin.web-content.programs.index')
        ->set('level_id', $level->id)
        ->set('slug', 'existing-slug')
        ->call('save')
        ->assertHasErrors(['slug']);
});

test('program image must be valid image file', function () {
    $this->actingAs($this->admin);

    $level = Level::factory()->create();
    $invalidFile = UploadedFile::fake()->create('document.pdf', 1000);

    Livewire::test('admin.web-content.programs.index')
        ->set('level_id', $level->id)
        ->set('slug', 'test-slug')
        ->set('description', 'Test Description')
        ->set('duration', 'Test Duration')
        ->set('image', $invalidFile)
        ->call('save')
        ->assertHasErrors(['image']);
});

test('cannot create duplicate program for same level', function () {
    $this->actingAs($this->admin);

    $level = Level::factory()->create(['name' => 'PAUD']);
    Program::factory()->forLevel($level)->create(['slug' => 'paud-original']);

    Livewire::test('admin.web-content.programs.index')
        ->set('level_id', $level->id)
        ->set('slug', 'paud-new')
        ->set('description', 'Another program')
        ->set('duration', '1 tahun')
        ->call('save')
        ->assertHasErrors(['level_id']);
});
test('admin can delete a program', function () {
    $this->actingAs($this->admin);

    $image = UploadedFile::fake()->image('program.jpg');
    $program = Program::factory()->create([
        'image_path' => $image->store('programs', 'public'),
    ]);

    Livewire::test('admin.web-content.programs.index')
        ->call('delete', $program->id);

    expect(Program::find($program->id))->toBeNull();
    Storage::disk('public')->assertMissing($program->image_path);
});

test('admin can reorder programs', function () {
    $this->actingAs($this->admin);

    $program1 = Program::factory()->create(['order' => 1]);
    $program2 = Program::factory()->create(['order' => 2]);

    Livewire::test('admin.web-content.programs.index')
        ->call('moveDown', $program1->id);

    $program1->refresh();
    $program2->refresh();

    expect($program1->order)->toBe(2);
    expect($program2->order)->toBe(1);
});
