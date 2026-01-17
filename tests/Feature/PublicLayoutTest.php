<?php

declare(strict_types=1);

use App\Models\SchoolProfile;

beforeEach(function () {
    $this->withoutVite();
});

test('public pages use public layout not admin layout', function () {
    SchoolProfile::factory()->create(['is_active' => true]);

    $response = $this->get('/tentang-kami');

    $response->assertSuccessful();

    // Should have public navigation
    $response->assertSee('Tentang Kami');
    $response->assertSee('Program Pendidikan');
    $response->assertSee('Beranda');

    // Should NOT have admin sidebar elements
    $response->assertDontSee('Dashboard');
    $response->assertDontSee('Siswa');
    $response->assertDontSee('Manajemen PTK');
    $response->assertDontSee('Tahun Ajaran');
});

test('homepage uses public layout', function () {
    SchoolProfile::factory()->create(['is_active' => true]);

    $response = $this->get('/');

    $response->assertSuccessful();

    // Should have public navigation
    $response->assertSee('Beranda');
    $response->assertSee('Tentang Kami');

    // Should NOT have admin elements
    $response->assertDontSee('Dashboard');
    $response->assertDontSee('flux:sidebar');
});

test('contact page uses public layout', function () {
    SchoolProfile::factory()->create(['is_active' => true]);

    $response = $this->get('/kontak');

    $response->assertSuccessful();

    // Should have public navigation
    $response->assertSee('Hubungi Kami');
    $response->assertSee('Kirim Pesan');

    // Should NOT have admin elements
    $response->assertDontSee('Dashboard');
    $response->assertDontSee('Siswa');
});

test('gallery page uses public layout', function () {
    $response = $this->get('/galeri');

    $response->assertSuccessful();

    // Should have public navigation
    $response->assertSee('Galeri Foto');

    // Should NOT have admin elements
    $response->assertDontSee('Dashboard');
    $response->assertDontSee('Manajemen PTK');
});

test('programs page uses public layout', function () {
    $response = $this->get('/program-pendidikan');

    $response->assertSuccessful();

    // Should have public navigation
    $response->assertSee('Program Pendidikan');

    // Should NOT have admin elements
    $response->assertDontSee('Dashboard');
    $response->assertDontSee('Siswa');
});

test('news page uses public layout', function () {
    $response = $this->get('/berita');

    $response->assertSuccessful();

    // Should have public navigation
    $response->assertSee('Berita');

    // Should NOT have admin elements
    $response->assertDontSee('Dashboard');
    $response->assertDontSee('Tahun Ajaran');
});
