<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\CalendarEvent;
use App\Models\Level;
use App\Models\User;
use Illuminate\Database\Seeder;

class CalendarEventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $academicYear = AcademicYear::where('is_active', true)->first();

        if (! $academicYear) {
            return;
        }

        $admin = User::where('role', 'admin')->first();
        $levels = Level::all();

        foreach ($levels as $level) {
            CalendarEvent::factory()->rapatJenjang()->create([
                'title' => "Rapat Guru {$level->name}",
                'level_id' => $level->id,
                'academic_year_id' => $academicYear->id,
                'start_date' => now()->addDays(rand(1, 30)),
                'location' => 'Ruang Guru',
                'created_by' => $admin?->id,
            ]);

            CalendarEvent::factory()->ujianSekolah()->create([
                'title' => "UAS {$level->name} Semester 1",
                'level_id' => $level->id,
                'academic_year_id' => $academicYear->id,
                'start_date' => now()->addDays(rand(45, 60)),
                'end_date' => now()->addDays(rand(62, 70)),
                'is_all_day' => true,
                'start_time' => null,
                'end_time' => null,
                'location' => 'Ruang Kelas',
                'created_by' => $admin?->id,
            ]);
        }

        CalendarEvent::factory()->rapatGabungan()->create([
            'title' => 'Rapat Pleno PKBM',
            'academic_year_id' => $academicYear->id,
            'start_date' => now()->addDays(rand(5, 15)),
            'start_time' => '09:00',
            'end_time' => '12:00',
            'location' => 'Aula PKBM',
            'created_by' => $admin?->id,
        ]);

        CalendarEvent::factory()->rapatGabungan()->create([
            'title' => 'Sosialisasi Kurikulum Merdeka',
            'academic_year_id' => $academicYear->id,
            'start_date' => now()->addDays(rand(20, 35)),
            'start_time' => '08:00',
            'end_time' => '15:00',
            'location' => 'Aula PKBM',
            'created_by' => $admin?->id,
        ]);

        CalendarEvent::factory()->rapatYayasan()->create([
            'title' => 'Rapat Pengurus Yayasan',
            'academic_year_id' => $academicYear->id,
            'start_date' => now()->addDays(rand(10, 25)),
            'start_time' => '10:00',
            'end_time' => '12:00',
            'location' => 'Kantor Yayasan',
            'created_by' => $admin?->id,
        ]);

        CalendarEvent::factory()->ujianDinas()->create([
            'title' => 'Ujian CBT Dinas Pendidikan',
            'type' => 'ujian_dinas',
            'scope' => 'pkbm',
            'level_id' => null,
            'academic_year_id' => $academicYear->id,
            'start_date' => now()->addDays(rand(50, 65)),
            'end_date' => now()->addDays(rand(67, 72)),
            'is_all_day' => true,
            'start_time' => null,
            'end_time' => null,
            'location' => 'Lab Komputer',
            'created_by' => $admin?->id,
        ]);

        CalendarEvent::factory()->create([
            'title' => 'MPLS (Masa Pengenalan Lingkungan Sekolah)',
            'type' => 'kegiatan',
            'scope' => 'pkbm',
            'level_id' => null,
            'academic_year_id' => $academicYear->id,
            'start_date' => now()->addDays(rand(1, 5)),
            'end_date' => now()->addDays(rand(6, 8)),
            'is_all_day' => true,
            'start_time' => null,
            'end_time' => null,
            'location' => 'Aula PKBM',
            'created_by' => $admin?->id,
        ]);

        CalendarEvent::factory()->create([
            'title' => 'Peringatan Hari Pendidikan Nasional',
            'type' => 'lainnya',
            'scope' => 'pkbm',
            'level_id' => null,
            'academic_year_id' => $academicYear->id,
            'start_date' => now()->addDays(rand(30, 40)),
            'is_all_day' => true,
            'start_time' => null,
            'end_time' => null,
            'location' => 'Lapangan PKBM',
            'created_by' => $admin?->id,
        ]);
    }
}
