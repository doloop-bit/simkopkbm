<?php

declare(strict_types=1);

use App\Models\GalleryPhoto;
use App\Models\SchoolProfile;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
});

test('gallery page loads successfully', function () {
    $response = $this->get('/galeri');

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Public/Gallery', false)
    );
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

test('contact page loads successfully', function () {
    SchoolProfile::factory()->create(['is_active' => true]);

    $response = $this->get('/kontak');

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Public/Contact', false)
    );
});

test('contact page displays school information', function () {
    $schoolProfile = SchoolProfile::factory()->create([
        'is_active' => true,
        'address' => 'Jl. Test No. 123',
        'phone' => '021-1234567',
        'email' => 'test@pkbm.test',
    ]);

    $response = $this->get('/kontak');

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Public/Contact', false)
        ->where('schoolProfile.address', 'Jl. Test No. 123')
        ->where('schoolProfile.phone', '021-1234567')
        ->where('schoolProfile.email', 'test@pkbm.test')
    );
});
