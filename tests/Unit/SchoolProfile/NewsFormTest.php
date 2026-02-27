<?php

declare(strict_types=1);

use App\Models\NewsArticle;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->withoutVite();
    $this->user = User::factory()->create(['role' => 'admin']);
    actingAs($this->user);
});

test('admin can access news create form', function () {
    Volt::test('admin.news.form')
        ->assertOk()
        ->assertSee('Tambah Berita')
        ->assertSee('Judul Artikel');
});

test('admin can create news article', function () {
    Volt::test('admin.news.form')
        ->set('title', 'Artikel Berita Baru')
        ->set('content', 'Ini adalah konten artikel berita yang baru.')
        ->set('status', 'draft')
        ->set('publishedAt', now()->format('Y-m-d'))
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.news.index'));

    expect(NewsArticle::where('title', 'Artikel Berita Baru')->exists())->toBeTrue();
});

test('admin can create news article with featured image', function () {
    Storage::fake('public');

    $file = UploadedFile::fake()->image('featured.jpg', 1200, 800);

    Volt::test('admin.news.form')
        ->set('title', 'Artikel dengan Gambar')
        ->set('content', 'Konten artikel dengan gambar unggulan.')
        ->set('status', 'published')
        ->set('publishedAt', now()->format('Y-m-d'))
        ->set('featuredImage', $file)
        ->call('save')
        ->assertHasNoErrors();

    $article = NewsArticle::where('title', 'Artikel dengan Gambar')->first();
    expect($article)->not->toBeNull();
    expect($article->featured_image_path)->not->toBeNull();
    Storage::disk('public')->assertExists($article->featured_image_path);
});

test('news article form validates required fields', function () {
    Volt::test('admin.news.form')
        ->set('title', '')
        ->set('content', '')
        ->set('publishedAt', '')
        ->call('save')
        ->assertHasErrors(['title', 'content', 'publishedAt']);
});

test('news article form generates unique slug', function () {
    // Create first article
    Volt::test('admin.news.form')
        ->set('title', 'Artikel Sama')
        ->set('content', 'Konten pertama')
        ->set('status', 'draft')
        ->set('publishedAt', now()->format('Y-m-d'))
        ->call('save')
        ->assertHasNoErrors();

    // Create second article with same title
    Volt::test('admin.news.form')
        ->set('title', 'Artikel Sama')
        ->set('content', 'Konten kedua')
        ->set('status', 'draft')
        ->set('publishedAt', now()->format('Y-m-d'))
        ->call('save')
        ->assertHasNoErrors();

    $articles = NewsArticle::where('title', 'Artikel Sama')->get();
    expect($articles)->toHaveCount(2);
    expect($articles[0]->slug)->toBe('artikel-sama');
    expect($articles[1]->slug)->toBe('artikel-sama-1');
});

test('admin can edit existing news article', function () {
    $article = NewsArticle::factory()->create([
        'title' => 'Artikel Lama',
        'content' => 'Konten lama',
        'status' => 'draft',
    ]);

    Volt::test('admin.news.form', ['id' => $article->id])
        ->assertSet('title', 'Artikel Lama')
        ->assertSet('content', 'Konten lama')
        ->set('title', 'Artikel Diperbarui')
        ->set('content', 'Konten diperbarui')
        ->call('save')
        ->assertHasNoErrors();

    $article->refresh();
    expect($article->title)->toBe('Artikel Diperbarui');
    expect($article->content)->toBe('Konten diperbarui');
});

test('admin can remove featured image from article', function () {
    Storage::fake('public');

    $file = UploadedFile::fake()->image('featured.jpg');
    $path = $file->store('news', 'public');

    $article = NewsArticle::factory()->create([
        'featured_image_path' => $path,
    ]);

    Storage::disk('public')->assertExists($path);

    Volt::test('admin.news.form', ['id' => $article->id])
        ->call('removeFeaturedImage')
        ->assertHasNoErrors();

    $article->refresh();
    expect($article->featured_image_path)->toBeNull();
    Storage::disk('public')->assertMissing($path);
});

test('news article form auto-generates excerpt if not provided', function () {
    $longContent = str_repeat('Lorem ipsum dolor sit amet. ', 50);

    Volt::test('admin.news.form')
        ->set('title', 'Artikel Tanpa Ringkasan')
        ->set('content', $longContent)
        ->set('status', 'draft')
        ->set('publishedAt', now()->format('Y-m-d'))
        ->call('save')
        ->assertHasNoErrors();

    $article = NewsArticle::where('title', 'Artikel Tanpa Ringkasan')->first();
    expect($article->excerpt)->not->toBeNull();
    expect(strlen($article->excerpt))->toBeLessThanOrEqual(203); // 200 + "..."
});

test('news article form validates image file type', function () {
    Storage::fake('public');

    $file = UploadedFile::fake()->create('document.pdf', 1000);

    Volt::test('admin.news.form')
        ->set('title', 'Artikel Test')
        ->set('content', 'Konten test')
        ->set('status', 'draft')
        ->set('publishedAt', now()->format('Y-m-d'))
        ->set('featuredImage', $file)
        ->call('save')
        ->assertHasErrors(['featuredImage']);
});

test('news article form validates image file size', function () {
    Storage::fake('public');

    // Create a file larger than 5MB (5120KB)
    $file = UploadedFile::fake()->image('large.jpg')->size(6000);

    Volt::test('admin.news.form')
        ->set('title', 'Artikel Test')
        ->set('content', 'Konten test')
        ->set('status', 'draft')
        ->set('publishedAt', now()->format('Y-m-d'))
        ->set('featuredImage', $file)
        ->call('save')
        ->assertHasErrors(['featuredImage']);
});

test('news article form sets author to current user', function () {
    Volt::test('admin.news.form')
        ->set('title', 'Artikel Baru')
        ->set('content', 'Konten artikel')
        ->set('status', 'draft')
        ->set('publishedAt', now()->format('Y-m-d'))
        ->call('save')
        ->assertHasNoErrors();

    $article = NewsArticle::where('title', 'Artikel Baru')->first();
    expect($article->author_id)->toBe($this->user->id);
});

test('news article form auto-generates meta title and description if not provided', function () {
    Volt::test('admin.news.form')
        ->set('title', 'Artikel SEO')
        ->set('content', 'Ini adalah konten artikel yang akan digunakan untuk meta description.')
        ->set('status', 'draft')
        ->set('publishedAt', now()->format('Y-m-d'))
        ->call('save')
        ->assertHasNoErrors();

    $article = NewsArticle::where('title', 'Artikel SEO')->first();
    expect($article->meta_title)->toBe('Artikel SEO');
    expect($article->meta_description)->not->toBeNull();
    expect(strlen($article->meta_description))->toBeLessThanOrEqual(163); // 160 + "..."
});
