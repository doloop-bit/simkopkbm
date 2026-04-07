<?php

use App\Models\User;
use Livewire\Livewire;

it('can render', function () {
    $this->actingAs(User::factory()->create(['role' => 'admin']));

    $component = Livewire::test('admin.data-master.ptk.index');

    $component->assertOk();
});
