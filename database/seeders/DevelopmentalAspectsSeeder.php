<?php

namespace Database\Seeders;

use App\Models\DevelopmentalAspect;
use Illuminate\Database\Seeder;

class DevelopmentalAspectsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $aspects = [
            // 1. Nilai Agama dan Budi Pekerti
            [
                'aspect_type' => 'nilai_agama',
                'name' => 'Mengenal Agama yang Dianut',
                'description' => 'Anak mampu mengenal dan mempraktikkan ajaran agama yang dianutnya',
            ],
            [
                'aspect_type' => 'nilai_agama',
                'name' => 'Mengerjakan Ibadah',
                'description' => 'Anak mampu melakukan ibadah sesuai dengan agamanya',
            ],
            [
                'aspect_type' => 'nilai_agama',
                'name' => 'Berperilaku Jujur, Penolong, Sopan, Hormat',
                'description' => 'Anak menunjukkan perilaku baik dalam kehidupan sehari-hari',
            ],

            // 2. Fisik-Motorik
            [
                'aspect_type' => 'fisik_motorik',
                'name' => 'Motorik Kasar',
                'description' => 'Kemampuan menggunakan otot-otot besar (berlari, melompat, memanjat)',
            ],
            [
                'aspect_type' => 'fisik_motorik',
                'name' => 'Motorik Halus',
                'description' => 'Kemampuan menggunakan otot-otot kecil (menulis, menggambar, menggunting)',
            ],
            [
                'aspect_type' => 'fisik_motorik',
                'name' => 'Kesehatan dan Perilaku Keselamatan',
                'description' => 'Pemahaman tentang kesehatan dan keselamatan diri',
            ],

            // 3. Kognitif
            [
                'aspect_type' => 'kognitif',
                'name' => 'Belajar dan Pemecahan Masalah',
                'description' => 'Kemampuan berpikir logis dan memecahkan masalah sederhana',
            ],
            [
                'aspect_type' => 'kognitif',
                'name' => 'Berpikir Logis',
                'description' => 'Kemampuan menalar dan memahami sebab-akibat',
            ],
            [
                'aspect_type' => 'kognitif',
                'name' => 'Berpikir Simbolik',
                'description' => 'Kemampuan mengenal simbol, huruf, dan angka',
            ],

            // 4. Bahasa
            [
                'aspect_type' => 'bahasa',
                'name' => 'Memahami Bahasa',
                'description' => 'Kemampuan memahami bahasa lisan dan tulisan',
            ],
            [
                'aspect_type' => 'bahasa',
                'name' => 'Mengungkapkan Bahasa',
                'description' => 'Kemampuan berkomunikasi secara lisan',
            ],
            [
                'aspect_type' => 'bahasa',
                'name' => 'Keaksaraan',
                'description' => 'Kemampuan mengenal huruf dan membaca sederhana',
            ],

            // 5. Sosial-Emosional
            [
                'aspect_type' => 'sosial_emosional',
                'name' => 'Kesadaran Diri',
                'description' => 'Pemahaman tentang diri sendiri dan perasaan',
            ],
            [
                'aspect_type' => 'sosial_emosional',
                'name' => 'Rasa Tanggung Jawab untuk Diri dan Orang Lain',
                'description' => 'Kemampuan bertanggung jawab dan peduli terhadap orang lain',
            ],
            [
                'aspect_type' => 'sosial_emosional',
                'name' => 'Perilaku Prososial',
                'description' => 'Kemampuan berinteraksi dan bekerja sama dengan teman',
            ],

            // 6. Seni
            [
                'aspect_type' => 'seni',
                'name' => 'Mengeksplorasi dan Mengekspresikan Diri',
                'description' => 'Kemampuan berekspresi melalui seni (menggambar, menyanyi, menari)',
            ],
            [
                'aspect_type' => 'seni',
                'name' => 'Berimajinasi dengan Gerakan, Musik, Drama, dan Beragam Bidang Seni',
                'description' => 'Kemampuan berkreasi dan berimajinasi dalam berbagai bentuk seni',
            ],
        ];

        foreach ($aspects as $aspect) {
            DevelopmentalAspect::create($aspect);
        }
    }
}
