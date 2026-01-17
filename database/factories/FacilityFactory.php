<?php

namespace Database\Factories;

use App\Models\SchoolProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Facility>
 */
class FacilityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $facilities = [
            [
                'name' => 'Ruang Kelas',
                'description' => 'Ruang kelas yang nyaman dan dilengkapi dengan fasilitas pembelajaran modern seperti proyektor, papan tulis, dan meja kursi yang ergonomis. Setiap ruang kelas dirancang untuk menciptakan suasana belajar yang kondusif.',
            ],
            [
                'name' => 'Perpustakaan',
                'description' => 'Perpustakaan dengan koleksi buku yang lengkap dan beragam, mulai dari buku pelajaran, buku referensi, hingga buku bacaan umum. Dilengkapi dengan ruang baca yang nyaman dan akses internet untuk mendukung kegiatan belajar.',
            ],
            [
                'name' => 'Laboratorium Komputer',
                'description' => 'Laboratorium komputer dengan perangkat yang modern dan terhubung dengan internet. Digunakan untuk pembelajaran teknologi informasi dan komunikasi serta mendukung kegiatan pembelajaran berbasis digital.',
            ],
            [
                'name' => 'Ruang Multimedia',
                'description' => 'Ruang multimedia yang dilengkapi dengan peralatan audio visual untuk mendukung pembelajaran yang interaktif dan menarik. Cocok untuk presentasi, seminar, dan kegiatan pembelajaran berbasis multimedia.',
            ],
            [
                'name' => 'Aula Serbaguna',
                'description' => 'Aula serbaguna yang luas dan nyaman, digunakan untuk berbagai kegiatan seperti upacara, pertemuan, seminar, dan acara-acara penting lainnya. Dilengkapi dengan sound system dan AC.',
            ],
            [
                'name' => 'Lapangan Olahraga',
                'description' => 'Lapangan olahraga yang luas untuk kegiatan pendidikan jasmani dan olahraga. Dapat digunakan untuk berbagai cabang olahraga seperti sepak bola, voli, basket, dan badminton.',
            ],
            [
                'name' => 'Kantin',
                'description' => 'Kantin yang bersih dan nyaman menyediakan berbagai makanan dan minuman sehat dengan harga terjangkau. Tempat yang ideal untuk peserta didik dan tutor beristirahat dan bersosialisasi.',
            ],
            [
                'name' => 'Musholla',
                'description' => 'Musholla yang bersih dan nyaman untuk kegiatan ibadah. Dilengkapi dengan tempat wudhu yang memadai dan perlengkapan ibadah yang lengkap.',
            ],
            [
                'name' => 'Ruang Guru',
                'description' => 'Ruang guru yang nyaman dilengkapi dengan meja kerja, lemari penyimpanan, dan fasilitas pendukung lainnya. Tempat yang ideal untuk tutor mempersiapkan materi pembelajaran dan beristirahat.',
            ],
            [
                'name' => 'Ruang Administrasi',
                'description' => 'Ruang administrasi yang tertata rapi untuk mengelola berbagai kegiatan administratif sekolah. Dilengkapi dengan komputer, printer, dan sistem filing yang terorganisir.',
            ],
            [
                'name' => 'Toilet',
                'description' => 'Toilet yang bersih dan terawat, terpisah untuk laki-laki dan perempuan. Dilengkapi dengan fasilitas yang memadai dan dijaga kebersihannya secara rutin.',
            ],
            [
                'name' => 'Parkir',
                'description' => 'Area parkir yang luas dan aman untuk kendaraan peserta didik, tutor, dan tamu. Dilengkapi dengan sistem keamanan dan petugas parkir.',
            ],
        ];

        $facility = fake()->randomElement($facilities);

        return [
            'school_profile_id' => SchoolProfile::factory(),
            'name' => $facility['name'],
            'description' => $facility['description'],
            'image_path' => null,
            'order' => fake()->numberBetween(1, 100),
        ];
    }

    /**
     * Indicate that the facility has an image.
     */
    public function withImage(): static
    {
        return $this->state(fn (array $attributes) => [
            'image_path' => 'facilities/'.fake()->uuid().'.jpg',
        ]);
    }

    /**
     * Create a facility with a specific name.
     */
    public function name(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
        ]);
    }
}
