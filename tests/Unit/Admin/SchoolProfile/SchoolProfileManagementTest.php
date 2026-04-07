<?php

declare(strict_types=1);

use App\Models\SchoolProfile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->withoutVite();
});

test('admin can access school profile edit page', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('admin.school-profile.edit'))
        ->assertOk()
        ->assertSeeLivewire('admin.web-content.school-profile.edit');
});

test('non-admin cannot access school profile edit page', function () {
    $user = User::factory()->create(['role' => 'guru']);

    $this->actingAs($user)
        ->get(route('admin.school-profile.edit'))
        ->assertForbidden();
});

test('guest cannot access school profile edit page', function () {
    $this->get(route('admin.school-profile.edit'))
        ->assertRedirect(route('login'));
});

test('admin can create new school profile', function () {
    Storage::fake('public');
    $admin = User::factory()->create(['role' => 'admin']);

    Livewire::actingAs($admin)
        ->test('admin.web-content.school-profile.edit')
        ->set('name', 'PKBM Harapan Bangsa')
        ->set('address', 'Jl. Pendidikan No. 123, Jakarta')
        ->set('phone', '021-12345678')
        ->set('email', 'info@pkbm.com')
        ->set('vision', 'Menjadi lembaga pendidikan terdepan')
        ->set('mission', 'Memberikan pendidikan berkualitas untuk semua')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('name', 'PKBM Harapan Bangsa');

    expect(SchoolProfile::count())->toBe(1);

    $profile = SchoolProfile::first();
    expect($profile->name)->toBe('PKBM Harapan Bangsa');
});

test('admin can update existing school profile', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $profile = SchoolProfile::factory()->create([
        'name' => 'Old Name',
        'is_active' => true,
    ]);

    Livewire::actingAs($admin)
        ->test('admin.web-content.school-profile.edit')
        ->set('name', 'New Name')
        ->set('address', $profile->address)
        ->set('phone', $profile->phone)
        ->set('email', $profile->email)
        ->set('vision', $profile->vision)
        ->set('mission', $profile->mission)
        ->call('save')
        ->assertHasNoErrors();

    $profile->refresh();
    expect($profile->name)->toBe('New Name');
});

test('admin can upload school logo', function () {
    Storage::fake('public');
    $admin = User::factory()->create(['role' => 'admin']);
    $logo = UploadedFile::fake()->image('logo.png', 500, 500);

    Livewire::actingAs($admin)
        ->test('admin.web-content.school-profile.edit')
        ->set('name', 'PKBM Test')
        ->set('address', 'Test Address')
        ->set('phone', '021-12345678')
        ->set('email', 'test@test.com')
        ->set('vision', 'Test Vision')
        ->set('mission', 'Test Mission')
        ->set('logo', $logo)
        ->call('save')
        ->assertHasNoErrors();

    $profile = SchoolProfile::first();
    expect($profile->logo_path)->not->toBeNull();
    Storage::disk('public')->assertExists($profile->logo_path);
});

test('admin can remove school logo', function () {
    Storage::fake('public');
    $admin = User::factory()->create(['role' => 'admin']);

    $logo = UploadedFile::fake()->image('logo.png');
    $profile = SchoolProfile::factory()->create([
        'logo_path' => $logo->store('school-profile', 'public'),
        'is_active' => true,
    ]);

    $logoPath = $profile->logo_path;
    Storage::disk('public')->assertExists($logoPath);

    Livewire::actingAs($admin)
        ->test('admin.web-content.school-profile.edit')
        ->call('removeLogo')
        ->assertHasNoErrors();

    $profile->refresh();
    expect($profile->logo_path)->toBeNull();
    Storage::disk('public')->assertMissing($logoPath);
});

test('required fields are validated', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    Livewire::actingAs($admin)
        ->test('admin.web-content.school-profile.edit')
        ->set('name', '')
        ->set('address', '')
        ->set('phone', '')
        ->set('email', '')
        ->set('vision', '')
        ->set('mission', '')
        ->call('save')
        ->assertHasErrors(['name', 'address', 'phone', 'email', 'vision', 'mission']);
});

test('email field must be valid email', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    Livewire::actingAs($admin)
        ->test('admin.web-content.school-profile.edit')
        ->set('name', 'Test School')
        ->set('email', 'invalid-email')
        ->call('save')
        ->assertHasErrors(['email']);
});

test('logo must be an image file', function () {
    Storage::fake('public');
    $admin = User::factory()->create(['role' => 'admin']);
    $file = UploadedFile::fake()->create('document.pdf', 1000);

    Livewire::actingAs($admin)
        ->test('admin.web-content.school-profile.edit')
        ->set('logo', $file)
        ->call('save')
        ->assertHasErrors(['logo']);
});
