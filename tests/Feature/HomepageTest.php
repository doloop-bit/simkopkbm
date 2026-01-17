<?php

declare(strict_types=1);

use App\Models\GalleryPhoto;
use App\Models\NewsArticle;
use App\Models\Program;
use App\Models\SchoolProfile;

beforeEach(function () {
    $this->withoutVite();
});

test('homepage loads successfully', function () {
    // Create test data
    $schoolProfile = SchoolProfile::factory()->create(['is_active' => true]);
    NewsArticle::factory()->count(3)->create(['status' => 'published', 'published_at' => now()]);

    // Create programs with unique slugs
    Program::factory()->paud()->create(['is_active' => true]);
    Program::factory()->paketA()->create(['is_active' => true]);
    Program::factory()->paketB()->create(['is_active' => true]);

    GalleryPhoto::factory()->count(6)->create(['is_published' => true]);

    $response = $this->get('/');

    $response->assertSuccessful();
    $response->assertSee($schoolProfile->name);
    $response->assertSee('Pusat Kegiatan Belajar Masyarakat');
});

test('homepage displays school profile information', function () {
    $schoolProfile = SchoolProfile::factory()->create([
        'name' => 'PKBM Test School',
        'vision' => 'Test vision for the school',
        'is_active' => true,
    ]);

    $response = $this->get('/');

    $response->assertSuccessful();
    $response->assertSee('PKBM Test School');
    $response->assertSee('Test vision for the school');
});

test('homepage displays latest news when available', function () {
    SchoolProfile::factory()->create(['is_active' => true]);

    $news = NewsArticle::factory()->count(3)->create(['status' => 'published', 'published_at' => now()]);

    $response = $this->get('/');

    $response->assertSuccessful();
    $response->assertSee('Berita Terbaru');

    foreach ($news as $article) {
        $response->assertSee($article->title);
    }
});

test('homepage displays programs when available', function () {
    SchoolProfile::factory()->create(['is_active' => true]);

    // Create programs with unique slugs
    $program1 = Program::factory()->paud()->create(['is_active' => true]);
    $program2 = Program::factory()->paketA()->create(['is_active' => true]);

    $response = $this->get('/');

    $response->assertSuccessful();
    $response->assertSee('Program Pendidikan');
    $response->assertSee($program1->name);
    $response->assertSee($program2->name);
});
