<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NewsArticle extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'excerpt',
        'featured_image_path',
        'published_at',
        'status',
        'meta_title',
        'meta_description',
        'author_id',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    /**
     * Get the author of the news article.
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Get the images associated with the news article.
     */
    public function images(): HasMany
    {
        return $this->hasMany(NewsImage::class);
    }

    /**
     * Scope a query to only include published articles.
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * Scope a query to order articles by publication date descending.
     */
    public function scopeLatestPublished($query)
    {
        return $query->orderByDesc('published_at');
    }

    /**
     * Scope a query to order articles by latest (published_at or created_at).
     */
    public function scopeLatest($query)
    {
        return $query->orderByDesc('published_at')->orderByDesc('created_at');
    }

    /**
     * Check if the article is published.
     */
    public function isPublished(): bool
    {
        return $this->status === 'published'
            && $this->published_at !== null
            && $this->published_at->isPast();
    }
}
