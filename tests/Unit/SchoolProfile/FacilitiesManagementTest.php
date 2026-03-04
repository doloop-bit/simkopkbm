<?php

declare(strict_types=1);

use App\Models\Facility;
use App\Models\SchoolProfile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->withoutVite();
    Storage::fake('public');

    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->profile = SchoolProfile::factory()->create(['is_active' => true]);
});

test('admin can access facilities management page', function () {
    actingAs($this->admin)
        ->get(route('admin.school-profile.facilities'))
        ->assertOk()
        ->assertSeeLivewire('admin.school-profile.facilities');
});

test('non-admin cannot access facilities management page', function () {
    $user = User::factory()->create(['role' => 'teacher']);

    actingAs($user)
        ->get(route('admin.school-profile.facilities'))
        ->assertForbidden();
});

test('guest cannot access facilities management page', function () {
    $this->get(route('admin.school-profile.facilities'))
        ->assertRedirect(route('login'));
});

test('facilities management page displays existing facilities', function () {
    $facility1 = Facility::factory()->create([
        'school_profile_id' => $this->profile->id,
        'name' => 'Perpustakaan',
        'order' => 1,
    ]);

    $facility2 = Facility::factory()->create([
        'school_profile_id' => $this->profile->id,
        'name' => 'Laboratorium Komputer',
        'order' => 2,
    ]);

    actingAs($this->admin)
        ->get(route('admin.school-profile.facilities'))
        ->assertSee('Perpustakaan')
        ->assertSee('Laboratorium Komputer');
});

test('admin can create new facility without image', function () {
    Volt::actingAs($this->admin)
        ->test('admin.school-profile.facilities')
        ->call('showAddForm')
        ->set('name', 'Ruang Kelas')
        ->set('description', 'Ruang kelas yang nyaman dan ber-AC')
        ->call('save')
        ->assertHasNoErrors();

    expect(Facility::where('name', 'Ruang Kelas')->exists())->toBeTrue();

    $facility = Facility::where('name', 'Ruang Kelas')->first();
    expect($facility->description)->toBe('Ruang kelas yang nyaman dan ber-AC');
    expect($facility->school_profile_id)->toBe($this->profile->id);
    expect($facility->order)->toBe(1);
});

test('admin can create new facility with image', function () {
    $image = UploadedFile::fake()->image('facility.jpg', 800, 600);

    Volt::actingAs($this->admin)
        ->test('admin.school-profile.facilities')
        ->call('showAddForm')
        ->set('name', 'Perpustakaan')
        ->set('description', 'Perpustakaan dengan koleksi lengkap')
        ->set('image', $image)
        ->call('save')
        ->assertHasNoErrors();

    expect(Facility::where('name', 'Perpustakaan')->exists())->toBeTrue();

    $facility = Facility::where('name', 'Perpustakaan')->first();
    expect($facility->image_path)->not->toBeNull();
    Storage::disk('public')->assertExists($facility->image_path);
});

test('facility name is required', function () {
    Volt::actingAs($this->admin)
        ->test('admin.school-profile.facilities')
        ->call('showAddForm')
        ->set('name', '')
        ->set('description', 'Test description')
        ->call('save')
        ->assertHasErrors(['name']);

    expect(Facility::count())->toBe(0);
});

test('facility image must be valid image file', function () {
    $file = UploadedFile::fake()->create('document.pdf', 1000);

    Volt::actingAs($this->admin)
        ->test('admin.school-profile.facilities')
        ->call('showAddForm')
        ->set('name', 'Test Facility')
        ->set('image', $file)
        ->call('save')
        ->assertHasErrors(['image']);
});

test('facility image must not exceed 5MB', function () {
    $image = UploadedFile::fake()->image('large.jpg')->size(6000); // 6MB

    Volt::actingAs($this->admin)
        ->test('admin.school-profile.facilities')
        ->call('showAddForm')
        ->set('name', 'Test Facility')
        ->set('image', $image)
        ->call('save')
        ->assertHasErrors(['image']);
});

test('admin can edit existing facility', function () {
    $facility = Facility::factory()->create([
        'school_profile_id' => $this->profile->id,
        'name' => 'Old Name',
        'description' => 'Old description',
        'order' => 1,
    ]);

    Volt::actingAs($this->admin)
        ->test('admin.school-profile.facilities')
        ->call('edit', $facility->id)
        ->set('name', 'Updated Name')
        ->set('description', 'Updated description')
        ->call('save')
        ->assertHasNoErrors();

    $facility->refresh();
    expect($facility->name)->toBe('Updated Name');
    expect($facility->description)->toBe('Updated description');
});

test('admin can update facility image', function () {
    $oldImage = UploadedFile::fake()->image('old.jpg');
    $oldPath = $oldImage->store('facilities', 'public');

    $facility = Facility::factory()->create([
        'school_profile_id' => $this->profile->id,
        'name' => 'Test Facility',
        'image_path' => $oldPath,
        'order' => 1,
    ]);

    $newImage = UploadedFile::fake()->image('new.jpg');

    Volt::actingAs($this->admin)
        ->test('admin.school-profile.facilities')
        ->call('edit', $facility->id)
        ->set('image', $newImage)
        ->call('save')
        ->assertHasNoErrors();

    $facility->refresh();
    expect($facility->image_path)->not->toBe($oldPath);
    Storage::disk('public')->assertExists($facility->image_path);
    Storage::disk('public')->assertMissing($oldPath);
});

test('admin can remove facility image', function () {
    $image = UploadedFile::fake()->image('facility.jpg');
    $path = $image->store('facilities', 'public');

    $facility = Facility::factory()->create([
        'school_profile_id' => $this->profile->id,
        'name' => 'Test Facility',
        'image_path' => $path,
        'order' => 1,
    ]);

    Volt::actingAs($this->admin)
        ->test('admin.school-profile.facilities')
        ->call('edit', $facility->id)
        ->call('removeImage');

    $facility->refresh();
    expect($facility->image_path)->toBeNull();
    Storage::disk('public')->assertMissing($path);
});

test('admin can delete facility', function () {
    $image = UploadedFile::fake()->image('facility.jpg');
    $path = $image->store('facilities', 'public');

    $facility = Facility::factory()->create([
        'school_profile_id' => $this->profile->id,
        'name' => 'Test Facility',
        'image_path' => $path,
        'order' => 1,
    ]);

    Volt::actingAs($this->admin)
        ->test('admin.school-profile.facilities')
        ->call('delete', $facility->id);

    expect(Facility::find($facility->id))->toBeNull();
    Storage::disk('public')->assertMissing($path);
});

test('facilities are ordered correctly on creation', function () {
    $facility1 = Facility::factory()->create([
        'school_profile_id' => $this->profile->id,
        'name' => 'First',
        'order' => 1,
    ]);

    Volt::actingAs($this->admin)
        ->test('admin.school-profile.facilities')
        ->call('showAddForm')
        ->set('name', 'Second')
        ->call('save');

    $facility2 = Facility::where('name', 'Second')->first();
    expect($facility2->order)->toBe(2);
});

test('admin can move facility up in order', function () {
    $facility1 = Facility::factory()->create([
        'school_profile_id' => $this->profile->id,
        'name' => 'First',
        'order' => 1,
    ]);

    $facility2 = Facility::factory()->create([
        'school_profile_id' => $this->profile->id,
        'name' => 'Second',
        'order' => 2,
    ]);

    Volt::actingAs($this->admin)
        ->test('admin.school-profile.facilities')
        ->call('moveUp', $facility2->id);

    $facility1->refresh();
    $facility2->refresh();

    expect($facility1->order)->toBe(2);
    expect($facility2->order)->toBe(1);
});

test('admin can move facility down in order', function () {
    $facility1 = Facility::factory()->create([
        'school_profile_id' => $this->profile->id,
        'name' => 'First',
        'order' => 1,
    ]);

    $facility2 = Facility::factory()->create([
        'school_profile_id' => $this->profile->id,
        'name' => 'Second',
        'order' => 2,
    ]);

    Volt::actingAs($this->admin)
        ->test('admin.school-profile.facilities')
        ->call('moveDown', $facility1->id);

    $facility1->refresh();
    $facility2->refresh();

    expect($facility1->order)->toBe(2);
    expect($facility2->order)->toBe(1);
});

test('cannot move first facility up', function () {
    $facility = Facility::factory()->create([
        'school_profile_id' => $this->profile->id,
        'name' => 'First',
        'order' => 1,
    ]);

    Volt::actingAs($this->admin)
        ->test('admin.school-profile.facilities')
        ->call('moveUp', $facility->id);

    $facility->refresh();
    expect($facility->order)->toBe(1);
});

test('cannot move last facility down', function () {
    $facility1 = Facility::factory()->create([
        'school_profile_id' => $this->profile->id,
        'name' => 'First',
        'order' => 1,
    ]);

    $facility2 = Facility::factory()->create([
        'school_profile_id' => $this->profile->id,
        'name' => 'Second',
        'order' => 2,
    ]);

    Volt::actingAs($this->admin)
        ->test('admin.school-profile.facilities')
        ->call('moveDown', $facility2->id);

    $facility2->refresh();
    expect($facility2->order)->toBe(2);
});

test('deleting facility reorders remaining facilities', function () {
    $facility1 = Facility::factory()->create([
        'school_profile_id' => $this->profile->id,
        'name' => 'First',
        'order' => 1,
    ]);

    $facility2 = Facility::factory()->create([
        'school_profile_id' => $this->profile->id,
        'name' => 'Second',
        'order' => 2,
    ]);

    $facility3 = Facility::factory()->create([
        'school_profile_id' => $this->profile->id,
        'name' => 'Third',
        'order' => 3,
    ]);

    Volt::actingAs($this->admin)
        ->test('admin.school-profile.facilities')
        ->call('delete', $facility2->id);

    $facility1->refresh();
    $facility3->refresh();

    expect($facility1->order)->toBe(1);
    expect($facility3->order)->toBe(2);
});

test('shows error message when school profile does not exist', function () {
    SchoolProfile::query()->delete();

    Volt::actingAs($this->admin)
        ->test('admin.school-profile.facilities')
        ->assertSee('Profil sekolah belum dibuat');
});

test('cancel edit resets form', function () {
    $facility = Facility::factory()->create([
        'school_profile_id' => $this->profile->id,
        'name' => 'Test Facility',
        'order' => 1,
    ]);

    $component = Volt::actingAs($this->admin)
        ->test('admin.school-profile.facilities')
        ->call('edit', $facility->id)
        ->assertSet('editingId', $facility->id)
        ->assertSet('name', 'Test Facility')
        ->call('cancelEdit')
        ->assertSet('editingId', null)
        ->assertSet('name', '')
        ->assertSet('showForm', false);
});
