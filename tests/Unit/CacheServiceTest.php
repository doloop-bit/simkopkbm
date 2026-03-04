<?php

use App\Models\GalleryPhoto;
use App\Models\NewsArticle;
use App\Models\Program;
use App\Models\SchoolProfile;
use App\Models\User;
use App\Services\CacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->cacheService = app(CacheService::class);
    Cache::flush();
});

test('can cache and retrieve school profile', function () {
    $schoolProfile = SchoolProfile::factory()->create(['is_active' => true]);

    $cached = $this->cacheService->getSchoolProfile();

    expect($cached)->not->toBeNull();
    expect($cached->id)->toBe($schoolProfile->id);

    // Verify it's cached by checking cache directly
    expect(Cache::has(CacheService::SCHOOL_PROFILE_KEY))->toBeTrue();
});

test('can cache and retrieve active programs', function () {
    Program::factory()->count(3)->create(['is_active' => true]);
    Program::factory()->create(['is_active' => false]); // Should not be included

    $cached = $this->cacheService->getActivePrograms();

    expect($cached)->toHaveCount(3);
    expect(Cache::has(CacheService::PROGRAMS_ACTIVE_KEY))->toBeTrue();
});

test('can cache and retrieve latest news', function () {
    $user = User::factory()->create();

    NewsArticle::factory()->count(5)->create([
        'status' => 'published',
        'published_at' => now()->subDays(1),
        'author_id' => $user->id,
    ]);

    $cached = $this->cacheService->getLatestNews(3);

    expect($cached)->toHaveCount(3);
    expect(Cache::has(CacheService::LATEST_NEWS_KEY.'_3'))->toBeTrue();
});

test('can cache and retrieve featured photos', function () {
    GalleryPhoto::factory()->count(8)->create(['is_published' => true]);

    $cached = $this->cacheService->getFeaturedPhotos(6);

    expect($cached)->toHaveCount(6);
    expect(Cache::has(CacheService::FEATURED_PHOTOS_KEY.'_6'))->toBeTrue();
});

test('can clear specific caches', function () {
    // Create required data first
    SchoolProfile::factory()->create(['is_active' => true]);
    Program::factory()->count(2)->create(['is_active' => true]);

    // Populate caches
    $this->cacheService->getSchoolProfile();
    $this->cacheService->getActivePrograms();

    expect(Cache::has(CacheService::SCHOOL_PROFILE_KEY))->toBeTrue();
    expect(Cache::has(CacheService::PROGRAMS_ACTIVE_KEY))->toBeTrue();

    // Clear school profile cache
    $this->cacheService->clearSchoolProfileCache();

    expect(Cache::has(CacheService::SCHOOL_PROFILE_KEY))->toBeFalse();
    expect(Cache::has(CacheService::PROGRAMS_ACTIVE_KEY))->toBeTrue(); // Should still exist

    // Clear programs cache
    $this->cacheService->clearProgramsCache();

    expect(Cache::has(CacheService::PROGRAMS_ACTIVE_KEY))->toBeFalse();
});

test('can warm up cache', function () {
    SchoolProfile::factory()->create(['is_active' => true]);
    Program::factory()->count(2)->create(['is_active' => true]);

    $user = User::factory()->create();
    NewsArticle::factory()->count(3)->create([
        'status' => 'published',
        'published_at' => now()->subDays(1),
        'author_id' => $user->id,
    ]);

    GalleryPhoto::factory()->count(6)->create(['is_published' => true]);

    $this->cacheService->warmUpCache();

    expect(Cache::has(CacheService::SCHOOL_PROFILE_KEY))->toBeTrue();
    expect(Cache::has(CacheService::PROGRAMS_ACTIVE_KEY))->toBeTrue();
    expect(Cache::has(CacheService::LATEST_NEWS_KEY.'_3'))->toBeTrue();
    expect(Cache::has(CacheService::FEATURED_PHOTOS_KEY.'_6'))->toBeTrue();
});
