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
        return [
            'level_id' => Level::factory(),
            'name' => 'Program '.fake()->company(),
            'slug' => fn (array $attributes) => Str::slug($attributes['name']).'-'.fake()->unique()->randomNumber(4),
            'description' => fake()->paragraph(),
            'curriculum_overview' => 'Fokus pada pengembangan karakter, literasi numerasi, dan penguatan Profil Pelajar Pancasila.',
            'duration' => fake()->randomElement(['1 Tahun', '2 Semester', '6 Bulan (Intensif)']),
            'requirements' => 'FC Ijazah Terakhir, Akta Kelahiran, Kartu Keluarga, dan Pas Foto 3x4.',
            'image_path' => null,
            'logo_path' => null,
            'order' => fake()->unique()->numberBetween(1, 100),
            'is_active' => true,
        ];
    }

    /**
     * Set a realistic program based on education type.
     */
    public function levelSpecific(): static
    {
        return $this->state(function (array $attributes) {
            $level = Level::find($attributes['level_id']) ?? Level::factory()->create();
            $name = 'Program '.$level->name.' '.fake()->randomElement(['Unggulan', 'Reguler', 'Intensif', 'Digital']);

            $desc = match (strtolower($level->education_level)) {
                'paud' => 'Fokus pada pengembangan motorik, sosial-emosional, dan persiapan transisi menuju pendidikan dasar.',
                'sd' => 'Penguatan literasi dasar, numerasi, dan karakter melalui pendekatan Kurikulum Merdeka yang menyenangkan.',
                'smp' => 'Pendalaman materi akademik dasar dan pengembangan minat bakat melalui berbagai proyek kreatif.',
                'sma' => 'Persiapan matang menuju pendidikan tinggi atau dunia kerja sesuai dengan minat dan bakat peserta didik.',
                default => 'Program pendidikan fleksibel dengan pendekatan pembelajaran orang dewasa (Andragogi).',
            };

            return [
                'name' => $name,
                'description' => $desc,
                'requirements' => $level->education_level === 'paud' ? 'FC Akta & KK' : 'FC Ijazah Sebelumnya, Akta & KK',
            ];
        });
    }

    /**
     * Indicate that the program has a logo and image.
     */
    public function withBranding(): static
    {
        return $this->state(fn (array $attributes) => [
            'image_path' => 'programs/'.fake()->uuid().'.jpg',
            'logo_path' => 'programs/logos/'.fake()->uuid().'.png',
        ]);
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

    public function forLevel(Level $level): static
    {
        return $this->state(fn (array $attributes) => [
            'level_id' => $level->id,
            'name' => $level->name,
            'slug' => Str::slug($level->name),
        ]);
    }

    public function paud(): static
    {
        return $this->state(fn (array $attributes) => [
            'level_id' => Level::factory()->state(['education_level' => 'paud', 'name' => 'PAUD']),
            'name' => 'Program PAUD',
            'order' => 1,
        ]);
    }

    public function paketA(): static
    {
        return $this->state(fn (array $attributes) => [
            'level_id' => Level::factory()->state(['education_level' => 'sd', 'name' => 'PAKET A']),
            'name' => 'Program Paket A',
            'order' => 2,
        ]);
    }

    public function paketB(): static
    {
        return $this->state(fn (array $attributes) => [
            'level_id' => Level::factory()->state(['education_level' => 'smp', 'name' => 'PAKET B']),
            'name' => 'Program Paket B',
            'order' => 3,
        ]);
    }

    public function paketC(): static
    {
        return $this->state(fn (array $attributes) => [
            'level_id' => Level::factory()->state(['education_level' => 'sma', 'name' => 'PAKET C']),
            'name' => 'Program Paket C',
            'order' => 4,
        ]);
    }
}
