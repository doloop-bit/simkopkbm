<?php

use App\Models\{SchoolProfile, NewsArticle, Program, GalleryPhoto, User};
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

test('homepage displays responsive layout elements', function () {
    // Create test data
    $user = User::factory()->create();
    $schoolProfile = SchoolProfile::factory()->create(['is_active' => true]);
    $news = NewsArticle::factory()->count(3)->create([
        'status' => 'published',
        'published_at' => now(),
        'author_id' => $user->id,
    ]);
    $programs = Program::factory()->count(4)->create(['is_active' => true]);
    $photos = GalleryPhoto::factory()->count(6)->create(['is_published' => true]);

    $response = $this->get('/');

    $response->assertStatus(200);
    
    // Check for responsive grid classes
    $response->assertSee('grid-cols-1');
    $response->assertSee('sm:grid-cols-2');
    $response->assertSee('lg:grid-cols-3');
    $response->assertSee('lg:grid-cols-4');
    
    // Check for responsive text classes
    $response->assertSee('text-3xl');
    $response->assertSee('sm:text-4xl');
    $response->assertSee('md:text-5xl');
    
    // Check for responsive spacing
    $response->assertSee('px-4');
    $response->assertSee('sm:px-6');
    $response->assertSee('lg:px-8');
    
    // Check for responsive padding
    $response->assertSee('py-16');
    $response->assertSee('sm:py-20');
});

test('navigation includes mobile menu functionality', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
    
    // Check for mobile menu button
    $response->assertSee('lg:hidden');
    
    // Check for desktop navigation
    $response->assertSee('hidden lg:ml-12 lg:flex');
    
    // Check for mobile menu classes
    $response->assertSee('x-show=');
    $response->assertSee('mobileMenuOpen');
});

test('news listing page has responsive grid', function () {
    $user = User::factory()->create();
    NewsArticle::factory()->count(5)->create([
        'status' => 'published',
        'published_at' => now(),
        'author_id' => $user->id,
    ]);

    $response = $this->get('/berita');

    $response->assertStatus(200);
    
    // Check for responsive news grid
    $response->assertSee('grid-cols-1');
    $response->assertSee('sm:grid-cols-2');
    $response->assertSee('lg:grid-cols-3');
    
    // Check for responsive hero section
    $response->assertSee('py-12');
    $response->assertSee('sm:py-16');
});

test('gallery page has responsive photo grid', function () {
    GalleryPhoto::factory()->count(8)->create(['is_published' => true]);

    $response = $this->get('/galeri');

    $response->assertStatus(200);
    
    // Check for responsive gallery grid
    $response->assertSee('grid-cols-2');
    $response->assertSee('sm:grid-cols-3');
    $response->assertSee('md:grid-cols-4');
    $response->assertSee('lg:grid-cols-5');
    $response->assertSee('xl:grid-cols-6');
});

test('contact page has responsive layout', function () {
    SchoolProfile::factory()->create(['is_active' => true]);

    $response = $this->get('/kontak');

    $response->assertStatus(200);
    
    // Check for responsive two-column layout
    $response->assertSee('grid-cols-1');
    $response->assertSee('lg:grid-cols-2');
    
    // Check for responsive form elements
    $response->assertSee('px-3');
    $response->assertSee('sm:px-4');
    $response->assertSee('py-2');
    $response->assertSee('sm:py-3');
});

test('footer has responsive layout', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
    
    // Check for responsive footer grid
    $response->assertSee('grid-cols-1');
    $response->assertSee('sm:grid-cols-2');
    $response->assertSee('lg:grid-cols-4');
    
    // Check for responsive footer bottom section
    $response->assertSee('flex-col');
    $response->assertSee('sm:flex-row');
});