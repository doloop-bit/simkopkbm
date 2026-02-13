<?php

namespace App\Services;

use Intervention\Image\Encoders\WebpEncoder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class ImageOptimizationService
{
    /**
     * Process and optimize an uploaded image
     */
    public function processImage(UploadedFile $file, string $directory, array $sizes = []): array
    {
        $filename = uniqid() . '.' . $file->extension();
        $webpFilename = uniqid() . '.webp';
        
        // Default sizes if none provided
        if (empty($sizes)) {
            $sizes = [
                'original' => null, // Keep original size
                'large' => 1200,
                'medium' => 800,
                'small' => 400,
                'thumbnail' => 300,
            ];
        }

        $paths = [];
        
        foreach ($sizes as $sizeName => $width) {
            // Create JPEG/PNG version
            $image = Image::read($file->getRealPath());
            
            if ($width && $sizeName !== 'original') {
                if ($sizeName === 'thumbnail') {
                    // Square thumbnail
                    $image = $image->cover($width, $width);
                } else {
                    // Responsive width
                    $image = $image->scaleDown(width: $width);
                }
            }
            
            $originalPath = "{$directory}/{$sizeName}/{$filename}";
            Storage::disk('public')->put($originalPath, $image->encode());
            $paths[$sizeName] = $originalPath;
            
            // Create WebP version
            $webpImage = Image::read($file->getRealPath());
            
            if ($width && $sizeName !== 'original') {
                if ($sizeName === 'thumbnail') {
                    $webpImage = $webpImage->cover($width, $width);
                } else {
                    $webpImage = $webpImage->scaleDown(width: $width);
                }
            }
            
            $webpPath = "{$directory}/{$sizeName}/{$webpFilename}";
            Storage::disk('public')->put($webpPath, $webpImage->encode(new WebpEncoder(quality: 85)));
            $paths["{$sizeName}_webp"] = $webpPath;
        }
        
        return $paths;
    }

    /**
     * Generate responsive image HTML with WebP support and lazy loading
     */
    public function generateResponsiveImage(
        array $imagePaths,
        string $alt,
        string $class = '',
        bool $lazy = true,
        array $breakpoints = []
    ): string {
        if (empty($breakpoints)) {
            $breakpoints = [
                'small' => '(max-width: 640px)',
                'medium' => '(max-width: 1024px)',
                'large' => '(min-width: 1025px)',
            ];
        }

        $sources = [];
        
        // Generate WebP sources
        foreach ($breakpoints as $size => $media) {
            if (isset($imagePaths["{$size}_webp"])) {
                $sources[] = sprintf(
                    '<source media="%s" srcset="%s" type="image/webp">',
                    $media,
                    Storage::url($imagePaths["{$size}_webp"])
                );
            }
        }
        
        // Generate fallback sources
        foreach ($breakpoints as $size => $media) {
            if (isset($imagePaths[$size])) {
                $sources[] = sprintf(
                    '<source media="%s" srcset="%s">',
                    $media,
                    Storage::url($imagePaths[$size])
                );
            }
        }
        
        // Fallback image
        $fallbackSrc = isset($imagePaths['medium']) 
            ? Storage::url($imagePaths['medium'])
            : (isset($imagePaths['large']) ? Storage::url($imagePaths['large']) : Storage::url($imagePaths['original']));
        
        $lazyAttributes = $lazy ? 'loading="lazy" decoding="async"' : '';
        
        return sprintf(
            '<picture>%s<img src="%s" alt="%s" class="%s" %s></picture>',
            implode('', $sources),
            $fallbackSrc,
            htmlspecialchars($alt),
            $class,
            $lazyAttributes
        );
    }

    /**
     * Delete all versions of an image
     */
    public function deleteImageVersions(array $imagePaths): void
    {
        $pathsToDelete = array_filter($imagePaths);
        
        if (!empty($pathsToDelete)) {
            Storage::disk('public')->delete($pathsToDelete);
        }
    }

    /**
     * Generate srcset for responsive images
     */
    public function generateSrcSet(array $imagePaths, bool $webp = false): string
    {
        $srcset = [];
        $suffix = $webp ? '_webp' : '';
        
        $sizeMap = [
            'small' => '400w',
            'medium' => '800w',
            'large' => '1200w',
        ];
        
        foreach ($sizeMap as $size => $descriptor) {
            $key = $size . $suffix;
            if (isset($imagePaths[$key])) {
                $srcset[] = Storage::url($imagePaths[$key]) . ' ' . $descriptor;
            }
        }
        
        return implode(', ', $srcset);
    }
}