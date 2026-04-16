<?php

declare(strict_types=1);

use App\Models\SchoolProfile;
use App\Models\StaffMember;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->withoutVite();
    Storage::fake('public');
    $this->user = User::factory()->create(['role' => 'admin']);
    $this->profile = SchoolProfile::factory()->create(['is_active' => true]);
});

test('admin can view staff members management page', function () {
    actingAs($this->user)
        ->get(route('admin.school-profile.staff-members'))
        ->assertOk()
        ->assertSee('Struktur Organisasi');
});

test('admin can add new staff member without photo', function () {
    Livewire::actingAs($this->user)
        ->test('admin.web-content.school-profile.staff-members')
        ->set('name', 'Dr. Ahmad Suryadi, M.Pd')
        ->set('position', 'Kepala Sekolah')
        ->call('save')
        ->assertHasNoErrors();

    expect(StaffMember::count())->toBe(1);

    $staff = StaffMember::first();
    expect($staff->name)->toBe('Dr. Ahmad Suryadi, M.Pd');
});

test('admin can add new staff member with photo', function () {
    $photo = UploadedFile::fake()->image('staff.jpg', 800, 600);

    Livewire::actingAs($this->user)
        ->test('admin.web-content.school-profile.staff-members')
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
    $staff = StaffMember::factory()->create([
        'school_profile_id' => $this->profile->id,
        'name' => 'Old Name',
        'position' => 'Old Position',
    ]);

    Livewire::actingAs($this->user)
        ->test('admin.web-content.school-profile.staff-members')
        ->call('edit', $staff->id)
        ->assertSet('editingId', $staff->id)
        ->assertSet('name', 'Old Name')
        ->assertSet('position', 'Old Position')
        ->set('name', 'New Name')
        ->set('position', 'New Position')
        ->call('save')
        ->assertHasNoErrors();

    $staff->refresh();
    expect($staff->name)->toBe('New Name');
});

test('admin can delete staff member', function () {
    $photo = UploadedFile::fake()->image('staff.jpg');
    $path = $photo->store('staff', 'public');

    $staff = StaffMember::factory()->create([
        'school_profile_id' => $this->profile->id,
        'photo_path' => $path,
    ]);

    Livewire::actingAs($this->user)
        ->test('admin.web-content.school-profile.staff-members')
        ->call('delete', $staff->id);

    expect(StaffMember::count())->toBe(0);
    Storage::disk('public')->assertMissing($path);
});

test('admin can move staff member up', function () {
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

    Livewire::actingAs($this->user)
        ->test('admin.web-content.school-profile.staff-members')
        ->call('moveUp', $staff2->id);

    $staff1->refresh();
    $staff2->refresh();

    expect($staff1->order)->toBe(2)
        ->and($staff2->order)->toBe(1);
});

test('non-admin cannot access staff members management', function () {
    $user = User::factory()->create(['role' => 'guru']);

    actingAs($user)
        ->get(route('admin.school-profile.staff-members'))
        ->assertForbidden();
});
