<?php

use App\Models\Role;
use App\Models\User;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('admin can manage multiple roles for a user', function () {
    $admin = User::factory()->admin()->create();
    // Seed roles if they don't exist (migration should have done it, but let's be safe)
    if (Role::count() === 0) {
        collect(['admin', 'bendahara', 'guru'])->each(fn ($s) => Role::create(['name' => ucfirst($s), 'slug' => $s]));
    }

    $targetUser = User::factory()->create(['name' => 'Target User', 'email' => 'target@example.com']);
    $roles = Role::whereIn('slug', ['bendahara', 'guru'])->get();

    Livewire::actingAs($admin)
        ->test('admin.data-master.users')
        ->call('edit', $targetUser)
        ->set('role_ids', $roles->pluck('id')->toArray())
        ->set('is_active', true)
        ->call('save')
        ->assertHasNoErrors();

    expect($targetUser->fresh()->roles)->toHaveCount(2);
    expect($targetUser->fresh()->role)->toBe('bendahara'); // First selected role as legacy
});

test('user with multiple roles can select active role', function () {
    $roles = collect(['admin', 'bendahara', 'guru'])->map(fn ($s) => Role::firstOrCreate(['slug' => $s], ['name' => ucfirst($s)]));

    $user = User::factory()->create();
    $user->roles()->attach($roles->pluck('id')->toArray());

    Livewire::actingAs($user)
        ->test('auth.select-role')
        ->assertSee('Admin')
        ->assertSee('Bendahara')
        ->assertSee('Guru')
        ->call('selectRole', $roles->firstWhere('slug', 'bendahara')->id)
        ->assertRedirect(route('dashboard'));

    expect(session('active_role_id'))->toBe($roles->firstWhere('slug', 'bendahara')->id);
    expect($user->fresh()->activeRoleSlug())->toBe('bendahara');
});

test('middleware redirects user with multiple roles to select-role page', function () {
    $roles = collect(['admin', 'bendahara', 'guru'])->map(fn ($s) => Role::firstOrCreate(['slug' => $s], ['name' => ucfirst($s)]));
    $user = User::factory()->create();
    $user->roles()->attach($roles->pluck('id')->toArray());

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('select-role'));
});

test('middleware does not redirect user with single role', function () {
    $role = Role::firstOrCreate(['slug' => 'guru'], ['name' => 'Guru']);
    $user = User::factory()->create();
    $user->roles()->attach([$role->id]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk(); // Should auto-select role and proceed

    expect(session('active_role_id'))->toBe($role->id);
});
