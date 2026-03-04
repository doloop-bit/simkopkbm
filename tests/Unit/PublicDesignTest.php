<?php

declare(strict_types=1);

use App\Models\SchoolProfile;

beforeEach(function () {
    $this->withoutVite();
});

test('homepage has modern green design elements', function () {
    SchoolProfile::factory()->create(['is_active' => true]);

    $response = $this->get('/');

    $response->assertSuccessful();

    // Should have green gradient backgrounds
    $response->assertSee('from-green-600');
    $response->assertSee('to-emerald-800');

    // Should have modern styling
    $response->assertSee('rounded-xl');
    $response->assertSee('shadow-lg');

    // Should have Inter font
    $response->assertSee('Inter');
});

test('contact page has modern green design', function () {
    SchoolProfile::factory()->create(['is_active' => true]);

    $response = $this->get('/kontak');

    $response->assertSuccessful();

    // Should have green gradient header
    $response->assertSee('from-green-600');
    $response->assertSee('to-emerald-800');

    // Should have modern form styling
    $response->assertSee('rounded-xl');
    $response->assertSee('focus:ring-green-500');
});

test('gallery page has modern green design', function () {
    $response = $this->get('/galeri');

    $response->assertSuccessful();

    // Should have green gradient header
    $response->assertSee('from-green-600');
    $response->assertSee('to-emerald-800');

    // Should have modern button styling
    $response->assertSee('from-green-600 to-emerald-600');
});

test('navigation has modern styling', function () {
    $response = $this->get('/');

    $response->assertSuccessful();

    // Should have modern navigation
    $response->assertSee('backdrop-blur-md');
    $response->assertSee('rounded-xl');
    $response->assertSee('hover:bg-green-50');
});
