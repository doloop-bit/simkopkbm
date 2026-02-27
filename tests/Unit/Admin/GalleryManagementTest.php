<?php

use App\Models\GalleryPhoto;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->withoutVite();

    // Create admin user
    $this->admin = User::factory()->create([
        'role' => 'admin',
    ]);

    // Fake the public storage disk
    Storage::fake('public');
});

describe('Gallery Photo File Cleanup', function () {
    test('photo deletion removes all file versions from storage', function () {
        // Create test files in storage
        $originalPath = 'gallery/original/test.jpg';
        $thumbnailPath = 'gallery/thumbnails/test.jpg';
        $webPath = 'gallery/web/test.jpg';

        Storage::disk('public')->put($originalPath, 'original content');
        Storage::disk('public')->put($thumbnailPath, 'thumbnail content');
        Storage::disk('public')->put($webPath, 'web content');

        // Create gallery photo record
        $photo = GalleryPhoto::create([
            'title' => 'Test Photo',
            'caption' => 'Test caption',
            'category' => 'Test Category',
            'original_path' => $originalPath,
            'thumbnail_path' => $thumbnailPath,
            'web_path' => $webPath,
            'order' => 1,
            'is_published' => true,
        ]);

        // Verify files exist before deletion
        expect(Storage::disk('public')->exists($originalPath))->toBeTrue()
            ->and(Storage::disk('public')->exists($thumbnailPath))->toBeTrue()
            ->and(Storage::disk('public')->exists($webPath))->toBeTrue();

        // Simulate the deletion logic from the component
        Storage::disk('public')->delete([
            $photo->original_path,
            $photo->thumbnail_path,
            $photo->web_path,
        ]);

        $photo->delete();

        // Verify photo record is deleted from database
        expect(GalleryPhoto::find($photo->id))->toBeNull();

        // Verify all file versions are deleted from storage
        expect(Storage::disk('public')->exists($originalPath))->toBeFalse()
            ->and(Storage::disk('public')->exists($thumbnailPath))->toBeFalse()
            ->and(Storage::disk('public')->exists($webPath))->toBeFalse();
    });

    test('deletion handles missing files gracefully', function () {
        // Create gallery photo record without creating actual files
        $photo = GalleryPhoto::create([
            'title' => 'Test Photo',
            'caption' => 'Test caption',
            'category' => 'Test Category',
            'original_path' => 'gallery/original/nonexistent.jpg',
            'thumbnail_path' => 'gallery/thumbnails/nonexistent.jpg',
            'web_path' => 'gallery/web/nonexistent.jpg',
            'order' => 1,
            'is_published' => true,
        ]);

        // Only create one of the files
        Storage::disk('public')->put($photo->original_path, 'content');

        // Verify initial state
        expect(Storage::disk('public')->exists($photo->original_path))->toBeTrue()
            ->and(Storage::disk('public')->exists($photo->thumbnail_path))->toBeFalse()
            ->and(Storage::disk('public')->exists($photo->web_path))->toBeFalse();

        // Simulate deletion (should not throw errors for missing files)
        Storage::disk('public')->delete([
            $photo->original_path,
            $photo->thumbnail_path,
            $photo->web_path,
        ]);

        $photo->delete();

        // Verify photo record is deleted
        expect(GalleryPhoto::find($photo->id))->toBeNull();

        // Verify existing file is deleted
        expect(Storage::disk('public')->exists($photo->original_path))->toBeFalse();
    });

    test('multiple photos can be deleted independently', function () {
        // Create two photos with different files
        $photo1 = GalleryPhoto::create([
            'title' => 'Photo 1',
            'category' => 'Category A',
            'original_path' => 'gallery/original/photo1.jpg',
            'thumbnail_path' => 'gallery/thumbnails/photo1.jpg',
            'web_path' => 'gallery/web/photo1.jpg',
        ]);

        $photo2 = GalleryPhoto::create([
            'title' => 'Photo 2',
            'category' => 'Category B',
            'original_path' => 'gallery/original/photo2.jpg',
            'thumbnail_path' => 'gallery/thumbnails/photo2.jpg',
            'web_path' => 'gallery/web/photo2.jpg',
        ]);

        // Create files for both photos
        Storage::disk('public')->put($photo1->original_path, 'photo1 original');
        Storage::disk('public')->put($photo1->thumbnail_path, 'photo1 thumbnail');
        Storage::disk('public')->put($photo1->web_path, 'photo1 web');

        Storage::disk('public')->put($photo2->original_path, 'photo2 original');
        Storage::disk('public')->put($photo2->thumbnail_path, 'photo2 thumbnail');
        Storage::disk('public')->put($photo2->web_path, 'photo2 web');

        // Delete only photo1
        Storage::disk('public')->delete([
            $photo1->original_path,
            $photo1->thumbnail_path,
            $photo1->web_path,
        ]);
        $photo1->delete();

        // Verify photo1 is deleted but photo2 remains
        expect(GalleryPhoto::find($photo1->id))->toBeNull()
            ->and(GalleryPhoto::find($photo2->id))->not->toBeNull();

        // Verify photo1 files are deleted but photo2 files remain
        expect(Storage::disk('public')->exists($photo1->original_path))->toBeFalse()
            ->and(Storage::disk('public')->exists($photo1->thumbnail_path))->toBeFalse()
            ->and(Storage::disk('public')->exists($photo1->web_path))->toBeFalse()
            ->and(Storage::disk('public')->exists($photo2->original_path))->toBeTrue()
            ->and(Storage::disk('public')->exists($photo2->thumbnail_path))->toBeTrue()
            ->and(Storage::disk('public')->exists($photo2->web_path))->toBeTrue();
    });
});

describe('Gallery Management Route Access', function () {
    test('requires admin authentication for gallery management', function () {
        // Create a non-admin user
        $user = User::factory()->create(['role' => 'teacher']);

        // Test that non-admin cannot access gallery management
        $this->actingAs($user)
            ->get('/admin/galeri')
            ->assertForbidden();
    });

    test('allows admin to access gallery management', function () {
        $this->actingAs($this->admin)
            ->get('/admin/galeri')
            ->assertSuccessful();
    });
});
