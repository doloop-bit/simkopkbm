<?php

use App\Models\User;
use Livewire\Livewire;

it('can render', function () {
    $this->actingAs(User::factory()->create(['role' => 'admin']));

    $component = Livewire::test('admin.reports');

    $component->assertOk();
});
