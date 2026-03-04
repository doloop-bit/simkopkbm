<?php

use App\Models\SchoolProfile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;

beforeEach(function () {
    $this->withoutVite();
});

test('admin can access school profile edit page', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('admin.school-profile.edit'))
        ->assertOk()
        ->assertSeeLivewire('admin.school-profile.edit');
});

test('non-admin cannot access school profile edit page', function () {
    $user = User::factory()->create(['role' => 'teacher']);

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

    Volt::actingAs($admin)
        ->test('admin.school-profile.edit')
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
    expect($profile->name)->toBe('PKBM Harapan Bangsa')
        ->and($profile->address)->toBe('Jl. Pendidikan No. 123, Jakarta')
        ->and($profile->phone)->toBe('021-12345678')
        ->and($profile->email)->toBe('info@pkbm.com')
        ->and($profile->vision)->toBe('Menjadi lembaga pendidikan terdepan')
        ->and($profile->mission)->toBe('Memberikan pendidikan berkualitas untuk semua')
        ->and($profile->is_active)->toBeTrue();
});

test('admin can update existing school profile', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $profile = SchoolProfile::factory()->create([
        'name' => 'Old Name',
        'is_active' => true,
    ]);

    Volt::actingAs($admin)
        ->test('admin.school-profile.edit')
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

    Volt::actingAs($admin)
        ->test('admin.school-profile.edit')
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

test('logo upload replaces old logo', function () {
    Storage::fake('public');
    $admin = User::factory()->create(['role' => 'admin']);

    $oldLogo = UploadedFile::fake()->image('old-logo.png');
    $profile = SchoolProfile::factory()->create([
        'logo_path' => $oldLogo->store('school-profile', 'public'),
        'is_active' => true,
    ]);

    $oldLogoPath = $profile->logo_path;
    Storage::disk('public')->assertExists($oldLogoPath);

    $newLogo = UploadedFile::fake()->image('new-logo.png');

    Volt::actingAs($admin)
        ->test('admin.school-profile.edit')
        ->set('logo', $newLogo)
        ->call('save')
        ->assertHasNoErrors();

    $profile->refresh();
    expect($profile->logo_path)->not->toBe($oldLogoPath);
    Storage::disk('public')->assertMissing($oldLogoPath);
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

    Volt::actingAs($admin)
        ->test('admin.school-profile.edit')
        ->call('removeLogo')
        ->assertHasNoErrors();

    $profile->refresh();
    expect($profile->logo_path)->toBeNull();
    Storage::disk('public')->assertMissing($logoPath);
});

test('required fields are validated', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    Volt::actingAs($admin)
        ->test('admin.school-profile.edit')
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

    Volt::actingAs($admin)
        ->test('admin.school-profile.edit')
        ->set('name', 'Test School')
        ->set('address', 'Test Address')
        ->set('phone', '021-12345678')
        ->set('email', 'invalid-email')
        ->set('vision', 'Test Vision')
        ->set('mission', 'Test Mission')
        ->call('save')
        ->assertHasErrors(['email']);
});

test('social media urls must be valid urls', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    Volt::actingAs($admin)
        ->test('admin.school-profile.edit')
        ->set('name', 'Test School')
        ->set('address', 'Test Address')
        ->set('phone', '021-12345678')
        ->set('email', 'test@test.com')
        ->set('vision', 'Test Vision')
        ->set('mission', 'Test Mission')
        ->set('facebook_url', 'not-a-url')
        ->set('instagram_url', 'also-not-a-url')
        ->call('save')
        ->assertHasErrors(['facebook_url', 'instagram_url']);
});

test('logo must be an image file', function () {
    Storage::fake('public');
    $admin = User::factory()->create(['role' => 'admin']);
    $file = UploadedFile::fake()->create('document.pdf', 1000);

    Volt::actingAs($admin)
        ->test('admin.school-profile.edit')
        ->set('name', 'Test School')
        ->set('address', 'Test Address')
        ->set('phone', '021-12345678')
        ->set('email', 'test@test.com')
        ->set('vision', 'Test Vision')
        ->set('mission', 'Test Mission')
        ->set('logo', $file)
        ->call('save')
        ->assertHasErrors(['logo']);
});

test('logo must not exceed 5MB', function () {
    Storage::fake('public');
    $admin = User::factory()->create(['role' => 'admin']);
    $file = UploadedFile::fake()->image('large-logo.png')->size(6000); // 6MB

    Volt::actingAs($admin)
        ->test('admin.school-profile.edit')
        ->set('name', 'Test School')
        ->set('address', 'Test Address')
        ->set('phone', '021-12345678')
        ->set('email', 'test@test.com')
        ->set('vision', 'Test Vision')
        ->set('mission', 'Test Mission')
        ->set('logo', $file)
        ->call('save')
        ->assertHasErrors(['logo']);
});

test('logo accepts jpeg, png, and webp formats', function () {
    Storage::fake('public');
    $admin = User::factory()->create(['role' => 'admin']);

    $formats = ['jpg', 'jpeg', 'png', 'webp'];

    foreach ($formats as $format) {
        $file = UploadedFile::fake()->image("logo.{$format}");

        Volt::actingAs($admin)
            ->test('admin.school-profile.edit')
            ->set('name', 'Test School')
            ->set('address', 'Test Address')
            ->set('phone', '021-12345678')
            ->set('email', 'test@test.com')
            ->set('vision', 'Test Vision')
            ->set('mission', 'Test Mission')
            ->set('logo', $file)
            ->call('save')
            ->assertHasNoErrors(['logo']);

        // Clean up for next iteration
        SchoolProfile::query()->delete();
        Storage::disk('public')->deleteDirectory('school-profile');
    }
});

test('latitude must be between -90 and 90', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    Volt::actingAs($admin)
        ->test('admin.school-profile.edit')
        ->set('name', 'Test School')
        ->set('address', 'Test Address')
        ->set('phone', '021-12345678')
        ->set('email', 'test@test.com')
        ->set('vision', 'Test Vision')
        ->set('mission', 'Test Mission')
        ->set('latitude', '100')
        ->call('save')
        ->assertHasErrors(['latitude']);
});

test('longitude must be between -180 and 180', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    Volt::actingAs($admin)
        ->test('admin.school-profile.edit')
        ->set('name', 'Test School')
        ->set('address', 'Test Address')
        ->set('phone', '021-12345678')
        ->set('email', 'test@test.com')
        ->set('vision', 'Test Vision')
        ->set('mission', 'Test Mission')
        ->set('longitude', '200')
        ->call('save')
        ->assertHasErrors(['longitude']);
});

test('component loads existing profile data', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $profile = SchoolProfile::factory()->create([
        'name' => 'Existing School',
        'address' => 'Existing Address',
        'is_active' => true,
    ]);

    Volt::actingAs($admin)
        ->test('admin.school-profile.edit')
        ->assertSet('name', 'Existing School')
        ->assertSet('address', 'Existing Address');
});

test('profile is saved successfully', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    Volt::actingAs($admin)
        ->test('admin.school-profile.edit')
        ->set('name', 'Test School')
        ->set('address', 'Test Address')
        ->set('phone', '021-12345678')
        ->set('email', 'test@test.com')
        ->set('vision', 'Test Vision')
        ->set('mission', 'Test Mission')
        ->call('save')
        ->assertHasNoErrors();

    // Verify the profile was saved to the database
    $profile = SchoolProfile::first();
    expect($profile)->not->toBeNull()
        ->and($profile->name)->toBe('Test School')
        ->and($profile->is_active)->toBeTrue();
});

test('optional fields can be left empty', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    Volt::actingAs($admin)
        ->test('admin.school-profile.edit')
        ->set('name', 'Test School')
        ->set('address', 'Test Address')
        ->set('phone', '021-12345678')
        ->set('email', 'test@test.com')
        ->set('vision', 'Test Vision')
        ->set('mission', 'Test Mission')
        ->set('history', '')
        ->set('operating_hours', '')
        ->set('facebook_url', '')
        ->set('instagram_url', '')
        ->set('youtube_url', '')
        ->set('twitter_url', '')
        ->set('latitude', '')
        ->set('longitude', '')
        ->call('save')
        ->assertHasNoErrors();

    $profile = SchoolProfile::first();
    expect($profile->history)->toBe('')
        ->and($profile->operating_hours)->toBe('')
        ->and($profile->facebook_url)->toBe('')
        ->and($profile->latitude)->toBeNull()
        ->and($profile->longitude)->toBeNull();
});
