<?php

namespace Database\Factories;

use App\Models\Level;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Program>
 */
class ProgramFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $descriptions = [
            'Program pendidikan untuk anak usia 3-6 tahun yang dirancang untuk mengembangkan potensi anak sejak dini.',
            'Program pendidikan kesetaraan yang memberikan kesempatan kepada masyarakat untuk menyelesaikan pendidikan.',
            'Program pendidikan kesetaraan yang dirancang untuk memberikan kesempatan melanjutkan pendidikan.',
            'Program pendidikan kesetaraan yang memberikan kesempatan menyelesaikan pendidikan menengah.',
        ];

        $name = fake()->unique()->words(3, true);

        return [
            'level_id' => Level::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->randomNumber(4),
            'description' => fake()->randomElement($descriptions),
            'curriculum_overview' => fake()->optional()->paragraph(),
            'duration' => fake()->randomElement(['1 tahun', '2 tahun', '2-3 tahun', '3 tahun']),
            'requirements' => fake()->optional()->sentence(),
            'image_path' => null,
            'order' => fake()->numberBetween(1, 10),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the program is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the program has an image.
     */
    public function withImage(): static
    {
        return $this->state(fn (array $attributes) => [
            'image_path' => 'programs/'.fake()->uuid().'.jpg',
        ]);
    }

    /**
     * Create a program for a specific level.
     */
    public function forLevel(Level $level): static
    {
        return $this->state(fn (array $attributes) => [
            'level_id' => $level->id,
            'name' => $level->name,
            'slug' => Str::slug($level->name).'-'.fake()->unique()->randomNumber(4),
        ]);
    }
}
