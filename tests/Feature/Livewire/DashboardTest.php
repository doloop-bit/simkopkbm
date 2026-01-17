<?php

use App\Models\User;
use Livewire\Volt\Volt;

it('can render', function () {
    $user = User::factory()->create();
    
    $this->actingAs($user);
    
    $component = Volt::test('dashboard');

    $component->assertSee('Selamat Datang, ' . $user->name);
});
