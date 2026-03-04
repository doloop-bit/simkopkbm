<?php

declare(strict_types=1);

use function Pest\Laravel\get;

test('homepage dropdown navigation appears above hero section', function () {
    $response = get('/');

    $response->assertStatus(200);

    // Check that the dropdown container has z-[9999] class
    $response->assertSee('z-[9999]');

    // Check that the hero section exists (which could interfere with dropdown)
    $response->assertSee('Hero Section');
    $response->assertSee('relative bg-gradient-to-br from-green-600');

    // Check that the dropdown menu structure is present
    $response->assertSee('Tentang Kami');
    $response->assertSee('Profil Sekolah');
    $response->assertSee('Struktur Organisasi');
    $response->assertSee('Fasilitas');
});

test('berita page does not have hero section interference', function () {
    $response = get('/berita');

    $response->assertStatus(200);

    // The dropdown should still work with the same z-index
    $response->assertSee('z-[9999]');

    // Check that berita page has different header structure than homepage
    $response->assertSee('from-red-600 to-red-800'); // berita page header
});
