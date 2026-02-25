<?php

namespace App\Services;

use App\Models\GalleryPhoto;
use App\Models\NewsArticle;
use App\Models\Program;
use App\Models\SchoolProfile;
use Illuminate\Support\Facades\Cache;

class CacheService
{
    const SCHOOL_PROFILE_KEY = 'school_profile_active';

    const NAVIGATION_MENU_KEY = 'navigation_menu';

    const PROGRAMS_ACTIVE_KEY = 'programs_active';

    const LATEST_NEWS_KEY = 'latest_news';

    const GALLERY_CATEGORIES_KEY = 'gallery_categories';

    const FEATURED_PHOTOS_KEY = 'featured_photos';

    const DEFAULT_TTL = 3600; // 1 hour

    const LONG_TTL = 86400; // 24 hours

    /**
     * Get cached school profile
     */
    public function getSchoolProfile(): ?SchoolProfile
    {
        return Cache::remember(self::SCHOOL_PROFILE_KEY, self::LONG_TTL, function () {
            return SchoolProfile::active();
        });
    }

    /**
     * Get cached navigation menu data
     */
    public function getNavigationMenu(): array
    {
        return Cache::remember(self::NAVIGATION_MENU_KEY, self::LONG_TTL, function () {
            $schoolProfile = $this->getSchoolProfile();

            return [
                'school_name' => $schoolProfile?->name ?? config('app.name'),
                'logo_path' => $schoolProfile?->logo_path,
                'menu_items' => [
                    ['name' => 'Beranda', 'route' => 'public.homepage'],
                    ['name' => 'Tentang Kami', 'route' => 'public.about.index'],
                    ['name' => 'Program Pendidikan', 'route' => 'public.programs.index'],
                    ['name' => 'Berita', 'route' => 'public.news.index'],
                    ['name' => 'Galeri', 'route' => 'public.gallery'],
                    ['name' => 'Kontak', 'route' => 'public.contact'],
                ],
                'social_links' => [
                    'facebook' => $schoolProfile?->facebook_url,
                    'instagram' => $schoolProfile?->instagram_url,
                    'youtube' => $schoolProfile?->youtube_url,
                    'twitter' => $schoolProfile?->twitter_url,
                ],
            ];
        });
    }

    /**
     * Get cached active programs
     */
    public function getActivePrograms(): \Illuminate\Database\Eloquent\Collection
    {
        return Cache::remember(self::PROGRAMS_ACTIVE_KEY, self::DEFAULT_TTL, function () {
            return Program::active()
                ->ordered()
                ->with('level')
                ->select(['id', 'level_id', 'name', 'slug', 'description', 'image_path', 'order'])
                ->get();
        });
    }

    /**
     * Get cached latest news
     */
    public function getLatestNews(int $limit = 3): \Illuminate\Database\Eloquent\Collection
    {
        $key = self::LATEST_NEWS_KEY."_{$limit}";

        return Cache::remember($key, self::DEFAULT_TTL, function () use ($limit) {
            return NewsArticle::published()
                ->latest()
                ->select(['id', 'title', 'slug', 'excerpt', 'featured_image_path', 'published_at'])
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Get cached gallery categories
     */
    public function getGalleryCategories(): array
    {
        return Cache::remember(self::GALLERY_CATEGORIES_KEY, self::DEFAULT_TTL, function () {
            return GalleryPhoto::published()
                ->whereNotNull('category')
                ->distinct()
                ->pluck('category')
                ->filter()
                ->sort()
                ->values()
                ->toArray();
        });
    }

    /**
     * Get cached featured photos
     */
    public function getFeaturedPhotos(int $limit = 6): \Illuminate\Database\Eloquent\Collection
    {
        $key = self::FEATURED_PHOTOS_KEY."_{$limit}";

        return Cache::remember($key, self::DEFAULT_TTL, function () use ($limit) {
            return GalleryPhoto::published()
                ->ordered()
                ->select(['id', 'title', 'caption', 'category', 'thumbnail_path', 'thumbnail_webp_path', 'web_path', 'web_webp_path'])
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Clear school profile cache
     */
    public function clearSchoolProfileCache(): void
    {
        Cache::forget(self::SCHOOL_PROFILE_KEY);
        Cache::forget(self::NAVIGATION_MENU_KEY);
    }

    /**
     * Clear programs cache
     */
    public function clearProgramsCache(): void
    {
        Cache::forget(self::PROGRAMS_ACTIVE_KEY);
    }

    /**
     * Clear news cache
     */
    public function clearNewsCache(): void
    {
        // Cache::tags(['news'])->flush(); // Not supported by database driver

        // Clear specific keys
        for ($i = 1; $i <= 10; $i++) {
            Cache::forget(self::LATEST_NEWS_KEY."_{$i}");
        }
    }

    /**
     * Clear gallery cache
     */
    public function clearGalleryCache(): void
    {
        Cache::forget(self::GALLERY_CATEGORIES_KEY);

        // Clear featured photos with different limits
        for ($i = 1; $i <= 20; $i++) {
            Cache::forget(self::FEATURED_PHOTOS_KEY."_{$i}");
        }
    }

    /**
     * Clear all public website caches
     */
    public function clearAllPublicCache(): void
    {
        $this->clearSchoolProfileCache();
        $this->clearProgramsCache();
        $this->clearNewsCache();
        $this->clearGalleryCache();
    }

    /**
     * Warm up essential caches
     */
    public function warmUpCache(): void
    {
        // Warm up most commonly accessed data
        $this->getSchoolProfile();
        $this->getNavigationMenu();
        $this->getActivePrograms();
        $this->getLatestNews(3);
        $this->getFeaturedPhotos(6);
        $this->getGalleryCategories();
    }
}
