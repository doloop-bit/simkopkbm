<?php

use App\Models\NewsArticle;
use App\Models\User;

describe('NewsArticle Model', function () {
    test('can create a news article', function () {
        $user = User::factory()->create();

        $article = NewsArticle::create([
            'title' => 'Berita Terbaru',
            'slug' => 'berita-terbaru',
            'content' => 'Ini adalah konten berita terbaru.',
            'excerpt' => 'Ringkasan berita',
            'published_at' => now(),
            'status' => 'published',
            'author_id' => $user->id,
        ]);

        expect($article)->toBeInstanceOf(NewsArticle::class)
            ->and($article->title)->toBe('Berita Terbaru')
            ->and($article->status)->toBe('published');
    });

    test('casts published_at to datetime', function () {
        $user = User::factory()->create();
        $publishedAt = now();

        $article = NewsArticle::create([
            'title' => 'Berita Test',
            'slug' => 'berita-test',
            'content' => 'Konten berita',
            'published_at' => $publishedAt,
            'status' => 'published',
            'author_id' => $user->id,
        ]);

        expect($article->published_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class)
            ->and($article->published_at->toDateTimeString())->toBe($publishedAt->toDateTimeString());
    });

    test('has author relationship', function () {
        $user = User::factory()->create(['name' => 'John Doe']);

        $article = NewsArticle::create([
            'title' => 'Berita Test',
            'slug' => 'berita-test',
            'content' => 'Konten berita',
            'published_at' => now(),
            'status' => 'published',
            'author_id' => $user->id,
        ]);

        expect($article->author)->toBeInstanceOf(User::class)
            ->and($article->author->name)->toBe('John Doe')
            ->and($article->author->id)->toBe($user->id);
    });

    test('published scope filters published articles', function () {
        $user = User::factory()->create();

        // Create published article
        NewsArticle::create([
            'title' => 'Published Article',
            'slug' => 'published-article',
            'content' => 'Content',
            'published_at' => now()->subDay(),
            'status' => 'published',
            'author_id' => $user->id,
        ]);

        // Create draft article
        NewsArticle::create([
            'title' => 'Draft Article',
            'slug' => 'draft-article',
            'content' => 'Content',
            'published_at' => now(),
            'status' => 'draft',
            'author_id' => $user->id,
        ]);

        // Create article with null published_at
        NewsArticle::create([
            'title' => 'Unpublished Article',
            'slug' => 'unpublished-article',
            'content' => 'Content',
            'published_at' => null,
            'status' => 'published',
            'author_id' => $user->id,
        ]);

        // Create article with future published_at
        NewsArticle::create([
            'title' => 'Future Article',
            'slug' => 'future-article',
            'content' => 'Content',
            'published_at' => now()->addDay(),
            'status' => 'published',
            'author_id' => $user->id,
        ]);

        $publishedArticles = NewsArticle::published()->get();

        expect($publishedArticles)->toHaveCount(1)
            ->and($publishedArticles->first()->title)->toBe('Published Article');
    });

    test('latestPublished scope orders articles by published_at descending', function () {
        $user = User::factory()->create();

        NewsArticle::create([
            'title' => 'Oldest Article',
            'slug' => 'oldest-article',
            'content' => 'Content',
            'published_at' => now()->subDays(3),
            'status' => 'published',
            'author_id' => $user->id,
        ]);

        NewsArticle::create([
            'title' => 'Middle Article',
            'slug' => 'middle-article',
            'content' => 'Content',
            'published_at' => now()->subDays(2),
            'status' => 'published',
            'author_id' => $user->id,
        ]);

        NewsArticle::create([
            'title' => 'Newest Article',
            'slug' => 'newest-article',
            'content' => 'Content',
            'published_at' => now()->subDay(),
            'status' => 'published',
            'author_id' => $user->id,
        ]);

        $articles = NewsArticle::latestPublished()->get();

        expect($articles)->toHaveCount(3)
            ->and($articles->get(0)->title)->toBe('Newest Article')
            ->and($articles->get(1)->title)->toBe('Middle Article')
            ->and($articles->get(2)->title)->toBe('Oldest Article');
    });

    test('published and latestPublished scopes can be combined', function () {
        $user = User::factory()->create();

        // Published articles
        NewsArticle::create([
            'title' => 'Published Old',
            'slug' => 'published-old',
            'content' => 'Content',
            'published_at' => now()->subDays(3),
            'status' => 'published',
            'author_id' => $user->id,
        ]);

        NewsArticle::create([
            'title' => 'Published New',
            'slug' => 'published-new',
            'content' => 'Content',
            'published_at' => now()->subDay(),
            'status' => 'published',
            'author_id' => $user->id,
        ]);

        // Draft article
        NewsArticle::create([
            'title' => 'Draft Article',
            'slug' => 'draft-article',
            'content' => 'Content',
            'published_at' => now()->subDays(2),
            'status' => 'draft',
            'author_id' => $user->id,
        ]);

        $articles = NewsArticle::published()->latestPublished()->get();

        expect($articles)->toHaveCount(2)
            ->and($articles->get(0)->title)->toBe('Published New')
            ->and($articles->get(1)->title)->toBe('Published Old');
    });

    test('isPublished method returns true for published articles', function () {
        $user = User::factory()->create();

        $article = NewsArticle::create([
            'title' => 'Published Article',
            'slug' => 'published-article',
            'content' => 'Content',
            'published_at' => now()->subDay(),
            'status' => 'published',
            'author_id' => $user->id,
        ]);

        expect($article->isPublished())->toBeTrue();
    });

    test('isPublished method returns false for draft articles', function () {
        $user = User::factory()->create();

        $article = NewsArticle::create([
            'title' => 'Draft Article',
            'slug' => 'draft-article',
            'content' => 'Content',
            'published_at' => now(),
            'status' => 'draft',
            'author_id' => $user->id,
        ]);

        expect($article->isPublished())->toBeFalse();
    });

    test('isPublished method returns false when published_at is null', function () {
        $user = User::factory()->create();

        $article = NewsArticle::create([
            'title' => 'Article',
            'slug' => 'article',
            'content' => 'Content',
            'published_at' => null,
            'status' => 'published',
            'author_id' => $user->id,
        ]);

        expect($article->isPublished())->toBeFalse();
    });

    test('isPublished method returns false for future published_at', function () {
        $user = User::factory()->create();

        $article = NewsArticle::create([
            'title' => 'Future Article',
            'slug' => 'future-article',
            'content' => 'Content',
            'published_at' => now()->addDay(),
            'status' => 'published',
            'author_id' => $user->id,
        ]);

        expect($article->isPublished())->toBeFalse();
    });

    test('can create article with all optional fields', function () {
        $user = User::factory()->create();

        $article = NewsArticle::create([
            'title' => 'Complete Article',
            'slug' => 'complete-article',
            'content' => 'Full content here',
            'excerpt' => 'Short excerpt',
            'featured_image_path' => 'news/image.jpg',
            'published_at' => now(),
            'status' => 'published',
            'meta_title' => 'SEO Title',
            'meta_description' => 'SEO Description',
            'author_id' => $user->id,
        ]);

        expect($article->excerpt)->toBe('Short excerpt')
            ->and($article->featured_image_path)->toBe('news/image.jpg')
            ->and($article->meta_title)->toBe('SEO Title')
            ->and($article->meta_description)->toBe('SEO Description');
    });

    test('factory creates valid article', function () {
        $article = NewsArticle::factory()->create();

        expect($article)->toBeInstanceOf(NewsArticle::class)
            ->and($article->title)->not->toBeEmpty()
            ->and($article->slug)->not->toBeEmpty()
            ->and($article->content)->not->toBeEmpty()
            ->and($article->author_id)->not->toBeNull();
    });

    test('factory draft state creates draft article', function () {
        $article = NewsArticle::factory()->draft()->create();

        expect($article->status)->toBe('draft')
            ->and($article->published_at)->toBeNull();
    });

    test('factory published state creates published article', function () {
        $article = NewsArticle::factory()->published()->create();

        expect($article->status)->toBe('published')
            ->and($article->published_at)->not->toBeNull()
            ->and($article->isPublished())->toBeTrue();
    });

    test('factory withFeaturedImage state adds featured image', function () {
        $article = NewsArticle::factory()->withFeaturedImage()->create();

        expect($article->featured_image_path)->not->toBeNull()
            ->and($article->featured_image_path)->toContain('news/');
    });

    test('factory withSeoMetadata state adds SEO fields', function () {
        $article = NewsArticle::factory()->withSeoMetadata()->create();

        expect($article->meta_title)->not->toBeNull()
            ->and($article->meta_description)->not->toBeNull();
    });

    test('created_at timestamp is preserved on update', function () {
        $user = User::factory()->create();

        $article = NewsArticle::create([
            'title' => 'Original Title',
            'slug' => 'original-title',
            'content' => 'Content',
            'published_at' => now(),
            'status' => 'published',
            'author_id' => $user->id,
        ]);

        $originalCreatedAt = $article->created_at;

        // Wait a moment to ensure time difference
        sleep(1);

        // Update the article
        $article->update(['title' => 'Updated Title']);

        expect($article->created_at->toDateTimeString())->toBe($originalCreatedAt->toDateTimeString())
            ->and($article->updated_at->greaterThan($originalCreatedAt))->toBeTrue();
    });
});
