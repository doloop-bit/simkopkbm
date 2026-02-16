<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Level;
use Illuminate\Support\Facades\DB;

class LevelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // KB / PAUD
        Level::updateOrCreate(
            ['name' => 'Kelompok Bermain (KB)'],
            [
                'type' => 'class_teacher', // Guru Kelas
                'education_level' => 'PAUD',
                'phase_map' => null, // Fase Fondasi usually, or distinct
            ]
        );

        Level::updateOrCreate(
            ['name' => 'Taman Kanak-Kanak (TK)'],
            [
                'type' => 'class_teacher',
                'education_level' => 'PAUD',
                'phase_map' => null,
            ]
        );

        // SD / Paket A
        Level::updateOrCreate(
            ['name' => 'Paket A (Setara SD)'],
            [
                'type' => 'class_teacher',
                'education_level' => 'SD',
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
                'education_level' => 'SMP',
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
                'education_level' => 'SMA',
                'phase_map' => [
                    '10' => 'E',
                    '11' => 'F',
                    '12' => 'F',
                ],
            ]
        );
    }
}
