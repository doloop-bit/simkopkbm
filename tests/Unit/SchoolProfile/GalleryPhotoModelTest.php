<?php

use App\Models\GalleryPhoto;

describe('GalleryPhoto Model', function () {
    test('can create a gallery photo', function () {
        $photo = GalleryPhoto::create([
            'title' => 'Kegiatan Belajar',
            'caption' => 'Siswa sedang belajar di kelas',
            'category' => 'Kegiatan Belajar',
            'original_path' => 'gallery/original/photo.jpg',
            'thumbnail_path' => 'gallery/thumbnails/photo.jpg',
            'web_path' => 'gallery/web/photo.jpg',
            'order' => 1,
            'is_published' => true,
        ]);

        expect($photo)->toBeInstanceOf(GalleryPhoto::class)
            ->and($photo->title)->toBe('Kegiatan Belajar')
            ->and($photo->category)->toBe('Kegiatan Belajar')
            ->and($photo->is_published)->toBeTrue();
    });

    test('casts is_published to boolean', function () {
        $photo = GalleryPhoto::create([
            'title' => 'Test Photo',
            'category' => 'Test',
            'original_path' => 'gallery/original/test.jpg',
            'thumbnail_path' => 'gallery/thumbnails/test.jpg',
            'web_path' => 'gallery/web/test.jpg',
            'is_published' => 1,
        ]);

        expect($photo->is_published)->toBeTrue()
            ->and($photo->is_published)->toBeBool();
    });

    test('casts order to integer', function () {
        $photo = GalleryPhoto::create([
            'title' => 'Test Photo',
            'category' => 'Test',
            'original_path' => 'gallery/original/test.jpg',
            'thumbnail_path' => 'gallery/thumbnails/test.jpg',
            'web_path' => 'gallery/web/test.jpg',
            'order' => '5',
        ]);

        expect($photo->order)->toBe(5)
            ->and($photo->order)->toBeInt();
    });

    test('published scope filters published photos', function () {
        // Create published photo
        GalleryPhoto::create([
            'title' => 'Published Photo',
            'category' => 'Test',
            'original_path' => 'gallery/original/published.jpg',
            'thumbnail_path' => 'gallery/thumbnails/published.jpg',
            'web_path' => 'gallery/web/published.jpg',
            'is_published' => true,
        ]);

        // Create unpublished photo
        GalleryPhoto::create([
            'title' => 'Unpublished Photo',
            'category' => 'Test',
            'original_path' => 'gallery/original/unpublished.jpg',
            'thumbnail_path' => 'gallery/thumbnails/unpublished.jpg',
            'web_path' => 'gallery/web/unpublished.jpg',
            'is_published' => false,
        ]);

        $publishedPhotos = GalleryPhoto::published()->get();

        expect($publishedPhotos)->toHaveCount(1)
            ->and($publishedPhotos->first()->title)->toBe('Published Photo');
    });

    test('byCategory scope filters photos by category', function () {
        GalleryPhoto::create([
            'title' => 'Sports Photo',
            'category' => 'Olahraga',
            'original_path' => 'gallery/original/sports.jpg',
            'thumbnail_path' => 'gallery/thumbnails/sports.jpg',
            'web_path' => 'gallery/web/sports.jpg',
        ]);

        GalleryPhoto::create([
            'title' => 'Learning Photo',
            'category' => 'Kegiatan Belajar',
            'original_path' => 'gallery/original/learning.jpg',
            'thumbnail_path' => 'gallery/thumbnails/learning.jpg',
            'web_path' => 'gallery/web/learning.jpg',
        ]);

        GalleryPhoto::create([
            'title' => 'Another Sports Photo',
            'category' => 'Olahraga',
            'original_path' => 'gallery/original/sports2.jpg',
            'thumbnail_path' => 'gallery/thumbnails/sports2.jpg',
            'web_path' => 'gallery/web/sports2.jpg',
        ]);

        $sportsPhotos = GalleryPhoto::byCategory('Olahraga')->get();

        expect($sportsPhotos)->toHaveCount(2)
            ->and($sportsPhotos->every(fn ($photo) => $photo->category === 'Olahraga'))->toBeTrue();
    });

    test('ordered scope orders photos by order field then created_at descending', function () {
        // Create photos with different order values and timestamps
        // Photo with order 2 (should be last)
        $photo1 = GalleryPhoto::create([
            'title' => 'Photo 1',
            'category' => 'Test',
            'original_path' => 'gallery/original/1.jpg',
            'thumbnail_path' => 'gallery/thumbnails/1.jpg',
            'web_path' => 'gallery/web/1.jpg',
            'order' => 2,
        ]);
        $photo1->created_at = now()->subDays(3);
        $photo1->save();

        // Photo with order 1, older (should be second)
        $photo2 = GalleryPhoto::create([
            'title' => 'Photo 2',
            'category' => 'Test',
            'original_path' => 'gallery/original/2.jpg',
            'thumbnail_path' => 'gallery/thumbnails/2.jpg',
            'web_path' => 'gallery/web/2.jpg',
            'order' => 1,
        ]);
        $photo2->created_at = now()->subDays(2);
        $photo2->save();

        // Photo with order 1, newer (should be first)
        $photo3 = GalleryPhoto::create([
            'title' => 'Photo 3',
            'category' => 'Test',
            'original_path' => 'gallery/original/3.jpg',
            'thumbnail_path' => 'gallery/thumbnails/3.jpg',
            'web_path' => 'gallery/web/3.jpg',
            'order' => 1,
        ]);
        $photo3->created_at = now()->subDay();
        $photo3->save();

        $photos = GalleryPhoto::ordered()->get();

        expect($photos)->toHaveCount(3)
            ->and($photos->get(0)->title)->toBe('Photo 3') // order 1, newest
            ->and($photos->get(1)->title)->toBe('Photo 2') // order 1, older
            ->and($photos->get(2)->title)->toBe('Photo 1'); // order 2
    });

    test('scopes can be combined', function () {
        // Published sports photo
        GalleryPhoto::create([
            'title' => 'Published Sports',
            'category' => 'Olahraga',
            'original_path' => 'gallery/original/sports1.jpg',
            'thumbnail_path' => 'gallery/thumbnails/sports1.jpg',
            'web_path' => 'gallery/web/sports1.jpg',
            'order' => 1,
            'is_published' => true,
        ]);

        // Unpublished sports photo
        GalleryPhoto::create([
            'title' => 'Unpublished Sports',
            'category' => 'Olahraga',
            'original_path' => 'gallery/original/sports2.jpg',
            'thumbnail_path' => 'gallery/thumbnails/sports2.jpg',
            'web_path' => 'gallery/web/sports2.jpg',
            'order' => 2,
            'is_published' => false,
        ]);

        // Published learning photo
        GalleryPhoto::create([
            'title' => 'Published Learning',
            'category' => 'Kegiatan Belajar',
            'original_path' => 'gallery/original/learning.jpg',
            'thumbnail_path' => 'gallery/thumbnails/learning.jpg',
            'web_path' => 'gallery/web/learning.jpg',
            'order' => 3,
            'is_published' => true,
        ]);

        $photos = GalleryPhoto::published()->byCategory('Olahraga')->ordered()->get();

        expect($photos)->toHaveCount(1)
            ->and($photos->first()->title)->toBe('Published Sports');
    });

    test('caption is optional', function () {
        $photo = GalleryPhoto::create([
            'title' => 'Photo Without Caption',
            'category' => 'Test',
            'original_path' => 'gallery/original/test.jpg',
            'thumbnail_path' => 'gallery/thumbnails/test.jpg',
            'web_path' => 'gallery/web/test.jpg',
        ]);

        expect($photo->caption)->toBeNull();
    });

    test('default order is 0', function () {
        $photo = GalleryPhoto::create([
            'title' => 'Test Photo',
            'category' => 'Test',
            'original_path' => 'gallery/original/test.jpg',
            'thumbnail_path' => 'gallery/thumbnails/test.jpg',
            'web_path' => 'gallery/web/test.jpg',
        ]);

        expect($photo->order)->toBe(0);
    });

    test('default is_published is true', function () {
        $photo = GalleryPhoto::create([
            'title' => 'Test Photo',
            'category' => 'Test',
            'original_path' => 'gallery/original/test.jpg',
            'thumbnail_path' => 'gallery/thumbnails/test.jpg',
            'web_path' => 'gallery/web/test.jpg',
        ]);

        expect($photo->is_published)->toBeTrue();
    });

    test('factory creates valid photo', function () {
        $photo = GalleryPhoto::factory()->create();

        expect($photo)->toBeInstanceOf(GalleryPhoto::class)
            ->and($photo->title)->not->toBeEmpty()
            ->and($photo->category)->not->toBeEmpty()
            ->and($photo->original_path)->not->toBeEmpty()
            ->and($photo->thumbnail_path)->not->toBeEmpty()
            ->and($photo->web_path)->not->toBeEmpty();
    });

    test('factory unpublished state creates unpublished photo', function () {
        $photo = GalleryPhoto::factory()->unpublished()->create();

        expect($photo->is_published)->toBeFalse();
    });

    test('factory category state creates photo with specific category', function () {
        $photo = GalleryPhoto::factory()->category('Wisuda')->create();

        expect($photo->category)->toBe('Wisuda');
    });

    test('can create photo with all fields', function () {
        $photo = GalleryPhoto::create([
            'title' => 'Complete Photo',
            'caption' => 'This is a complete photo with all fields',
            'category' => 'Kegiatan Belajar',
            'original_path' => 'gallery/original/complete.jpg',
            'thumbnail_path' => 'gallery/thumbnails/complete.jpg',
            'web_path' => 'gallery/web/complete.jpg',
            'order' => 10,
            'is_published' => true,
        ]);

        expect($photo->title)->toBe('Complete Photo')
            ->and($photo->caption)->toBe('This is a complete photo with all fields')
            ->and($photo->category)->toBe('Kegiatan Belajar')
            ->and($photo->original_path)->toBe('gallery/original/complete.jpg')
            ->and($photo->thumbnail_path)->toBe('gallery/thumbnails/complete.jpg')
            ->and($photo->web_path)->toBe('gallery/web/complete.jpg')
            ->and($photo->order)->toBe(10)
            ->and($photo->is_published)->toBeTrue();
    });

    test('multiple photos can have same order value', function () {
        GalleryPhoto::create([
            'title' => 'Photo 1',
            'category' => 'Test',
            'original_path' => 'gallery/original/1.jpg',
            'thumbnail_path' => 'gallery/thumbnails/1.jpg',
            'web_path' => 'gallery/web/1.jpg',
            'order' => 5,
        ]);

        GalleryPhoto::create([
            'title' => 'Photo 2',
            'category' => 'Test',
            'original_path' => 'gallery/original/2.jpg',
            'thumbnail_path' => 'gallery/thumbnails/2.jpg',
            'web_path' => 'gallery/web/2.jpg',
            'order' => 5,
        ]);

        $photos = GalleryPhoto::where('order', 5)->get();

        expect($photos)->toHaveCount(2);
    });

    test('byCategory scope is case sensitive', function () {
        GalleryPhoto::create([
            'title' => 'Photo 1',
            'category' => 'Olahraga',
            'original_path' => 'gallery/original/1.jpg',
            'thumbnail_path' => 'gallery/thumbnails/1.jpg',
            'web_path' => 'gallery/web/1.jpg',
        ]);

        $photos = GalleryPhoto::byCategory('olahraga')->get();

        expect($photos)->toHaveCount(0);
    });

    test('ordered scope with same order uses created_at as tiebreaker', function () {
        $older = GalleryPhoto::create([
            'title' => 'Older Photo',
            'category' => 'Test',
            'original_path' => 'gallery/original/older.jpg',
            'thumbnail_path' => 'gallery/thumbnails/older.jpg',
            'web_path' => 'gallery/web/older.jpg',
            'order' => 1,
        ]);
        $older->created_at = now()->subDays(2);
        $older->save();

        $newer = GalleryPhoto::create([
            'title' => 'Newer Photo',
            'category' => 'Test',
            'original_path' => 'gallery/original/newer.jpg',
            'thumbnail_path' => 'gallery/thumbnails/newer.jpg',
            'web_path' => 'gallery/web/newer.jpg',
            'order' => 1,
        ]);
        $newer->created_at = now()->subDay();
        $newer->save();

        $photos = GalleryPhoto::ordered()->get();

        expect($photos->first()->title)->toBe('Newer Photo')
            ->and($photos->last()->title)->toBe('Older Photo');
    });
});
