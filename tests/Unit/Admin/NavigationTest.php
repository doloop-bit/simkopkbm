<?php

declare(strict_types=1);

use App\Models\User;

beforeEach(function () {
    $this->withoutVite();
});

test('admin navigation includes CMS links', function () {
    $user = User::factory()->create([
        'role' => 'admin',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Profil Sekolah')
        ->assertSee('Berita & Artikel')
        ->assertSee('Galeri')
        ->assertSee('Program Pendidikan')
        ->assertSee('Pesan Kontak');
});

test('admin can access school profile management', function () {
    $user = User::factory()->create([
        'role' => 'admin',
    ]);

    $this->actingAs($user)
        ->get(route('admin.school-profile.edit'))
        ->assertOk();
});

test('admin can access news management', function () {
    $user = User::factory()->create([
        'role' => 'admin',
    ]);

    $this->actingAs($user)
        ->get(route('admin.news.index'))
        ->assertOk();
});

test('admin can access gallery management', function () {
    $user = User::factory()->create([
        'role' => 'admin',
    ]);

    $this->actingAs($user)
        ->get(route('admin.gallery.index'))
        ->assertOk();
});

test('admin can access programs management', function () {
    $user = User::factory()->create([
        'role' => 'admin',
    ]);

    $this->actingAs($user)
        ->get(route('admin.programs.index'))
        ->assertOk();
});

test('admin can access contact inquiries management', function () {
    $user = User::factory()->create([
        'role' => 'admin',
    ]);

    $this->actingAs($user)
        ->get(route('admin.contact-inquiries.index'))
        ->assertOk();
});
