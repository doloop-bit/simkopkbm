<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GalleryPhoto>
 */
class GalleryPhotoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = [
            'Kegiatan Belajar',
            'Upacara',
            'Olahraga',
            'Seni dan Budaya',
            'Kegiatan Ekstrakurikuler',
            'Fasilitas',
            'Wisuda',
            'Kunjungan',
        ];

        $filename = fake()->uuid().'.jpg';

        return [
            'title' => fake()->sentence(3),
            'caption' => fake()->optional(0.7)->sentence(),
            'category' => fake()->randomElement($categories),
            'original_path' => 'gallery/original/'.$filename,
            'thumbnail_path' => 'gallery/thumbnails/'.$filename,
            'web_path' => 'gallery/web/'.$filename,
            'order' => fake()->numberBetween(0, 100),
            'is_published' => true,
        ];
    }

    /**
     * Indicate that the photo is unpublished.
     */
    public function unpublished(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => false,
        ]);
    }

    /**
     * Indicate that the photo belongs to a specific category.
     */
    public function category(string $category): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => $category,
        ]);
    }
}
