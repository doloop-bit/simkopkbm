<?php

use App\Models\User;
use Livewire\Volt\Volt;

use function Pest\Laravel\actingAs;

it('can render', function () {
    $user = User::factory()->create(['role' => 'admin']);

    actingAs($user);

    $component = Volt::test('academic.teacher-assignments');

    $component->assertSee('Penugasan Guru');
})->skip('Known issue: Flux modal compatibility with Livewire 4 Blaze compiler');
