<?php

use App\Models\GalleryPhoto;
use App\Models\NewsArticle;
use App\Models\Program;
use App\Models\SchoolProfile;
use App\Models\User;

beforeEach(function () {
    $this->withoutVite();
});

test('homepage displays hero section with school branding', function () {
    $schoolProfile = SchoolProfile::factory()->create([
        'name' => 'PKBM Test',
        'vision' => 'Menjadi pusat pendidikan terbaik',
        'is_active' => true,
    ]);

    $response = $this->get(route('home'));

    $response->assertStatus(200);
    $response->assertSee('PKBM Test');
    $response->assertSee('Pusat Kegiatan Belajar Masyarakat');
});

test('homepage displays 3 latest news articles', function () {
    $user = User::factory()->create();

    // Create 5 published news articles
    NewsArticle::factory()->count(5)->create([
        'status' => 'published',
        'published_at' => now()->subDays(1),
        'author_id' => $user->id,
    ]);

    $response = $this->get(route('home'));

    $response->assertStatus(200);

    // Should display latest 3 articles
    $latestArticles = NewsArticle::published()->latestPublished()->limit(3)->get();

    foreach ($latestArticles as $article) {
        $response->assertSee($article->title);
    }
});

test('homepage displays program highlights', function () {
    // Create active programs
    $programs = Program::factory()->count(4)->create([
        'is_active' => true,
    ]);

    $response = $this->get(route('home'));

    $response->assertStatus(200);
    $response->assertSee('Program Pendidikan');

    foreach ($programs as $program) {
        $response->assertSee($program->name);
    }
});

test('homepage displays gallery preview with 6 photos', function () {
    // Create published gallery photos
    GalleryPhoto::factory()->count(10)->create([
        'is_published' => true,
    ]);

    $response = $this->get(route('home'));

    $response->assertStatus(200);
    $response->assertSee('Galeri Foto');

    // Should display 6 photos
    $featuredPhotos = GalleryPhoto::published()->ordered()->limit(6)->get();
    expect($featuredPhotos)->toHaveCount(6);
});

test('homepage displays call-to-action sections', function () {
    $response = $this->get(route('home'));

    $response->assertStatus(200);
    $response->assertSee('Bergabunglah Bersama Kami');
    $response->assertSee('Hubungi Kami');
});

test('homepage shows empty state when no news exists', function () {
    $response = $this->get(route('home'));

    $response->assertStatus(200);
    // Should not show the news section heading
    $response->assertDontSee('<h2 class="text-4xl font-bold text-gray-900 mb-4">Berita Terbaru</h2>', false);
});

test('homepage shows empty state when no programs exist', function () {
    $response = $this->get(route('home'));

    $response->assertStatus(200);
    // Should not show the programs section heading, but navigation links are still present
    $response->assertDontSee('<h2 class="text-4xl font-bold text-gray-900 mb-4">Program Pendidikan</h2>', false);
});

test('homepage shows empty state when no gallery photos exist', function () {
    $response = $this->get(route('home'));

    $response->assertStatus(200);
    // Should not show the gallery section heading
    $response->assertDontSee('<h2 class="text-4xl font-bold text-gray-900 mb-4">Galeri Foto</h2>', false);
});

test('homepage only displays published news articles', function () {
    $user = User::factory()->create();

    // Create published and draft articles
    $publishedArticle = NewsArticle::factory()->create([
        'status' => 'published',
        'published_at' => now()->subDays(1),
        'author_id' => $user->id,
        'title' => 'Published Article',
    ]);

    $draftArticle = NewsArticle::factory()->create([
        'status' => 'draft',
        'published_at' => now()->subDays(1),
        'author_id' => $user->id,
        'title' => 'Draft Article',
    ]);

    $response = $this->get(route('home'));

    $response->assertStatus(200);
    $response->assertSee('Published Article');
    $response->assertDontSee('Draft Article');
});

test('homepage only displays active programs', function () {
    // Create active and inactive programs
    $activeProgram = Program::factory()->create([
        'is_active' => true,
        'name' => 'Active Program',
    ]);

    $inactiveProgram = Program::factory()->create([
        'is_active' => false,
        'name' => 'Inactive Program',
    ]);

    $response = $this->get(route('home'));

    $response->assertStatus(200);
    $response->assertSee('Active Program');
    $response->assertDontSee('Inactive Program');
});

test('homepage only displays published gallery photos', function () {
    // Create published and unpublished photos
    $publishedPhoto = GalleryPhoto::factory()->create([
        'is_published' => true,
        'title' => 'Published Photo',
    ]);

    $unpublishedPhoto = GalleryPhoto::factory()->create([
        'is_published' => false,
        'title' => 'Unpublished Photo',
    ]);

    $response = $this->get(route('home'));

    $response->assertStatus(200);
    $response->assertSee('Published Photo');
    $response->assertDontSee('Unpublished Photo');
});

test('homepage navigation links are present', function () {
    $response = $this->get(route('home'));

    $response->assertStatus(200);
    $response->assertSee('Beranda');
    $response->assertSee('Tentang Kami');
    $response->assertSee('Program Pendidikan');
    $response->assertSee('Berita');
    $response->assertSee('Galeri');
    $response->assertSee('Kontak');
});

test('homepage footer is present', function () {
    $response = $this->get(route('home'));

    $response->assertStatus(200);
    $response->assertSee('Tautan Cepat');
    $response->assertSee('Hubungi Kami');
});
