<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NewsArticle>
 */
class NewsArticleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $titles = [
            'Kegiatan Belajar Mengajar Semester Baru Dimulai dengan Semangat',
            'PKBM Raih Prestasi dalam Lomba Pendidikan Nonformal Tingkat Provinsi',
            'Pelaksanaan Ujian Kesetaraan Paket C Berjalan Lancar',
            'Pelatihan Keterampilan Komputer untuk Peserta Didik',
            'Kunjungan Dinas Pendidikan ke PKBM',
            'Wisuda Angkatan ke-15 Dihadiri Ratusan Lulusan',
            'Program Literasi Digital untuk Meningkatkan Kompetensi Peserta Didik',
            'Kegiatan Bakti Sosial PKBM di Lingkungan Sekitar',
            'Seminar Motivasi: Meraih Masa Depan Cerah Melalui Pendidikan',
            'Pendaftaran Peserta Didik Baru Tahun Ajaran 2024/2025',
            'Peringatan Hari Pendidikan Nasional di PKBM',
            'Lomba Kreativitas Peserta Didik PAUD',
            'Kerjasama dengan Dunia Usaha untuk Program Magang',
            'Pelatihan Guru: Metode Pembelajaran Inovatif',
            'Kegiatan Outing Class ke Museum Pendidikan',
        ];

        $contentTemplates = [
            "Kegiatan ini diselenggarakan dengan tujuan untuk meningkatkan kualitas pembelajaran dan memberikan pengalaman belajar yang bermakna bagi peserta didik. Seluruh tutor dan staf PKBM berkomitmen untuk memberikan layanan pendidikan terbaik.\n\nDalam kegiatan ini, peserta didik menunjukkan antusiasme yang tinggi dan partisipasi aktif. Berbagai metode pembelajaran inovatif diterapkan untuk memastikan materi tersampaikan dengan efektif.\n\nKepala PKBM menyampaikan apresiasi kepada seluruh pihak yang telah mendukung terlaksananya kegiatan ini. Diharapkan kegiatan serupa dapat terus dilaksanakan untuk meningkatkan mutu pendidikan.\n\nKegiatan ditutup dengan harapan agar peserta didik dapat mengaplikasikan ilmu yang diperoleh dalam kehidupan sehari-hari dan terus semangat dalam menuntut ilmu.",
            "Acara berlangsung dengan meriah dan dihadiri oleh berbagai pihak termasuk orang tua peserta didik, alumni, dan mitra PKBM. Kegiatan ini menjadi momentum penting dalam perjalanan pendidikan di PKBM.\n\nDalam sambutannya, Kepala PKBM menyampaikan terima kasih kepada seluruh pihak yang telah berkontribusi. Beliau juga menekankan pentingnya pendidikan sebagai kunci kesuksesan masa depan.\n\nPeserta didik terlihat sangat antusias mengikuti seluruh rangkaian acara. Mereka mendapatkan banyak pengetahuan dan pengalaman berharga yang akan berguna untuk pengembangan diri.\n\nKegiatan ini diharapkan dapat memberikan dampak positif dan menjadi motivasi bagi peserta didik untuk terus berprestasi dan mengembangkan potensi diri.",
            "Pelaksanaan kegiatan ini merupakan bagian dari program rutin PKBM dalam upaya meningkatkan kualitas pendidikan. Seluruh rangkaian acara berjalan dengan lancar dan tertib.\n\nPara tutor dan staf PKBM telah mempersiapkan segala sesuatunya dengan matang. Koordinasi yang baik antar tim memastikan kegiatan berjalan sesuai rencana.\n\nAntusiasme peserta didik sangat tinggi, terlihat dari partisipasi aktif mereka dalam setiap sesi. Mereka menunjukkan semangat belajar yang patut diapresiasi.\n\nKepala PKBM berharap kegiatan ini dapat memberikan manfaat maksimal bagi peserta didik dan menjadi bekal untuk masa depan mereka yang lebih baik.",
        ];

        $title = fake()->randomElement($titles);
        $content = fake()->randomElement($contentTemplates);
        $uniqueSuffix = fake()->unique()->numberBetween(1000, 9999);

        return [
            'title' => $title,
            'slug' => Str::slug($title).'-'.$uniqueSuffix,
            'content' => $content,
            'excerpt' => Str::limit(strip_tags($content), 200),
            'featured_image_path' => null,
            'published_at' => fake()->dateTimeBetween('-6 months', 'now'),
            'status' => 'published',
            'meta_title' => null,
            'meta_description' => null,
            'author_id' => User::factory(),
        ];
    }

    /**
     * Indicate that the article is in draft status.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'published_at' => null,
        ]);
    }

    /**
     * Indicate that the article is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
            'published_at' => fake()->dateTimeBetween('-6 months', 'now'),
        ]);
    }

    /**
     * Indicate that the article has a featured image.
     */
    public function withFeaturedImage(): static
    {
        return $this->state(fn (array $attributes) => [
            'featured_image_path' => 'news/'.fake()->uuid().'.jpg',
        ]);
    }

    /**
     * Indicate that the article has SEO metadata.
     */
    public function withSeoMetadata(): static
    {
        return $this->state(fn (array $attributes) => [
            'meta_title' => fake()->sentence(8),
            'meta_description' => fake()->sentence(15),
        ]);
    }
}
