<?php

use App\Models\User;
use Livewire\Livewire;

it('can render', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $component = Livewire::test('admin.dashboard');

    $component->assertSee('Selamat Datang, '.$user->name);
});
