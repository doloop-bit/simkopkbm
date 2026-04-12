<?php

declare(strict_types=1);

use App\Models\User;
use Livewire\Livewire;

it('can render ptk index for admin', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    Livewire::actingAs($admin)
        ->test('admin.data-master.ptk.index')
        ->assertOk();
});
