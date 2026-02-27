<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GalleryPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'caption',
        'category',
        'original_path',
        'thumbnail_path',
        'web_path',
        'medium_path',
        'small_path',
        'original_webp_path',
        'thumbnail_webp_path',
        'web_webp_path',
        'medium_webp_path',
        'small_webp_path',
        'order',
        'is_published',
    ];

    protected $attributes = [
        'order' => 0,
        'is_published' => true,
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'order' => 'integer',
        ];
    }

    /**
     * Scope a query to only include published photos.
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * Scope a query to filter photos by category.
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope a query to order photos by order field and creation date.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('created_at', 'desc');
    }

    /**
     * Get all image paths for this photo
     */
    public function getImagePaths(): array
    {
        return [
            'original' => $this->original_path,
            'large' => $this->web_path,
            'medium' => $this->medium_path,
            'small' => $this->small_path,
            'thumbnail' => $this->thumbnail_path,
            'original_webp' => $this->original_webp_path,
            'large_webp' => $this->web_webp_path,
            'medium_webp' => $this->medium_webp_path,
            'small_webp' => $this->small_webp_path,
            'thumbnail_webp' => $this->thumbnail_webp_path,
        ];
    }

    /**
     * Get responsive image HTML
     */
    public function getResponsiveImageHtml(string $class = '', bool $lazy = true): string
    {
        $imageService = app(\App\Services\ImageOptimizationService::class);

        return $imageService->generateResponsiveImage(
            $this->getImagePaths(),
            $this->title ?? 'Gallery Photo',
            $class,
            $lazy
        );
    }
}
