<?php

namespace Tests\Feature\Livewire\Auth;

use App\Livewire\Auth\SelectRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Session;
use Livewire\Livewire;

test('select role component renders correctly with user roles', function () {
    $roles = [
        Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Administrator']),
        Role::firstOrCreate(['slug' => 'guru'], ['name' => 'Guru']),
    ];

    $user = User::factory()->create();
    $user->roles()->attach(collect($roles)->pluck('id'));

    Livewire::actingAs($user)
        ->test(SelectRole::class)
        ->assertStatus(200)
        ->assertSee('Administrator')
        ->assertSee('Guru')
        ->assertViewHas('roles', function ($viewRoles) {
            return $viewRoles->count() === 2;
        });
});

test('user can select a role and be redirected to correct dashboard', function () {
    $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Administrator']);
    $guruRole = Role::firstOrCreate(['slug' => 'guru'], ['name' => 'Guru']);

    $user = User::factory()->create();
    $user->roles()->attach([$adminRole->id, $guruRole->id]);

    // Test Admin selection
    Livewire::actingAs($user)
        ->test(SelectRole::class)
        ->call('selectRole', $adminRole->id)
        ->assertRedirect(route('dashboard'));

    // Test Guru selection
    Livewire::actingAs($user)
        ->test(SelectRole::class)
        ->call('selectRole', $guruRole->id)
        ->assertRedirect(route('teacher.dashboard'));

    expect(Session::get('active_role_id'))->toBe($guruRole->id);
});

test('user cannot select a role they do not have', function () {
    $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Administrator']);
    $guruRole = Role::firstOrCreate(['slug' => 'guru'], ['name' => 'Guru']);

    $user = User::factory()->create();
    $user->roles()->attach([$guruRole->id]); // User only has Guru role

    Livewire::actingAs($user)
        ->test(SelectRole::class)
        ->call('selectRole', $adminRole->id) // Try to select Admin
        ->assertNoRedirect();

    expect(Session::has('active_role_id'))->toBeFalse();
});

test('guest is redirected from select role page', function () {
    $this->get(route('select-role'))
        ->assertRedirect(route('login'));
});

test('user with multiple roles is redirected to select-role page upon login', function () {
    $roles = [
        Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Administrator']),
        Role::firstOrCreate(['slug' => 'guru'], ['name' => 'Guru']),
    ];

    $user = User::factory()->create();
    $user->roles()->attach(collect($roles)->pluck('id'));

    // Directly access dashboard without selecting role
    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('select-role'));
});

test('user with single role is automatically assigned when accessing protected route', function () {
    $guruRole = Role::firstOrCreate(['slug' => 'guru'], ['name' => 'Guru']);

    $user = User::factory()->create();
    $user->roles()->attach([$guruRole->id]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();

    expect(Session::get('active_role_id'))->toBe($guruRole->id);
});

test('logged in user without active role can still access public home page', function () {
    $roles = [
        Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Administrator']),
        Role::firstOrCreate(['slug' => 'guru'], ['name' => 'Guru']),
    ];

    $user = User::factory()->create();
    $user->roles()->attach(collect($roles)->pluck('id'));

    // Access home page (public)
    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk();

    // active_role_id should NOT be set automatically for public pages if multiple roles exist
    expect(Session::has('active_role_id'))->toBeFalse();
});

test('user is redirected to intended URL after selecting role (disabled for now as we use manual match)', function () {
    // We changed SelectRole to use manual match instead of redirectIntended to ensure
    // users go to the correct dashboard type (admin vs teacher).
    $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Administrator']);
    $user = User::factory()->create();
    $user->roles()->attach([$adminRole->id]);

    Livewire::actingAs($user)
        ->test(SelectRole::class)
        ->call('selectRole', $adminRole->id)
        ->assertRedirect(route('dashboard'));
});
