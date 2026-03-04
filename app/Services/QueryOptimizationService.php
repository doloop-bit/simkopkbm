<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QueryOptimizationService
{
    /**
     * Enable query logging for performance monitoring
     */
    public static function enableQueryLogging(): void
    {
        DB::listen(function ($query) {
            // Log queries that take longer than 100ms
            if ($query->time > 100) {
                Log::warning('Slow Query Detected', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time' => $query->time.'ms',
                    'connection' => $query->connectionName,
                ]);
            }
        });
    }

    /**
     * Get query execution plan for analysis
     */
    public static function explainQuery(string $sql, array $bindings = []): array
    {
        $explainSql = 'EXPLAIN '.$sql;

        return DB::select($explainSql, $bindings);
    }

    /**
     * Analyze table indexes
     */
    public static function analyzeTableIndexes(string $table): array
    {
        return DB::select("SHOW INDEX FROM {$table}");
    }

    /**
     * Get table statistics
     */
    public static function getTableStats(string $table): array
    {
        $stats = DB::select("SHOW TABLE STATUS LIKE '{$table}'");

        return $stats ? (array) $stats[0] : [];
    }

    /**
     * Optimize specific queries for the school profile website
     */
    public static function getOptimizedQueries(): array
    {
        return [
            'published_news_with_author' => [
                'description' => 'Get published news articles with author information',
                'query' => "
                    SELECT na.id, na.title, na.slug, na.excerpt, na.featured_image_path, 
                           na.published_at, u.name as author_name
                    FROM news_articles na
                    INNER JOIN users u ON na.author_id = u.id
                    WHERE na.status = 'published' 
                      AND na.published_at IS NOT NULL 
                      AND na.published_at <= NOW()
                    ORDER BY na.published_at DESC
                    LIMIT ?
                ",
                'indexes_used' => ['news_published_date_idx', 'author_id'],
            ],

            'active_programs_ordered' => [
                'description' => 'Get active programs ordered by display order',
                'query' => '
                    SELECT id, name, slug, description, level, image_path, `order`
                    FROM programs
                    WHERE is_active = 1
                    ORDER BY `order` ASC
                ',
                'indexes_used' => ['programs_active_order_idx'],
            ],

            'published_gallery_photos' => [
                'description' => 'Get published gallery photos with category filter',
                'query' => '
                    SELECT id, title, caption, category, thumbnail_path, 
                           thumbnail_webp_path, web_path, web_webp_path
                    FROM gallery_photos
                    WHERE is_published = 1
                      AND (? IS NULL OR category = ?)
                    ORDER BY `order` ASC, created_at DESC
                    LIMIT ?
                ',
                'indexes_used' => ['gallery_published_category_order_idx', 'gallery_published_order_idx'],
            ],

            'staff_by_school_profile' => [
                'description' => 'Get staff members for a school profile',
                'query' => '
                    SELECT id, name, position, photo_path, `order`
                    FROM staff_members
                    WHERE school_profile_id = ?
                    ORDER BY `order` ASC
                ',
                'indexes_used' => ['staff_profile_order_idx'],
            ],

            'facilities_by_school_profile' => [
                'description' => 'Get facilities for a school profile',
                'query' => '
                    SELECT id, name, description, image_path, `order`
                    FROM facilities
                    WHERE school_profile_id = ?
                    ORDER BY `order` ASC
                ',
                'indexes_used' => ['facilities_profile_order_idx'],
            ],
        ];
    }

    /**
     * Validate that required indexes exist
     */
    public static function validateIndexes(): array
    {
        $requiredIndexes = [
            'news_articles' => ['news_published_date_idx', 'slug', 'status', 'published_at', 'author_id'],
            'gallery_photos' => ['gallery_published_category_order_idx', 'gallery_published_order_idx', 'category', 'is_published', 'order'],
            'programs' => ['programs_active_order_idx', 'slug', 'is_active', 'order'],
            'staff_members' => ['staff_profile_order_idx', 'order'],
            'facilities' => ['facilities_profile_order_idx', 'order'],
            'school_profiles' => ['is_active'],
        ];

        $results = [];

        foreach ($requiredIndexes as $table => $indexes) {
            $existingIndexes = collect(self::analyzeTableIndexes($table))
                ->pluck('Key_name')
                ->toArray();

            $missingIndexes = array_diff($indexes, $existingIndexes);

            $results[$table] = [
                'existing' => $existingIndexes,
                'missing' => $missingIndexes,
                'status' => empty($missingIndexes) ? 'OK' : 'MISSING_INDEXES',
            ];
        }

        return $results;
    }
}
