<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SchoolProfile>
 */
class SchoolProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $schoolNames = [
            'PKBM Harapan Bangsa',
            'PKBM Tunas Mekar',
            'PKBM Cahaya Ilmu',
            'PKBM Bina Insan',
            'PKBM Karya Mandiri',
            'PKBM Mitra Sejahtera',
            'PKBM Cendekia',
            'PKBM Pelita Hati',
        ];

        $visions = [
            'Menjadi lembaga pendidikan nonformal terdepan yang menghasilkan lulusan berkualitas, berkarakter, dan berdaya saing tinggi.',
            'Terwujudnya pusat kegiatan belajar masyarakat yang unggul dalam memberikan layanan pendidikan berkualitas bagi seluruh lapisan masyarakat.',
            'Menjadi PKBM yang inovatif dan profesional dalam menyelenggarakan pendidikan kesetaraan dan keterampilan hidup.',
            'Menciptakan generasi yang cerdas, terampil, dan berakhlak mulia melalui pendidikan nonformal yang berkualitas.',
        ];

        $missions = [
            "1. Menyelenggarakan pendidikan kesetaraan yang berkualitas dan terjangkau\n2. Mengembangkan kurikulum yang relevan dengan kebutuhan masyarakat\n3. Meningkatkan kompetensi tenaga pendidik dan kependidikan\n4. Membangun kemitraan dengan berbagai pihak untuk pengembangan lembaga\n5. Menciptakan lingkungan belajar yang kondusif dan menyenangkan",
            "1. Memberikan akses pendidikan yang luas kepada masyarakat\n2. Mengembangkan program pendidikan yang inovatif dan berkualitas\n3. Memberdayakan masyarakat melalui pendidikan dan pelatihan keterampilan\n4. Membangun karakter peserta didik yang berakhlak mulia\n5. Meningkatkan kualitas sarana dan prasarana pendidikan",
            "1. Menyelenggarakan program pendidikan kesetaraan PAUD, Paket A, B, dan C\n2. Mengembangkan program keterampilan hidup yang aplikatif\n3. Meningkatkan profesionalisme tenaga pendidik\n4. Membangun jejaring kerjasama dengan stakeholder\n5. Menciptakan budaya belajar sepanjang hayat",
        ];

        $histories = [
            'PKBM {name} didirikan pada tahun 2010 dengan tujuan memberikan akses pendidikan kepada masyarakat yang tidak dapat mengikuti pendidikan formal.',
            'Berdiri sejak tahun 2008, PKBM {name} hadir sebagai solusi pendidikan alternatif bagi masyarakat.',
        ];

        $name = fake()->randomElement($schoolNames);

        return [
            'name' => $name,
            'address' => fake()->streetAddress().', '.fake()->city().', '.fake()->state().' '.fake()->postcode(),
            'phone' => fake()->numerify('(0###) ####-####'),
            'email' => strtolower(str_replace(' ', '', $name)).'@example.com',
            'vision' => fake()->randomElement($visions),
            'mission' => fake()->randomElement($missions),
            'history' => [
                [
                    'year' => '2010',
                    'title' => 'Pendirian',
                    'description' => str_replace('{name}', $name, fake()->randomElement($histories)),
                    'image_path' => null,
                ],
                [
                    'year' => '2015',
                    'title' => 'Akreditasi',
                    'description' => 'Mendapatkan akreditasi A untuk program Paket C.',
                    'image_path' => null,
                ],
            ],
            'operating_hours' => 'Senin - Jumat: 08.00 - 16.00 WIB, Sabtu: 08.00 - 12.00 WIB',
            'facebook_url' => fake()->optional(0.7)->url(),
            'instagram_url' => fake()->optional(0.7)->url(),
            'youtube_url' => fake()->optional(0.5)->url(),
            'twitter_url' => fake()->optional(0.3)->url(),
            'latitude' => fake()->latitude(-10, 5),
            'longitude' => fake()->longitude(95, 141),
            'logo_path' => null,
            'is_active' => false,
        ];
    }

    /**
     * Indicate that the school profile is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the school profile has a logo.
     */
    public function withLogo(): static
    {
        return $this->state(fn (array $attributes) => [
            'logo_path' => 'school-profile/'.fake()->uuid().'.png',
        ]);
    }

    /**
     * Indicate that the school profile has social media links.
     */
    public function withSocialMedia(): static
    {
        return $this->state(fn (array $attributes) => [
            'facebook_url' => 'https://facebook.com/'.fake()->userName(),
            'instagram_url' => 'https://instagram.com/'.fake()->userName(),
            'youtube_url' => 'https://youtube.com/@'.fake()->userName(),
            'twitter_url' => 'https://twitter.com/'.fake()->userName(),
        ]);
    }
}
