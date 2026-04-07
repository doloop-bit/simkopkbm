<?php

declare(strict_types=1);

use App\Models\User;
use Livewire\Livewire;

it('can render', function () {
    $this->withoutVite();
    $user = User::factory()->create(['role' => 'admin']);

    $this->actingAs($user);

    Livewire::test('admin.dashboard')
        ->assertSee('Selamat Datang, '.$user->name);
});
