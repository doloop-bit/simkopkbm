<?php

declare(strict_types=1);

use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->withoutVite();
});

test('profile page is displayed', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('profile.edit'))->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('admin.settings.profile')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->call('updateProfileInformation')
        ->assertHasNoErrors();

    $user->refresh();

    expect($user->name)->toEqual('Test User');
    expect($user->email)->toEqual('test@example.com');
});

test('email verification status is unchanged when email address is unchanged', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user);

    Livewire::test('admin.settings.profile')
        ->set('name', 'Test User')
        ->set('email', $user->email)
        ->call('updateProfileInformation')
        ->assertHasNoErrors();

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

test('user can delete their account', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password'),
    ]);

    $this->actingAs($user);

    Livewire::test('admin.settings.delete-user-form')
        ->set('password', 'password')
        ->call('deleteUser')
        ->assertHasNoErrors()
        ->assertRedirect('/');

    expect($user->fresh())->toBeNull();
    expect(auth()->check())->toBeFalse();
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password'),
    ]);

    $this->actingAs($user);

    Livewire::test('admin.settings.delete-user-form')
        ->set('password', 'wrong-password')
        ->call('deleteUser')
        ->assertHasErrors(['password']);

    expect($user->fresh())->not->toBeNull();
});

test('student profile page does not show delete account button', function () {
    $student = User::factory()->siswa()->create();

    $this->actingAs($student)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertDontSee('Delete account')
        ->assertDontSee('Delete your account');
});

test('student cannot delete their account', function () {
    $student = User::factory()->siswa()->create([
        'password' => Hash::make('password'),
    ]);

    $this->actingAs($student);

    Livewire::test('admin.settings.delete-user-form')
        ->set('password', 'password')
        ->call('deleteUser')
        ->assertForbidden();

    expect($student->fresh())->not->toBeNull();
});

test('user can upload profile photo', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $this->actingAs($user);

    $file = Illuminate\Http\UploadedFile::fake()->image('profile.jpg', 200, 200);

    Livewire::test('admin.settings.profile')
        ->set('photo', $file)
        ->call('updateProfileInformation')
        ->assertHasNoErrors();

    $user->refresh();

    expect($user->photo)->not->toBeNull();
    Storage::disk('public')->assertExists($user->photo);
});

test('student profile photo upload also syncs to student profile', function () {
    Storage::fake('public');

    $student = User::factory()->siswa()->create();
    $this->actingAs($student);

    $file = Illuminate\Http\UploadedFile::fake()->image('student.png', 200, 200);

    Livewire::test('admin.settings.profile')
        ->set('photo', $file)
        ->call('updateProfileInformation')
        ->assertHasNoErrors();

    $student->refresh();
    $studentProfile = $student->studentProfile ?? $student->latestProfile?->profileable;

    expect($student->photo)->not->toBeNull();
    expect($studentProfile->photo)->toEqual($student->photo);
    Storage::disk('public')->assertExists($student->photo);
});

test('user can remove profile photo', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $this->actingAs($user);

    $file = Illuminate\Http\UploadedFile::fake()->image('avatar.jpg');
    $path = $file->store('avatars', 'public');
    $user->update(['photo' => $path]);

    Livewire::test('admin.settings.profile')
        ->call('deletePhoto')
        ->assertHasNoErrors();

    $user->refresh();

    expect($user->photo)->toBeNull();
    Storage::disk('public')->assertMissing($path);
});
