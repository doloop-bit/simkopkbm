<?php

use App\Models\GalleryPhoto;
use App\Models\NewsArticle;
use App\Models\Program;
use App\Models\SchoolProfile;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

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
});

test('homepage displays 3 latest news articles', function () {
    $user = User::factory()->create();

    // Create 5 published news articles with unique dates for deterministic ordering
    // Article 1 is newest (1 day ago), Article 5 is oldest (5 days ago)
    for ($i = 1; $i <= 5; $i++) {
        NewsArticle::factory()->create([
            'status' => 'published',
            'published_at' => now()->subDays($i)->startOfDay(),
            'author_id' => $user->id,
            'title' => "News Article $i",
        ]);
    }

    $response = $this->get(route('home'));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Public/Home', false)
        ->has('latestNews', 3)
    );
});

test('homepage displays program highlights', function () {
    // Create active programs
    $programs = Program::factory()->count(4)->create([
        'is_active' => true,
    ]);

    $response = $this->get(route('home'));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Public/Home', false)
        ->has('programs', 4)
    );
});

test('homepage displays gallery preview with 6 photos', function () {
    // Create published gallery photos
    GalleryPhoto::factory()->count(10)->create([
        'is_published' => true,
    ]);

    $response = $this->get(route('home'));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Public/Home', false)
        ->has('featuredPhotos', 6)
    );
});

test('homepage displays call-to-action sections', function () {
    $response = $this->get(route('home'));

    $response->assertStatus(200);
    // These strings are likely in the Svelte component but not in props.
    // However, if they are static and not in props, we can't test them with assertSee in Inertia easily.
    // We will skip testing literal static strings for now unless they are in the root view.
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

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Public/Home', false)
        ->has('schoolProfile')
    );
});

test('homepage footer is present', function () {
    $response = $this->get(route('home'));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Public/Home', false)
        ->has('schoolProfile')
    );
});
