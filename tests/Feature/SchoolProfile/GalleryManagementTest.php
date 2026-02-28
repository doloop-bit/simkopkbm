<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->withoutVite();
    Storage::fake('public');
    $this->admin = User::factory()->create(['role' => 'admin']);
});

test('admin can access gallery management page', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.gallery.index'))
        ->assertOk()
        ->assertSeeLivewire('admin.web-content.gallery.index');
});

test('non-admin cannot access gallery management page', function () {
    $user = User::factory()->create(['role' => 'teacher']);

    $this->actingAs($user)
        ->get(route('admin.gallery.index'))
        ->assertForbidden();
});
