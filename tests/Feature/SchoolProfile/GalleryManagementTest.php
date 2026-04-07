<?php

declare(strict_types=1);

use App\Models\GalleryPhoto;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->withoutVite();
    Storage::fake('public');
    $this->admin = User::factory()->create(['role' => 'admin']);
});

test('admin can access gallery management page', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.gallery.index'))
        ->assertOk()
        ->assertSeeLivewire('admin.web-content.gallery.index');
});

test('non-admin cannot access gallery management page', function () {
    $user = User::factory()->create(['role' => 'teacher']);

    $this->actingAs($user)
        ->get(route('admin.gallery.index'))
        ->assertForbidden();
});

test('admin can upload photos to gallery', function () {
    $this->actingAs($this->admin);

    $photo1 = UploadedFile::fake()->image('photo1.jpg');
    $photo2 = UploadedFile::fake()->image('photo2.jpg');

    Livewire::test('admin.web-content.gallery.index')
        ->set('photos', [$photo1, $photo2])
        ->set('category', 'Kegiatan')
        ->set('caption', 'Foto Kegiatan Sekolah')
        ->call('uploadPhotos')
        ->assertHasNoErrors();

    expect(GalleryPhoto::count())->toBe(2);
    $photo = GalleryPhoto::first();
    expect($photo->category)->toBe('Kegiatan');
    expect($photo->caption)->toBe('Foto Kegiatan Sekolah');
});

test('admin can delete a photo', function () {
    $this->actingAs($this->admin);

    $photo = GalleryPhoto::factory()->create([
        'original_path' => 'gallery/photo.jpg',
        'thumbnail_path' => 'gallery/thumb.jpg',
    ]);

    Storage::disk('public')->put('gallery/photo.jpg', 'content');
    Storage::disk('public')->put('gallery/thumb.jpg', 'content');

    Livewire::test('admin.web-content.gallery.index')
        ->call('deletePhoto', $photo->id)
        ->assertHasNoErrors();

    expect(GalleryPhoto::find($photo->id))->toBeNull();
    Storage::disk('public')->assertMissing('gallery/photo.jpg');
    Storage::disk('public')->assertMissing('gallery/thumb.jpg');
});

test('admin can edit photo details', function () {
    $this->actingAs($this->admin);

    $photo = GalleryPhoto::factory()->create([
        'category' => 'Lama',
        'caption' => 'Keterangan Lama',
    ]);

    Livewire::test('admin.web-content.gallery.index')
        ->call('startEdit', $photo->id)
        ->set('editCategory', 'Baru')
        ->set('editCaption', 'Keterangan Baru')
        ->call('saveEdit')
        ->assertHasNoErrors();

    $photo->refresh();
    expect($photo->category)->toBe('Baru');
    expect($photo->caption)->toBe('Keterangan Baru');
});

test('admin can toggle photo publication status', function () {
    $this->actingAs($this->admin);

    $photo = GalleryPhoto::factory()->create(['is_published' => true]);

    Livewire::test('admin.web-content.gallery.index')
        ->call('togglePublish', $photo->id);

    $photo->refresh();
    expect($photo->is_published)->toBeFalse();

    Livewire::test('admin.web-content.gallery.index')
        ->call('togglePublish', $photo->id);

    $photo->refresh();
    expect($photo->is_published)->toBeTrue();
});
