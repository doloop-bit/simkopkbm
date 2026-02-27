<?php

namespace Database\Seeders;

use App\Models\Level;
use Illuminate\Database\Seeder;

class LevelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // KB / PAUD
        // Level::updateOrCreate(
        //     ['name' => 'Kelompok Bermain (KB)'],
        //     [
        //         'type' => 'class_teacher', // Guru Kelas
        //         'education_level' => 'paud',
        //         'phase_map' => null, // Fase Fondasi usually, or distinct
        //     ]
        // );

        Level::updateOrCreate(
            ['name' => 'PAUD'],
            [
                'type' => 'class_teacher',
                'education_level' => 'paud',
                'phase_map' => null,
            ]
        );

        // SD / Paket A
        Level::updateOrCreate(
            ['name' => 'Paket A (Setara SD)'],
            [
                'type' => 'class_teacher',
                'education_level' => 'sd',
                'phase_map' => [
                    '1' => 'A',
                    '2' => 'A',
                    '3' => 'B',
                    '4' => 'B',
                    '5' => 'C',
                    '6' => 'C',
                ],
            ]
        );

        // SMP / Paket B
        Level::updateOrCreate(
            ['name' => 'Paket B (Setara SMP)'],
            [
                'type' => 'subject_teacher', // Guru Mapel
                'education_level' => 'smp',
                'phase_map' => [
                    '7' => 'D',
                    '8' => 'D',
                    '9' => 'D',
                ],
            ]
        );

        // SMA / Paket C
        Level::updateOrCreate(
            ['name' => 'Paket C (Setara SMA)'],
            [
                'type' => 'subject_teacher',
                'education_level' => 'sma',
                'phase_map' => [
                    '10' => 'E',
                    '11' => 'F',
                    '12' => 'F',
                ],
            ]
        );
    }
}
