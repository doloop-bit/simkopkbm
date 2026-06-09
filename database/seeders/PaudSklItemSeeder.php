<?php

namespace Database\Seeders;

use App\Models\PaudSklItem;
use Illuminate\Database\Seeder;

class PaudSklItemSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'name' => 'Keimanan dan Ketakwaan terhadap Tuhan Yang Maha Esa',
                'code' => 'keimanan',
                'description' => 'Mengenal ajaran agama, mempraktikkan ibadah, menyayangi ciptaan Tuhan, menghormati perbedaan.',
                'order' => 1,
            ],
            [
                'name' => 'Kewargaan dan Kebangsaan',
                'code' => 'kewargaan',
                'description' => 'Memiliki identitas diri, bangga sebagai anak Indonesia, memahami hak dan kewajiban sederhana.',
                'order' => 2,
            ],
            [
                'name' => 'Penalaran Kritis dan Pemecahan Masalah',
                'code' => 'penalaran_kritis',
                'description' => 'Mengamati, bertanya, mencoba memecahkan masalah sehari-hari, mengambil keputusan sederhana.',
                'order' => 3,
            ],
            [
                'name' => 'Kreativitas dan Estetika',
                'code' => 'kreativitas',
                'description' => 'Mengekspresikan diri melalui seni, menghasilkan karya orisinal, berimajinasi.',
                'order' => 4,
            ],
            [
                'name' => 'Kolaborasi dan Gotong Royong',
                'code' => 'kolaborasi',
                'description' => 'Bekerja sama dengan teman, berbagi, membantu orang lain, berempati.',
                'order' => 5,
            ],
            [
                'name' => 'Kemandirian dan Regulasi Diri',
                'code' => 'kemandirian',
                'description' => 'Mengelola emosi diri, merawat diri secara mandiri, gigih menyelesaikan tugas.',
                'order' => 6,
            ],
            [
                'name' => 'Kesehatan dan Kesejahteraan Jasmani',
                'code' => 'kesehatan',
                'description' => 'Melakukan gerakan motorik kasar/halus, memahami kebiasaan hidup bersih dan sehat, menjaga keselamatan diri.',
                'order' => 7,
            ],
            [
                'name' => 'Komunikasi dan Bahasa',
                'code' => 'komunikasi',
                'description' => 'Mengungkapkan ide/perasaan secara lisan, menyimak pembicaraan, menyukai buku/keaksaraan awal.',
                'order' => 8,
            ],
        ];

        foreach ($items as $item) {
            PaudSklItem::updateOrCreate(['code' => $item['code']], $item);
        }
    }
}
