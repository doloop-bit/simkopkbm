<?php

namespace Database\Seeders;

use App\Models\Level;
use App\Models\Subject;
use Illuminate\Database\Seeder;

class SubjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $levels = Level::all()->keyBy('name');

        // Mata pelajaran untuk PAUD
        $paudSubjects = [
            ['name' => 'Nilai Agama & Budi Pekerti', 'code' => 'NABP'],
            ['name' => 'Fisik-Motorik', 'code' => 'FM'],
            ['name' => 'Kognitif', 'code' => 'KOG'],
            ['name' => 'Bahasa', 'code' => 'BHS'],
            ['name' => 'Sosial-Emosional', 'code' => 'SE'],
            ['name' => 'Seni & Kreativitas', 'code' => 'SNI'],
        ];

        // Mata pelajaran untuk Paket A (SD) — Kurikulum Merdeka
        $paketASubjects = [
            ['name' => 'Pendidikan Agama Islam', 'code' => 'PAI'],
            ['name' => 'Pendidikan Pancasila', 'code' => 'PPKN'],
            ['name' => 'Bahasa Indonesia', 'code' => 'BIND'],
            ['name' => 'Matematika', 'code' => 'MTK'],
            ['name' => 'IPAS', 'code' => 'IPAS'],
            ['name' => 'Seni Budaya', 'code' => 'SBD'],
            ['name' => 'PJOK', 'code' => 'PJOK'],
            ['name' => 'Bahasa Inggris', 'code' => 'BING'],
            ['name' => 'Informatika', 'code' => 'INF'],
            ['name' => 'Bahasa Jawa', 'code' => 'BJAW'],
        ];

        // Mata pelajaran untuk Paket B (SMP) — Kurikulum Merdeka
        $paketBSubjects = [
            ['name' => 'Pendidikan Agama Islam', 'code' => 'PAI'],
            ['name' => 'Pendidikan Pancasila', 'code' => 'PPKN'],
            ['name' => 'Bahasa Indonesia', 'code' => 'BIND'],
            ['name' => 'Matematika', 'code' => 'MTK'],
            ['name' => 'IPA', 'code' => 'IPA'],
            ['name' => 'IPS', 'code' => 'IPS'],
            ['name' => 'Bahasa Inggris', 'code' => 'BING'],
            ['name' => 'Seni Budaya', 'code' => 'SBD'],
            ['name' => 'PJOK', 'code' => 'PJOK'],
            ['name' => 'Informatika', 'code' => 'INF'],
            ['name' => 'Prakarya', 'code' => 'PRK'],
            ['name' => 'Bahasa Jawa', 'code' => 'BJAW'],
        ];

        // Mata pelajaran untuk Paket C (SMA) — Kurikulum Merdeka
        $paketCSubjects = [
            ['name' => 'Pendidikan Agama Islam', 'code' => 'PAI'],
            ['name' => 'Pendidikan Pancasila', 'code' => 'PPKN'],
            ['name' => 'Bahasa Indonesia', 'code' => 'BIND'],
            ['name' => 'Matematika', 'code' => 'MTK'],
            ['name' => 'Bahasa Inggris', 'code' => 'BING'],
            ['name' => 'Fisika', 'code' => 'FIS'],
            ['name' => 'Kimia', 'code' => 'KIM'],
            ['name' => 'Biologi', 'code' => 'BIO'],
            ['name' => 'Sejarah', 'code' => 'SEJ'],
            ['name' => 'Ekonomi', 'code' => 'EKO'],
            ['name' => 'Sosiologi', 'code' => 'SOS'],
            ['name' => 'Geografi', 'code' => 'GEO'],
            ['name' => 'Informatika', 'code' => 'INF'],
            ['name' => 'Bahasa Jawa', 'code' => 'BJAW'],
        ];

        // Map level name => subjects array & suffix
        $mapping = [
            'PAUD' => ['subjects' => $paudSubjects, 'suffix' => 'PD'],
            'Paket A' => ['subjects' => $paketASubjects, 'suffix' => 'PA'],
            'Paket B' => ['subjects' => $paketBSubjects, 'suffix' => 'PB'],
            'Paket C (Kelas 12)' => ['subjects' => $paketCSubjects, 'suffix' => 'PC'],
        ];

        foreach ($mapping as $levelName => $config) {
            if (!isset($levels[$levelName])) {
                $this->command->warn("Level '{$levelName}' not found, skipping...");
                continue;
            }

            $level = $levels[$levelName];

            foreach ($config['subjects'] as $subject) {
                Subject::firstOrCreate(
                    ['code' => $subject['code'] . '-' . $config['suffix']],
                    [
                        'name' => $subject['name'],
                        'level_id' => $level->id,
                    ]
                );
            }

            $this->command->info("✓ Created subjects for: {$levelName}");
        }
    }
}
