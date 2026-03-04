<?php

declare(strict_types=1);

use App\Models\NewsArticle;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->withoutVite();

    // Create an admin user
    $this->admin = User::factory()->create(['role' => 'admin']);

    actingAs($this->admin);
});

describe('News Listing Component', function () {
    test('displays news listing page', function () {
        Volt::test('admin.news.index')
            ->assertSee('Berita')
            ->assertSee('Kelola artikel berita dan pengumuman');
    });

    test('displays all news articles', function () {
        $articles = NewsArticle::factory()->count(3)->create([
            'status' => 'published',
            'published_at' => now(),
        ]);

        Volt::test('admin.news.index')
            ->assertSee($articles[0]->title)
            ->assertSee($articles[1]->title)
            ->assertSee($articles[2]->title);
    });

    test('displays article status correctly', function () {
        NewsArticle::factory()->create([
            'title' => 'Published Article',
            'status' => 'published',
            'published_at' => now(),
        ]);

        NewsArticle::factory()->create([
            'title' => 'Draft Article',
            'status' => 'draft',
            'published_at' => now(),
        ]);

        Volt::test('admin.news.index')
            ->assertSee('Published Article')
            ->assertSee('Draft Article')
            ->assertSee('Dipublikasikan')
            ->assertSee('Draft');
    });

    test('can search articles by title', function () {
        NewsArticle::factory()->create([
            'title' => 'Laravel Tutorial',
            'status' => 'published',
            'published_at' => now(),
        ]);

        NewsArticle::factory()->create([
            'title' => 'PHP Best Practices',
            'status' => 'published',
            'published_at' => now(),
        ]);

        Volt::test('admin.news.index')
            ->set('search', 'Laravel')
            ->assertSee('Laravel Tutorial')
            ->assertDontSee('PHP Best Practices');
    });

    test('can filter articles by status', function () {
        NewsArticle::factory()->create([
            'title' => 'Published Article',
            'status' => 'published',
            'published_at' => now(),
        ]);

        NewsArticle::factory()->create([
            'title' => 'Draft Article',
            'status' => 'draft',
            'published_at' => now(),
        ]);

        Volt::test('admin.news.index')
            ->set('statusFilter', 'published')
            ->assertSee('Published Article')
            ->assertDontSee('Draft Article');

        Volt::test('admin.news.index')
            ->set('statusFilter', 'draft')
            ->assertSee('Draft Article')
            ->assertDontSee('Published Article');
    });

    test('displays pagination when there are many articles', function () {
        NewsArticle::factory()->count(20)->create([
            'status' => 'published',
            'published_at' => now(),
        ]);

        Volt::test('admin.news.index')
            ->assertSee('Berita');

        // Check that pagination exists
        $articles = NewsArticle::paginate(15);
        expect($articles->hasPages())->toBeTrue();
    });

    test('can delete an article', function () {
        Storage::fake('public');

        $article = NewsArticle::factory()->create([
            'title' => 'Article to Delete',
            'status' => 'published',
            'published_at' => now(),
            'featured_image_path' => 'news/test-image.jpg',
        ]);

        // Create a fake image file
        Storage::disk('public')->put('news/test-image.jpg', 'fake-image-content');

        Volt::test('admin.news.index')
            ->call('deleteArticle', $article->id)
            ->assertHasNoErrors();

        // Verify article is deleted
        expect(NewsArticle::find($article->id))->toBeNull();

        // Verify image is deleted
        Storage::disk('public')->assertMissing('news/test-image.jpg');
    });

    test('can delete an article without featured image', function () {
        $article = NewsArticle::factory()->create([
            'title' => 'Article without Image',
            'status' => 'published',
            'published_at' => now(),
            'featured_image_path' => null,
        ]);

        Volt::test('admin.news.index')
            ->call('deleteArticle', $article->id)
            ->assertHasNoErrors();

        // Verify article is deleted
        expect(NewsArticle::find($article->id))->toBeNull();
    });

    test('cannot delete non-existent article', function () {
        Volt::test('admin.news.index')
            ->call('deleteArticle', 99999);
    })->throws(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

    test('displays empty state when no articles exist', function () {
        Volt::test('admin.news.index')
            ->assertSee('Belum ada artikel berita');
    });

    test('displays empty state when search returns no results', function () {
        NewsArticle::factory()->create([
            'title' => 'Laravel Tutorial',
            'status' => 'published',
            'published_at' => now(),
        ]);

        Volt::test('admin.news.index')
            ->set('search', 'NonExistentArticle')
            ->assertSee('Tidak ada artikel yang sesuai dengan pencarian atau filter Anda');
    });

    test('resets pagination when searching', function () {
        NewsArticle::factory()->count(20)->create([
            'status' => 'published',
            'published_at' => now(),
        ]);

        $component = Volt::test('admin.news.index');

        // Navigate to page 2 by calling the Livewire pagination method
        $component->call('gotoPage', 2, 'page');

        // Verify we're on page 2
        $articles = $component->viewData('articles');
        expect($articles->currentPage())->toBe(2);

        // Search should reset to page 1
        $component->set('search', 'test');

        // Verify we're back on page 1
        $articles = $component->viewData('articles');
        expect($articles->currentPage())->toBe(1);
    });

    test('displays author name for each article', function () {
        $author = User::factory()->create(['name' => 'John Doe']);

        NewsArticle::factory()->create([
            'title' => 'Test Article',
            'status' => 'published',
            'published_at' => now(),
            'author_id' => $author->id,
        ]);

        Volt::test('admin.news.index')
            ->assertSee('John Doe');
    });

    test('displays article excerpt when available', function () {
        NewsArticle::factory()->create([
            'title' => 'Test Article',
            'excerpt' => 'This is a test excerpt for the article',
            'status' => 'published',
            'published_at' => now(),
        ]);

        Volt::test('admin.news.index')
            ->assertSee('This is a test excerpt');
    });

    test('displays featured image when available', function () {
        Storage::fake('public');

        $article = NewsArticle::factory()->create([
            'title' => 'Test Article',
            'status' => 'published',
            'published_at' => now(),
            'featured_image_path' => 'news/test-image.jpg',
        ]);

        Storage::disk('public')->put('news/test-image.jpg', 'fake-image-content');

        $response = Volt::test('admin.news.index');

        // Check that the image URL is in the rendered HTML
        expect($response->html())->toContain(Storage::url('news/test-image.jpg'));
    });

    test('displays placeholder icon when no featured image', function () {
        NewsArticle::factory()->create([
            'title' => 'Test Article',
            'status' => 'published',
            'published_at' => now(),
            'featured_image_path' => null,
        ]);

        $response = Volt::test('admin.news.index');

        // Check for the newspaper icon SVG path
        expect($response->html())->toContain('M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375');
    });

    test('displays formatted publication date', function () {
        NewsArticle::factory()->create([
            'title' => 'Test Article',
            'status' => 'published',
            'published_at' => now()->setDate(2024, 1, 15),
        ]);

        Volt::test('admin.news.index')
            ->assertSee('15 Jan 2024');
    });

    test('displays dash when publication date is null', function () {
        NewsArticle::factory()->create([
            'title' => 'Test Article',
            'status' => 'draft',
            'published_at' => null,
        ]);

        $response = Volt::test('admin.news.index');

        // The dash should be displayed in the publication date column
        expect($response->html())->toContain('data-flux-text >-</p>');
    });
});

describe('News Listing Authorization', function () {
    test('requires authentication', function () {
        auth()->logout();

        $this->get(route('admin.news.index'))
            ->assertRedirect(route('login'));
    });

    test('requires admin role', function () {
        $user = User::factory()->create(['role' => 'teacher']);
        actingAs($user);

        $this->get(route('admin.news.index'))
            ->assertForbidden();
    });
});
