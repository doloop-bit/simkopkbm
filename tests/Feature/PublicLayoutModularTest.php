<?php

declare(strict_types=1);

use function Pest\Laravel\get;

beforeEach(function () {
    $this->withoutVite();
});

test('public layout uses modular navbar component', function () {
    $response = get('/');

    $response->assertStatus(200);

    // Check that navbar content is present (from navbar component)
    $response->assertSee('Pusat Kegiatan Belajar Masyarakat');
    $response->assertSee('Beranda');
    $response->assertSee('Tentang Kami');
    $response->assertSee('Program');
    $response->assertSee('Berita');
    $response->assertSee('Galeri');
    $response->assertSee('Kontak');
});

test('public layout uses modular footer component', function () {
    $response = get('/');

    $response->assertStatus(200);

    // Check that footer content is present (from footer component)
    $response->assertSee('Tautan Cepat');
    $response->assertSee('Hubungi Kami');
    $response->assertSee('info@pkbm.test');
    $response->assertSee('(021) 1234-5678');
    $response->assertSee('Jakarta, Indonesia');
    $response->assertSee('Semua hak dilindungi');
});

test('public layout maintains proper structure', function () {
    $response = get('/');

    $response->assertStatus(200);

    // Check that the layout structure is maintained
    $response->assertSee('<nav class="bg-white/80 backdrop-blur-md', false);
    $response->assertSee('<main class="min-h-screen">', false);
    $response->assertSee('<footer class="bg-gradient-to-br from-green-900', false);
});

test('public layout includes all necessary meta tags', function () {
    $response = get('/');

    $response->assertStatus(200);

    // Check that SEO meta tags are present
    $response->assertSee('<meta name="description"', false);
    $response->assertSee('<meta name="keywords"', false);
    $response->assertSee('<meta property="og:title"', false);
    $response->assertSee('<meta name="twitter:card"', false);
});
