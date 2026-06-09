<?php

namespace Database\Seeders;

use App\Models\PaudCpElement;
use Illuminate\Database\Seeder;

class PaudCpElementSeeder extends Seeder
{
    public function run(): void
    {
        $elements = [
            [
                'name' => 'Nilai Agama dan Budi Pekerti',
                'code' => 'agama',
                'description' => 'Mencakup kemampuan anak dalam mengenal Tuhan, melaksanakan ibadah, dan berperilaku baik sesuai ajaran agamanya.',
                'order' => 1,
            ],
            [
                'name' => 'Jati Diri',
                'code' => 'jati_diri',
                'description' => 'Mencakup identitas diri anak, kesehatan emosional dan sosial, kemandirian, serta motorik kasar dan halus.',
                'order' => 2,
            ],
            [
                'name' => 'Dasar-Dasar Literasi, Matematika, Sains, Teknologi, Rekayasa, dan Seni',
                'code' => 'literasi_steam',
                'description' => 'Mencakup kemampuan anak dalam berkomunikasi, bernalar kritis, memecahkan masalah, berkreasi, dan mengenal konsep STEAM sederhana.',
                'order' => 3,
            ],
        ];

        foreach ($elements as $element) {
            PaudCpElement::updateOrCreate(['code' => $element['code']], $element);
        }
    }
}
