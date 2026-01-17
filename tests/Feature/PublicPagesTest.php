<?php

declare(strict_types=1);

use App\Models\GalleryPhoto;
use App\Models\SchoolProfile;

beforeEach(function () {
    $this->withoutVite();
});

test('gallery page loads successfully', function () {
    $response = $this->get('/galeri');

    $response->assertSuccessful();
    $response->assertSee('Galeri Foto');
    $response->assertSee('Dokumentasi kegiatan dan fasilitas PKBM');
});

test('gallery page displays photos when available', function () {
    $photos = GalleryPhoto::factory()->count(3)->create([
        'is_published' => true,
        'title' => 'Test Photo',
        'category' => 'Kegiatan',
    ]);

    $response = $this->get('/galeri');

    $response->assertSuccessful();
    $response->assertSee('Test Photo');
    $response->assertSee('Kegiatan');
});

test('gallery page shows empty state when no photos', function () {
    $response = $this->get('/galeri');

    $response->assertSuccessful();
    $response->assertSee('Belum Ada Foto');
    $response->assertSee('Galeri foto belum tersedia saat ini');
});

test('contact page loads successfully', function () {
    SchoolProfile::factory()->create(['is_active' => true]);

    $response = $this->get('/kontak');

    $response->assertSuccessful();
    $response->assertSee('Hubungi Kami');
    $response->assertSee('Kirim Pesan');
});

test('contact page displays school information', function () {
    $schoolProfile = SchoolProfile::factory()->create([
        'is_active' => true,
        'address' => 'Jl. Test No. 123',
        'phone' => '021-1234567',
        'email' => 'test@pkbm.test',
    ]);

    $response = $this->get('/kontak');

    $response->assertSuccessful();
    $response->assertSee('Jl. Test No. 123');
    $response->assertSee('021-1234567');
    $response->assertSee('test@pkbm.test');
});

test('contact form can be submitted successfully', function () {
    SchoolProfile::factory()->create(['is_active' => true]);

    Livewire::test('public.contact')
        ->set('name', 'John Doe')
        ->set('email', 'john@example.com')
        ->set('phone', '08123456789')
        ->set('subject', 'Test Subject')
        ->set('message', 'This is a test message.')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('showSuccess', true);

    // Check if inquiry was saved to database
    $this->assertDatabaseHas('contact_inquiries', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'subject' => 'Test Subject',
        'is_read' => false,
    ]);
});
