<?php

namespace Database\Seeders;

use App\Models\ExtracurricularActivity;
use App\Models\Level;
use Illuminate\Database\Seeder;

class ExtracurricularActivitiesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $activities = [
            [
                'name' => 'Pramuka',
                'description' => 'Kegiatan kepramukaan untuk membentuk karakter dan keterampilan',
                'instructor' => 'Pak Budi Santoso',
                'is_active' => true,
            ],
            [
                'name' => 'Tahfidz Al-Quran',
                'description' => 'Program menghafal Al-Quran',
                'instructor' => 'Ustadz Ahmad',
                'is_active' => true,
            ],
            [
                'name' => 'Futsal',
                'description' => 'Olahraga futsal untuk melatih kerjasama tim',
                'instructor' => 'Coach Andi',
                'is_active' => true,
            ],
            [
                'name' => 'Seni Tari',
                'description' => 'Pembelajaran tari tradisional dan modern',
                'instructor' => 'Ibu Siti',
                'is_active' => true,
            ],
            [
                'name' => 'Robotika',
                'description' => 'Pembelajaran dasar robotika dan programming',
                'instructor' => 'Pak Dedi',
                'is_active' => true,
            ],
            [
                'name' => 'English Club',
                'description' => 'Klub bahasa Inggris untuk meningkatkan kemampuan berbahasa',
                'instructor' => 'Miss Sarah',
                'is_active' => true,
            ],
            [
                'name' => 'Seni Lukis',
                'description' => 'Kegiatan melukis dan menggambar',
                'instructor' => 'Ibu Rina',
                'is_active' => true,
            ],
            [
                'name' => 'Musik (Angklung)',
                'description' => 'Pembelajaran alat musik tradisional angklung',
                'instructor' => 'Pak Joko',
                'is_active' => true,
            ],
            [
                'name' => 'Karate',
                'description' => 'Seni bela diri karate',
                'instructor' => 'Sensei Hendra',
                'is_active' => true,
            ],
            [
                'name' => 'Jurnalistik',
                'description' => 'Kegiatan menulis dan jurnalistik siswa',
                'instructor' => 'Ibu Dewi',
                'is_active' => true,
            ],
        ];

        $levelId = Level::where('education_level', '!=', 'PAUD')->first()?->id;

        foreach ($activities as $activity) {
            $activity['level_id'] = $levelId;
            ExtracurricularActivity::firstOrCreate(
                ['name' => $activity['name']],
                $activity
            );
        }
    }
}
