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
        // Mata pelajaran berdasarkan Fase Kurikulum Merdeka
        
        $phases = [
            'Fondasi' => [ // PAUD
                ['name' => 'Nilai Agama & Budi Pekerti', 'code' => 'NABP-FONDASI'],
                ['name' => 'Fisik-Motorik', 'code' => 'FM-FONDASI'],
                ['name' => 'Kognitif', 'code' => 'KOG-FONDASI'],
                ['name' => 'Bahasa', 'code' => 'BHS-FONDASI'],
                ['name' => 'Sosial-Emosional', 'code' => 'SE-FONDASI'],
                ['name' => 'Seni & Kreativitas', 'code' => 'SNI-FONDASI'],
            ],
            'A' => [ // SD Kelas 1-2 (Belum ada IPAS & B. Inggris)
                ['name' => 'Pendidikan Agama Islam', 'code' => 'PAI-A'],
                ['name' => 'Pendidikan Pancasila', 'code' => 'PPKN-A'],
                ['name' => 'Bahasa Indonesia', 'code' => 'BIND-A'],
                ['name' => 'Matematika', 'code' => 'MTK-A'],
                ['name' => 'Seni Budaya', 'code' => 'SBD-A'],
                ['name' => 'PJOK', 'code' => 'PJOK-A'],
                ['name' => 'Bahasa Jawa', 'code' => 'BJAW-A'],
            ],
            'B' => [ // SD Kelas 3-4 (Mulai ada IPAS & B. Inggris)
                ['name' => 'Pendidikan Agama Islam', 'code' => 'PAI-B'],
                ['name' => 'Pendidikan Pancasila', 'code' => 'PPKN-B'],
                ['name' => 'Bahasa Indonesia', 'code' => 'BIND-B'],
                ['name' => 'Matematika', 'code' => 'MTK-B'],
                ['name' => 'IPAS', 'code' => 'IPAS-B'],
                ['name' => 'Seni Budaya', 'code' => 'SBD-B'],
                ['name' => 'PJOK', 'code' => 'PJOK-B'],
                ['name' => 'Bahasa Inggris', 'code' => 'BING-B'],
                ['name' => 'Bahasa Jawa', 'code' => 'BJAW-B'],
            ],
            'C' => [ // SD Kelas 5-6 
                ['name' => 'Pendidikan Agama Islam', 'code' => 'PAI-C'],
                ['name' => 'Pendidikan Pancasila', 'code' => 'PPKN-C'],
                ['name' => 'Bahasa Indonesia', 'code' => 'BIND-C'],
                ['name' => 'Matematika', 'code' => 'MTK-C'],
                ['name' => 'IPAS', 'code' => 'IPAS-C'],
                ['name' => 'Seni Budaya', 'code' => 'SBD-C'],
                ['name' => 'PJOK', 'code' => 'PJOK-C'],
                ['name' => 'Bahasa Inggris', 'code' => 'BING-C'],
                ['name' => 'Bahasa Jawa', 'code' => 'BJAW-C'],
            ],
            'D' => [ // SMP
                ['name' => 'Pendidikan Agama Islam', 'code' => 'PAI-D'],
                ['name' => 'Pendidikan Pancasila', 'code' => 'PPKN-D'],
                ['name' => 'Bahasa Indonesia', 'code' => 'BIND-D'],
                ['name' => 'Matematika', 'code' => 'MTK-D'],
                ['name' => 'IPA', 'code' => 'IPA-D'],
                ['name' => 'IPS', 'code' => 'IPS-D'],
                ['name' => 'Bahasa Inggris', 'code' => 'BING-D'],
                ['name' => 'Seni Budaya', 'code' => 'SBD-D'],
                ['name' => 'PJOK', 'code' => 'PJOK-D'],
                ['name' => 'Informatika', 'code' => 'INF-D'],
                ['name' => 'Prakarya', 'code' => 'PRK-D'],
                ['name' => 'Bahasa Jawa', 'code' => 'BJAW-D'],
            ],
            'E' => [ // SMA Kelas 10
                ['name' => 'Pendidikan Agama Islam', 'code' => 'PAI-E'],
                ['name' => 'Pendidikan Pancasila', 'code' => 'PPKN-E'],
                ['name' => 'Bahasa Indonesia', 'code' => 'BIND-E'],
                ['name' => 'Matematika', 'code' => 'MTK-E'],
                ['name' => 'Bahasa Inggris', 'code' => 'BING-E'],
                ['name' => 'IPA', 'code' => 'IPA-E'],
                ['name' => 'IPS', 'code' => 'IPS-E'],
                ['name' => 'PJOK', 'code' => 'PJOK-E'],
                ['name' => 'Seni Budaya', 'code' => 'SBD-E'],
                ['name' => 'Informatika', 'code' => 'INF-E'],
                ['name' => 'Bahasa Jawa', 'code' => 'BJAW-E'],
            ],
            'F' => [ // SMA Kelas 11-12 (Peminatan MIPA/IPS dipisah kalau perlu, disatukan dulu)
                ['name' => 'Pendidikan Agama Islam', 'code' => 'PAI-F'],
                ['name' => 'Pendidikan Pancasila', 'code' => 'PPKN-F'],
                ['name' => 'Bahasa Indonesia', 'code' => 'BIND-F'],
                ['name' => 'Matematika', 'code' => 'MTK-F'],
                ['name' => 'Bahasa Inggris', 'code' => 'BING-F'],
                ['name' => 'Fisika', 'code' => 'FIS-F'],
                ['name' => 'Kimia', 'code' => 'KIM-F'],
                ['name' => 'Biologi', 'code' => 'BIO-F'],
                ['name' => 'Sejarah', 'code' => 'SEJ-F'],
                ['name' => 'Ekonomi', 'code' => 'EKO-F'],
                ['name' => 'Sosiologi', 'code' => 'SOS-F'],
                ['name' => 'Geografi', 'code' => 'GEO-F'],
                ['name' => 'PJOK', 'code' => 'PJOK-F'],
                ['name' => 'Seni Budaya', 'code' => 'SBD-F'],
                ['name' => 'Informatika', 'code' => 'INF-F'],
                ['name' => 'Bahasa Jawa', 'code' => 'BJAW-F'],
            ],
        ];

        foreach ($phases as $phaseName => $subjects) {
            foreach ($subjects as $subject) {
                Subject::firstOrCreate(
                    ['code' => $subject['code']],
                    [
                        'name' => $subject['name'],
                        'phase' => $phaseName,
                    ]
                );
            }

            $this->command->info("✓ Created subjects for Phase: {$phaseName}");
        }
    }
}
