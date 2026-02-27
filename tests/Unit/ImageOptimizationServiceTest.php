<?php

use App\Services\ImageOptimizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    $this->service = app(ImageOptimizationService::class);
});

test('can generate responsive image HTML', function () {
    $imagePaths = [
        'small' => 'test/small.jpg',
        'medium' => 'test/medium.jpg',
        'large' => 'test/large.jpg',
        'small_webp' => 'test/small.webp',
        'medium_webp' => 'test/medium.webp',
        'large_webp' => 'test/large.webp',
    ];

    $html = $this->service->generateResponsiveImage(
        $imagePaths,
        'Test Image',
        'test-class',
        true
    );

    expect($html)
        ->toContain('<picture>')
        ->toContain('type="image/webp"')
        ->toContain('alt="Test Image"')
        ->toContain('class="test-class"')
        ->toContain('loading="lazy"')
        ->toContain('decoding="async"');
});

test('can generate srcset for responsive images', function () {
    $imagePaths = [
        'small' => 'test/small.jpg',
        'medium' => 'test/medium.jpg',
        'large' => 'test/large.jpg',
    ];

    $srcset = $this->service->generateSrcSet($imagePaths);

    expect($srcset)
        ->toContain('400w')
        ->toContain('800w')
        ->toContain('1200w');
});

test('can generate webp srcset', function () {
    $imagePaths = [
        'small_webp' => 'test/small.webp',
        'medium_webp' => 'test/medium.webp',
        'large_webp' => 'test/large.webp',
    ];

    $srcset = $this->service->generateSrcSet($imagePaths, true);

    expect($srcset)
        ->toContain('.webp')
        ->toContain('400w')
        ->toContain('800w')
        ->toContain('1200w');
});

test('can delete image versions', function () {
    // Create fake files
    Storage::disk('public')->put('test/image1.jpg', 'fake content');
    Storage::disk('public')->put('test/image2.webp', 'fake content');

    $imagePaths = [
        'test/image1.jpg',
        'test/image2.webp',
    ];

    // Verify files exist
    expect(Storage::disk('public')->exists('test/image1.jpg'))->toBeTrue();
    expect(Storage::disk('public')->exists('test/image2.webp'))->toBeTrue();

    // Delete using service
    $this->service->deleteImageVersions($imagePaths);

    // Verify files are deleted
    expect(Storage::disk('public')->exists('test/image1.jpg'))->toBeFalse();
    expect(Storage::disk('public')->exists('test/image2.webp'))->toBeFalse();
});
