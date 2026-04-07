<?php

declare(strict_types=1);

use App\Models\SchoolProfile;
use App\Models\StaffMember;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->withoutVite();
    Storage::fake('public');
    $this->user = User::factory()->create(['role' => 'admin']);
    $this->profile = SchoolProfile::factory()->create(['is_active' => true]);
});

test('admin can view staff members management page', function () {
    $this->actingAs($this->user)
        ->get(route('admin.school-profile.staff-members'))
        ->assertOk()
        ->assertSee('Struktur Organisasi');
});

test('admin can add new staff member without photo', function () {
    $this->actingAs($this->user);

    Livewire::test('admin.web-content.school-profile.staff-members')
        ->set('name', 'Dr. Ahmad Suryadi, M.Pd')
        ->set('position', 'Kepala Sekolah')
        ->call('save')
        ->assertHasNoErrors();

    expect(StaffMember::count())->toBe(1);

    $staff = StaffMember::first();
    expect($staff->name)->toBe('Dr. Ahmad Suryadi, M.Pd')
        ->and($staff->position)->toBe('Kepala Sekolah')
        ->and($staff->school_profile_id)->toBe($this->profile->id)
        ->and($staff->order)->toBe(1)
        ->and($staff->photo_path)->toBeNull();
});

test('admin can add new staff member with photo', function () {
    $this->actingAs($this->user);
    $photo = UploadedFile::fake()->image('staff.jpg', 800, 600);

    Livewire::test('admin.web-content.school-profile.staff-members')
        ->set('name', 'Dr. Ahmad Suryadi, M.Pd')
        ->set('position', 'Kepala Sekolah')
        ->set('photo', $photo)
        ->call('save')
        ->assertHasNoErrors();

    expect(StaffMember::count())->toBe(1);

    $staff = StaffMember::first();
    expect($staff->photo_path)->not->toBeNull();
    Storage::disk('public')->assertExists($staff->photo_path);
});

test('admin can edit staff member', function () {
    $this->actingAs($this->user);
    $staff = StaffMember::factory()->create([
        'school_profile_id' => $this->profile->id,
        'name' => 'Old Name',
        'position' => 'Old Position',
    ]);

    Livewire::test('admin.web-content.school-profile.staff-members')
        ->call('edit', $staff->id)
        ->assertSet('editingId', $staff->id)
        ->assertSet('name', 'Old Name')
        ->assertSet('position', 'Old Position')
        ->set('name', 'New Name')
        ->set('position', 'New Position')
        ->call('save')
        ->assertHasNoErrors();

    $staff->refresh();
    expect($staff->name)->toBe('New Name')
        ->and($staff->position)->toBe('New Position');
});

test('admin can update staff member photo', function () {
    $this->actingAs($this->user);
    $oldPhoto = UploadedFile::fake()->image('old.jpg');
    $oldPath = $oldPhoto->store('staff', 'public');

    $staff = StaffMember::factory()->create([
        'school_profile_id' => $this->profile->id,
        'photo_path' => $oldPath,
    ]);

    $newPhoto = UploadedFile::fake()->image('new.jpg');

    Livewire::test('admin.web-content.school-profile.staff-members')
        ->call('edit', $staff->id)
        ->set('photo', $newPhoto)
        ->call('save')
        ->assertHasNoErrors();

    $staff->refresh();
    expect($staff->photo_path)->not->toBe($oldPath);
    Storage::disk('public')->assertMissing($oldPath);
    Storage::disk('public')->assertExists($staff->photo_path);
});

test('admin can remove staff member photo', function () {
    $this->actingAs($this->user);
    $photo = UploadedFile::fake()->image('staff.jpg');
    $path = $photo->store('staff', 'public');

    $staff = StaffMember::factory()->create([
        'school_profile_id' => $this->profile->id,
        'photo_path' => $path,
    ]);

    Livewire::test('admin.web-content.school-profile.staff-members')
        ->call('edit', $staff->id)
        ->call('removePhoto');

    $staff->refresh();
    expect($staff->photo_path)->toBeNull();
    Storage::disk('public')->assertMissing($path);
});

test('admin can delete staff member', function () {
    $this->actingAs($this->user);
    $photo = UploadedFile::fake()->image('staff.jpg');
    $path = $photo->store('staff', 'public');

    $staff = StaffMember::factory()->create([
        'school_profile_id' => $this->profile->id,
        'photo_path' => $path,
    ]);

    Livewire::test('admin.web-content.school-profile.staff-members')
        ->call('delete', $staff->id);

    expect(StaffMember::count())->toBe(0);
    Storage::disk('public')->assertMissing($path);
});

test('staff members are ordered correctly on creation', function () {
    $this->actingAs($this->user);
    $staff1 = StaffMember::factory()->create([
        'school_profile_id' => $this->profile->id,
        'order' => 1,
    ]);

    $staff2 = StaffMember::factory()->create([
        'school_profile_id' => $this->profile->id,
        'order' => 2,
    ]);

    Livewire::test('admin.web-content.school-profile.staff-members')
        ->set('name', 'New Staff')
        ->set('position', 'New Position')
        ->call('save');

    $newStaff = StaffMember::latest('id')->first();
    expect($newStaff->order)->toBe(3);
});

test('admin can move staff member up', function () {
    $this->actingAs($this->user);
    $staff1 = StaffMember::factory()->create([
        'school_profile_id' => $this->profile->id,
        'name' => 'Staff 1',
        'order' => 1,
    ]);

    $staff2 = StaffMember::factory()->create([
        'school_profile_id' => $this->profile->id,
        'name' => 'Staff 2',
        'order' => 2,
    ]);

    Livewire::test('admin.web-content.school-profile.staff-members')
        ->call('moveUp', $staff2->id);

    $staff1->refresh();
    $staff2->refresh();

    expect($staff1->order)->toBe(2)
        ->and($staff2->order)->toBe(1);
});

test('admin can move staff member down', function () {
    $this->actingAs($this->user);
    $staff1 = StaffMember::factory()->create([
        'school_profile_id' => $this->profile->id,
        'name' => 'Staff 1',
        'order' => 1,
    ]);

    $staff2 = StaffMember::factory()->create([
        'school_profile_id' => $this->profile->id,
        'name' => 'Staff 2',
        'order' => 2,
    ]);

    Livewire::test('admin.web-content.school-profile.staff-members')
        ->call('moveDown', $staff1->id);

    $staff1->refresh();
    $staff2->refresh();

    expect($staff1->order)->toBe(2)
        ->and($staff2->order)->toBe(1);
});

test('cannot move first staff member up', function () {
    $this->actingAs($this->user);
    $staff = StaffMember::factory()->create([
        'school_profile_id' => $this->profile->id,
        'order' => 1,
    ]);

    Livewire::test('admin.web-content.school-profile.staff-members')
        ->call('moveUp', $staff->id);

    $staff->refresh();
    expect($staff->order)->toBe(1);
});

test('cannot move last staff member down', function () {
    $this->actingAs($this->user);
    $staff1 = StaffMember::factory()->create([
        'school_profile_id' => $this->profile->id,
        'order' => 1,
    ]);

    $staff2 = StaffMember::factory()->create([
        'school_profile_id' => $this->profile->id,
        'order' => 2,
    ]);

    Livewire::test('admin.web-content.school-profile.staff-members')
        ->call('moveDown', $staff2->id);

    $staff2->refresh();
    expect($staff2->order)->toBe(2);
});

test('staff members are reordered after deletion', function () {
    $this->actingAs($this->user);
    $staff1 = StaffMember::factory()->create([
        'school_profile_id' => $this->profile->id,
        'order' => 1,
    ]);

    $staff2 = StaffMember::factory()->create([
        'school_profile_id' => $this->profile->id,
        'order' => 2,
    ]);

    $staff3 = StaffMember::factory()->create([
        'school_profile_id' => $this->profile->id,
        'order' => 3,
    ]);

    Livewire::test('admin.web-content.school-profile.staff-members')
        ->call('delete', $staff2->id);

    $staff1->refresh();
    $staff3->refresh();

    expect($staff1->order)->toBe(1)
        ->and($staff3->order)->toBe(2);
});

test('name is required', function () {
    $this->actingAs($this->user);

    Livewire::test('admin.web-content.school-profile.staff-members')
        ->set('name', '')
        ->set('position', 'Kepala Sekolah')
        ->call('save')
        ->assertHasErrors(['name']);
});

test('position is required', function () {
    $this->actingAs($this->user);

    Livewire::test('admin.web-content.school-profile.staff-members')
        ->set('name', 'Dr. Ahmad Suryadi')
        ->set('position', '')
        ->call('save')
        ->assertHasErrors(['position']);
});

test('photo must be an image', function () {
    $this->actingAs($this->user);
    $file = UploadedFile::fake()->create('document.pdf', 1000);

    Livewire::test('admin.web-content.school-profile.staff-members')
        ->set('name', 'Dr. Ahmad Suryadi')
        ->set('position', 'Kepala Sekolah')
        ->set('photo', $file)
        ->call('save')
        ->assertHasErrors(['photo']);
});

test('admin can cancel editing', function () {
    $this->actingAs($this->user);
    $staff = StaffMember::factory()->create([
        'school_profile_id' => $this->profile->id,
    ]);

    Livewire::test('admin.web-content.school-profile.staff-members')
        ->call('edit', $staff->id)
        ->assertSet('editingId', $staff->id)
        ->call('cancelEdit')
        ->assertSet('editingId', null)
        ->assertSet('name', '')
        ->assertSet('position', '');
});

test('shows error when no school profile exists', function () {
    $this->actingAs($this->user);
    SchoolProfile::query()->delete();

    $this->get(route('admin.school-profile.staff-members'))
        ->assertOk()
        ->assertSee('Profil sekolah belum dibuat');
});

test('non-admin cannot access staff members management', function () {
    $user = User::factory()->create(['role' => 'teacher']);

    $this->actingAs($user)
        ->get(route('admin.school-profile.staff-members'))
        ->assertForbidden();
});
