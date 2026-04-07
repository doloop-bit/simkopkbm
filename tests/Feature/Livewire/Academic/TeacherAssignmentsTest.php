<?php

use App\Models\User;
use Livewire\Livewire;

it('can render', function () {
    $user = User::factory()->create(['role' => 'admin']);

    $this->actingAs($user);

    $component = Livewire::test('admin.academic.teacher-assignments');

    $component->assertSee('Penugasan Guru');
});
