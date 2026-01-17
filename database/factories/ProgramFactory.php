<?php

namespace Database\Factories;

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
        $programs = [
            'paud' => [
                'name' => 'PAUD (Pendidikan Anak Usia Dini)',
                'description' => 'Program pendidikan untuk anak usia 3-6 tahun yang dirancang untuk mengembangkan potensi anak sejak dini melalui pembelajaran yang menyenangkan dan bermakna.',
                'curriculum_overview' => "Program PAUD kami mencakup:\n- Pengembangan nilai agama dan moral\n- Pengembangan fisik motorik\n- Pengembangan kognitif\n- Pengembangan bahasa\n- Pengembangan sosial emosional\n- Pengembangan seni",
                'duration' => '3 tahun (usia 3-6 tahun)',
                'requirements' => "- Usia minimal 3 tahun\n- Fotokopi akta kelahiran\n- Fotokopi KK\n- Pas foto 3x4 (2 lembar)\n- Surat keterangan sehat dari dokter",
            ],
            'paket_a' => [
                'name' => 'Paket A (Setara SD/MI)',
                'description' => 'Program pendidikan kesetaraan setara SD/MI yang memberikan kesempatan kepada masyarakat untuk menyelesaikan pendidikan dasar dengan fleksibilitas waktu belajar.',
                'curriculum_overview' => "Kurikulum Paket A meliputi:\n- Pendidikan Agama dan Budi Pekerti\n- Pendidikan Kewarganegaraan\n- Bahasa Indonesia\n- Matematika\n- Ilmu Pengetahuan Alam\n- Ilmu Pengetahuan Sosial\n- Seni Budaya dan Prakarya\n- Pendidikan Jasmani, Olahraga, dan Kesehatan",
                'duration' => '2-3 tahun',
                'requirements' => "- Usia minimal 7 tahun\n- Fotokopi ijazah terakhir (jika ada)\n- Fotokopi KTP/KK\n- Pas foto 3x4 (4 lembar)\n- Surat keterangan dari RT/RW",
            ],
            'paket_b' => [
                'name' => 'Paket B (Setara SMP/MTs)',
                'description' => 'Program pendidikan kesetaraan setara SMP/MTs yang dirancang untuk memberikan kesempatan melanjutkan pendidikan bagi mereka yang tidak dapat mengikuti pendidikan formal.',
                'curriculum_overview' => "Kurikulum Paket B mencakup:\n- Pendidikan Agama dan Budi Pekerti\n- Pendidikan Kewarganegaraan\n- Bahasa Indonesia\n- Bahasa Inggris\n- Matematika\n- Ilmu Pengetahuan Alam\n- Ilmu Pengetahuan Sosial\n- Seni Budaya\n- Pendidikan Jasmani, Olahraga, dan Kesehatan\n- Prakarya dan Kewirausahaan",
                'duration' => '2-3 tahun',
                'requirements' => "- Usia minimal 13 tahun atau lulusan SD/MI\n- Fotokopi ijazah SD/MI\n- Fotokopi KTP/KK\n- Pas foto 3x4 (4 lembar)\n- Surat keterangan dari RT/RW",
            ],
            'paket_c' => [
                'name' => 'Paket C (Setara SMA/MA)',
                'description' => 'Program pendidikan kesetaraan setara SMA/MA yang memberikan kesempatan kepada masyarakat untuk menyelesaikan pendidikan menengah dengan sistem pembelajaran yang fleksibel.',
                'curriculum_overview' => "Kurikulum Paket C meliputi:\n- Pendidikan Agama dan Budi Pekerti\n- Pendidikan Kewarganegaraan\n- Bahasa Indonesia\n- Bahasa Inggris\n- Matematika\n- Sejarah Indonesia\n- Mata pelajaran peminatan (IPA/IPS/Bahasa)\n- Seni Budaya\n- Pendidikan Jasmani, Olahraga, dan Kesehatan\n- Prakarya dan Kewirausahaan",
                'duration' => '2-3 tahun',
                'requirements' => "- Usia minimal 16 tahun atau lulusan SMP/MTs\n- Fotokopi ijazah SMP/MTs\n- Fotokopi KTP/KK\n- Pas foto 3x4 (4 lembar)\n- Surat keterangan dari RT/RW",
            ],
        ];

        $level = fake()->randomElement(['paud', 'paket_a', 'paket_b', 'paket_c']);
        $programData = $programs[$level];

        return [
            'name' => $programData['name'],
            'slug' => Str::slug($programData['name']) . '-' . fake()->unique()->randomNumber(4),
            'level' => $level,
            'description' => $programData['description'],
            'curriculum_overview' => $programData['curriculum_overview'],
            'duration' => $programData['duration'],
            'requirements' => $programData['requirements'],
            'image_path' => null,
            'order' => match ($level) {
                'paud' => 1,
                'paket_a' => 2,
                'paket_b' => 3,
                'paket_c' => 4,
            },
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
     * Create a PAUD program.
     */
    public function paud(): static
    {
        return $this->state(fn (array $attributes) => [
            'level' => 'paud',
            'name' => 'PAUD (Pendidikan Anak Usia Dini)',
            'slug' => 'paud-pendidikan-anak-usia-dini-' . fake()->unique()->randomNumber(4),
            'order' => 1,
        ]);
    }

    /**
     * Create a Paket A program.
     */
    public function paketA(): static
    {
        return $this->state(fn (array $attributes) => [
            'level' => 'paket_a',
            'name' => 'Paket A (Setara SD/MI)',
            'slug' => 'paket-a-setara-sd-mi-' . fake()->unique()->randomNumber(4),
            'order' => 2,
        ]);
    }

    /**
     * Create a Paket B program.
     */
    public function paketB(): static
    {
        return $this->state(fn (array $attributes) => [
            'level' => 'paket_b',
            'name' => 'Paket B (Setara SMP/MTs)',
            'slug' => 'paket-b-setara-smp-mts-' . fake()->unique()->randomNumber(4),
            'order' => 3,
        ]);
    }

    /**
     * Create a Paket C program.
     */
    public function paketC(): static
    {
        return $this->state(fn (array $attributes) => [
            'level' => 'paket_c',
            'name' => 'Paket C (Setara SMA/MA)',
            'slug' => 'paket-c-setara-sma-ma-' . fake()->unique()->randomNumber(4),
            'order' => 4,
        ]);
    }
}
