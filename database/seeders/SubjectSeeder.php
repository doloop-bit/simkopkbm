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

        // Mata pelajaran untuk PAUD/TK (class_teacher)
        $paudSubjects = [
            ['name' => 'Pendidikan Agama', 'code' => 'PAI-PAUD'],
            ['name' => 'Bahasa Indonesia', 'code' => 'BIN-PAUD'],
            ['name' => 'Motorik Halus', 'code' => 'MH-PAUD'],
            ['name' => 'Motorik Kasar', 'code' => 'MK-PAUD'],
            ['name' => 'Kognitif', 'code' => 'KOG-PAUD'],
            ['name' => 'Seni & Kreativitas', 'code' => 'SNI-PAUD'],
        ];

        // Mata pelajaran untuk Paket A (SD)
        $paketASubjects = [
            ['name' => 'Pendidikan Agama Islam', 'code' => 'PAI'],
            ['name' => 'Pendidikan Pancasila', 'code' => 'PPKN'],
            ['name' => 'Bahasa Indonesia', 'code' => 'BIND'],
            ['name' => 'Matematika', 'code' => 'MTK'],
            ['name' => 'IPA', 'code' => 'IPA'],
            ['name' => 'IPS', 'code' => 'IPS'],
            ['name' => 'Seni Budaya', 'code' => 'SBD'],
            ['name' => 'PJOK', 'code' => 'PJOK'],
            ['name' => 'Bahasa Inggris', 'code' => 'BING'],
        ];

        // Mata pelajaran untuk Paket B (SMP)
        $paketBSubjects = [
            ['name' => 'Pendidikan Agama Islam', 'code' => 'PAI-B'],
            ['name' => 'Pendidikan Pancasila', 'code' => 'PPKN-B'],
            ['name' => 'Bahasa Indonesia', 'code' => 'BIND-B'],
            ['name' => 'Matematika', 'code' => 'MTK-B'],
            ['name' => 'IPA Terpadu', 'code' => 'IPA-B'],
            ['name' => 'IPS Terpadu', 'code' => 'IPS-B'],
            ['name' => 'Bahasa Inggris', 'code' => 'BING-B'],
            ['name' => 'Seni Budaya', 'code' => 'SBD-B'],
            ['name' => 'PJOK', 'code' => 'PJOK-B'],
            ['name' => 'Prakarya', 'code' => 'PRK-B'],
        ];

        // Mata pelajaran untuk Paket C (SMA)
        $paketCSubjects = [
            ['name' => 'Pendidikan Agama Islam', 'code' => 'PAI-C'],
            ['name' => 'Pendidikan Pancasila', 'code' => 'PPKN-C'],
            ['name' => 'Bahasa Indonesia', 'code' => 'BIND-C'],
            ['name' => 'Matematika', 'code' => 'MTK-C'],
            ['name' => 'Bahasa Inggris', 'code' => 'BING-C'],
            ['name' => 'Fisika', 'code' => 'FIS-C'],
            ['name' => 'Kimia', 'code' => 'KIM-C'],
            ['name' => 'Biologi', 'code' => 'BIO-C'],
            ['name' => 'Sejarah', 'code' => 'SEJ-C'],
            ['name' => 'Ekonomi', 'code' => 'EKO-C'],
            ['name' => 'Sosiologi', 'code' => 'SOS-C'],
            ['name' => 'Geografi', 'code' => 'GEO-C'],
        ];

        // Create subjects for PAUD & TK levels
        foreach (['PAUD', 'TK A', 'TK B'] as $levelName) {
            if (isset($levels[$levelName])) {
                foreach ($paudSubjects as $subject) {
                    Subject::firstOrCreate(
                        ['code' => $subject['code'] . '-' . $levels[$levelName]->id],
                        [
                            'name' => $subject['name'],
                            'level_id' => $levels[$levelName]->id,
                        ]
                    );
                }
            }
        }

        // Create subjects for Paket A levels
        $paketALevels = ['Paket A (Kelas 1)', 'Paket A (Kelas 2)', 'Paket A (Kelas 3)', 'Paket A (Kelas 4)', 'Paket A (Kelas 5)', 'Paket A (Kelas 6)'];
        foreach ($paketALevels as $levelName) {
            if (isset($levels[$levelName])) {
                foreach ($paketASubjects as $subject) {
                    Subject::firstOrCreate(
                        ['code' => $subject['code'] . '-' . $levels[$levelName]->id],
                        [
                            'name' => $subject['name'],
                            'level_id' => $levels[$levelName]->id,
                        ]
                    );
                }
            }
        }

        // Create subjects for Paket B levels
        $paketBLevels = ['Paket B (Kelas 7)', 'Paket B (Kelas 8)', 'Paket B (Kelas 9)'];
        foreach ($paketBLevels as $levelName) {
            if (isset($levels[$levelName])) {
                foreach ($paketBSubjects as $subject) {
                    Subject::firstOrCreate(
                        ['code' => $subject['code'] . '-' . $levels[$levelName]->id],
                        [
                            'name' => $subject['name'],
                            'level_id' => $levels[$levelName]->id,
                        ]
                    );
                }
            }
        }

        // Create subjects for Paket C levels
        $paketCLevels = ['Paket C (Kelas 10)', 'Paket C (Kelas 11)', 'Paket C (Kelas 12)'];
        foreach ($paketCLevels as $levelName) {
            if (isset($levels[$levelName])) {
                foreach ($paketCSubjects as $subject) {
                    Subject::firstOrCreate(
                        ['code' => $subject['code'] . '-' . $levels[$levelName]->id],
                        [
                            'name' => $subject['name'],
                            'level_id' => $levels[$levelName]->id,
                        ]
                    );
                }
            }
        }
    }
}
