<?php

declare(strict_types=1);

use App\Models\NewsArticle;
use App\Models\User;

beforeEach(function () {
    $this->withoutVite();
});

it('orders published news articles by publication date in descending order', function () {
    // Feature: school-profile-website, Property 1: News Article Ordering

    $author = User::factory()->create();

    // Create news articles with different publication dates
    $articles = collect();

    // Create articles with random publication dates over the past year
    for ($i = 0; $i < 10; $i++) {
        $publishedAt = now()->subDays(rand(1, 365));
        $articles->push(
            NewsArticle::factory()->create([
                'status' => 'published',
                'published_at' => $publishedAt,
                'author_id' => $author->id,
            ])
        );
    }

    // Get articles using the published scope with latest ordering
    $displayedArticles = NewsArticle::published()->latestPublished()->get();

    // Verify all articles are returned
    expect($displayedArticles->count())->toBe(10);

    // Verify ordering - each article should have published_at >= next article
    $dates = $displayedArticles->pluck('published_at');

    for ($i = 0; $i < $dates->count() - 1; $i++) {
        expect($dates[$i]->greaterThanOrEqualTo($dates[$i + 1]))->toBeTrue(
            "Article at index {$i} ({$dates[$i]}) should be >= article at index ".($i + 1)." ({$dates[$i + 1]})"
        );
    }
})->repeat(50);

it('excludes draft articles from published ordering', function () {
    // Feature: school-profile-website, Property 1: News Article Ordering

    $author = User::factory()->create();

    // Create mix of published and draft articles
    $publishedCount = rand(3, 8);
    $draftCount = rand(2, 5);

    // Create published articles
    for ($i = 0; $i < $publishedCount; $i++) {
        NewsArticle::factory()->create([
            'status' => 'published',
            'published_at' => now()->subDays(rand(1, 30)),
            'author_id' => $author->id,
        ]);
    }

    // Create draft articles
    for ($i = 0; $i < $draftCount; $i++) {
        NewsArticle::factory()->create([
            'status' => 'draft',
            'published_at' => now()->subDays(rand(1, 30)),
            'author_id' => $author->id,
        ]);
    }

    // Get only published articles
    $displayedArticles = NewsArticle::published()->latest()->get();

    // Should only return published articles
    expect($displayedArticles->count())->toBe($publishedCount);

    // All returned articles should be published
    $displayedArticles->each(function ($article) {
        expect($article->status)->toBe('published');
        expect($article->published_at)->not->toBeNull();
        expect($article->published_at->isPast())->toBeTrue();
    });
})->repeat(30);

it('excludes future-dated articles from published ordering', function () {
    // Feature: school-profile-website, Property 1: News Article Ordering

    $author = User::factory()->create();

    // Create articles with past dates (should be visible)
    $pastCount = rand(2, 5);
    for ($i = 0; $i < $pastCount; $i++) {
        NewsArticle::factory()->create([
            'status' => 'published',
            'published_at' => now()->subDays(rand(1, 30)),
            'author_id' => $author->id,
        ]);
    }

    // Create articles with future dates (should not be visible)
    $futureCount = rand(1, 3);
    for ($i = 0; $i < $futureCount; $i++) {
        NewsArticle::factory()->create([
            'status' => 'published',
            'published_at' => now()->addDays(rand(1, 30)),
            'author_id' => $author->id,
        ]);
    }

    // Get published articles
    $displayedArticles = NewsArticle::published()->latestPublished()->get();

    // Should only return past-dated articles
    expect($displayedArticles->count())->toBe($pastCount);

    // All returned articles should have past publication dates
    $displayedArticles->each(function ($article) {
        expect($article->published_at->isPast())->toBeTrue();
    });
})->repeat(30);

it('handles empty result set gracefully', function () {
    // Feature: school-profile-website, Property 1: News Article Ordering

    // Create only draft articles or future-dated articles
    $author = User::factory()->create();

    NewsArticle::factory()->count(3)->create([
        'status' => 'draft',
        'author_id' => $author->id,
    ]);

    NewsArticle::factory()->count(2)->create([
        'status' => 'published',
        'published_at' => now()->addDays(rand(1, 10)),
        'author_id' => $author->id,
    ]);

    // Should return empty collection
    $displayedArticles = NewsArticle::published()->latestPublished()->get();
    expect($displayedArticles->count())->toBe(0);
})->repeat(20);
