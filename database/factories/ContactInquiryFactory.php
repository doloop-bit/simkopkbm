<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ContactInquiry>
 */
class ContactInquiryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subjects = [
            'Informasi Pendaftaran',
            'Pertanyaan tentang Program PAUD',
            'Pertanyaan tentang Program Paket A',
            'Pertanyaan tentang Program Paket B',
            'Pertanyaan tentang Program Paket C',
            'Biaya Pendidikan',
            'Jadwal Pembelajaran',
            'Fasilitas Sekolah',
            'Kerjasama dan Kemitraan',
            'Informasi Umum',
            'Keluhan dan Saran',
            'Permintaan Informasi Ijazah',
        ];

        $messageTemplates = [
            'Selamat pagi/siang/sore. Saya ingin menanyakan tentang {subject}. Mohon informasinya. Terima kasih.',
            'Halo, saya tertarik untuk mendaftar di PKBM. Bisakah saya mendapatkan informasi lebih lanjut mengenai {subject}? Terima kasih.',
            'Assalamualaikum. Saya ingin bertanya tentang {subject}. Bagaimana prosedur dan persyaratannya? Mohon penjelasannya.',
            'Selamat pagi. Anak saya berminat untuk mengikuti program di PKBM. Saya ingin mengetahui lebih detail tentang {subject}. Terima kasih.',
            'Halo, saya sudah membaca informasi di website. Namun saya masih ingin menanyakan beberapa hal terkait {subject}. Mohon bantuannya.',
            'Selamat siang. Saya mendapat rekomendasi tentang PKBM ini. Saya ingin mengetahui lebih lanjut mengenai {subject}. Terima kasih sebelumnya.',
        ];

        $subject = fake()->randomElement($subjects);
        $messageTemplate = fake()->randomElement($messageTemplates);
        $message = str_replace('{subject}', strtolower($subject), $messageTemplate);

        return [
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->numerify('08##########'),
            'subject' => $subject,
            'message' => $message,
            'is_read' => false,
        ];
    }

    /**
     * Indicate that the inquiry has been read.
     */
    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_read' => true,
        ]);
    }

    /**
     * Indicate that the inquiry is unread.
     */
    public function unread(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_read' => false,
        ]);
    }

    /**
     * Create an inquiry with a specific subject.
     */
    public function subject(string $subject): static
    {
        return $this->state(fn (array $attributes) => [
            'subject' => $subject,
        ]);
    }
}
