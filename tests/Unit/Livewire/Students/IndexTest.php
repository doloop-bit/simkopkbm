<?php

declare(strict_types=1);

use Livewire\Livewire;
use App\Models\User;

it('can render students index for admin', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    Livewire::actingAs($admin)
        ->test('admin.data-master.students.index')
        ->assertOk();
});
