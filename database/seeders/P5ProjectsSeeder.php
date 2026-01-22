<?php

namespace Database\Seeders;

use App\Models\P5Project;
use App\Models\AcademicYear;
use Illuminate\Database\Seeder;

class P5ProjectsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get current academic year
        $academicYear = AcademicYear::latest()->first();

        if (!$academicYear) {
            $this->command->warn('No academic year found. Please create an academic year first.');
            return;
        }

        $projects = [
            // Semester 1
            [
                'name' => 'Budidaya Tanaman Hidroponik',
                'description' => 'Siswa belajar menanam sayuran dengan sistem hidroponik untuk memahami pentingnya kemandirian dan keberlanjutan lingkungan',
                'academic_year_id' => $academicYear->id,
                'semester' => '1',
                'dimension' => 'mandiri',
                'start_date' => now()->startOfYear()->addMonths(6),
                'end_date' => now()->startOfYear()->addMonths(8),
            ],
            [
                'name' => 'Kearifan Lokal Nusantara',
                'description' => 'Eksplorasi budaya dan kearifan lokal dari berbagai daerah di Indonesia',
                'academic_year_id' => $academicYear->id,
                'semester' => '1',
                'dimension' => 'berkebinekaan',
                'start_date' => now()->startOfYear()->addMonths(7),
                'end_date' => now()->startOfYear()->addMonths(9),
            ],
            [
                'name' => 'Gerakan Peduli Lingkungan',
                'description' => 'Aksi nyata siswa dalam menjaga kebersihan dan kelestarian lingkungan sekolah',
                'academic_year_id' => $academicYear->id,
                'semester' => '1',
                'dimension' => 'gotong_royong',
                'start_date' => now()->startOfYear()->addMonths(8),
                'end_date' => now()->startOfYear()->addMonths(10),
            ],

            // Semester 2
            [
                'name' => 'Wirausaha Muda',
                'description' => 'Siswa belajar membuat produk sederhana dan memasarkannya untuk mengembangkan jiwa kewirausahaan',
                'academic_year_id' => $academicYear->id,
                'semester' => '2',
                'dimension' => 'kreatif',
                'start_date' => now()->startOfYear()->addMonths(12),
                'end_date' => now()->startOfYear()->addMonths(14),
            ],
            [
                'name' => 'Literasi Digital dan Hoaks',
                'description' => 'Pembelajaran tentang cara berpikir kritis dalam menghadapi informasi di era digital',
                'academic_year_id' => $academicYear->id,
                'semester' => '2',
                'dimension' => 'bernalar_kritis',
                'start_date' => now()->startOfYear()->addMonths(13),
                'end_date' => now()->startOfYear()->addMonths(15),
            ],
            [
                'name' => 'Berbagi Kasih untuk Sesama',
                'description' => 'Kegiatan sosial dan berbagi dengan masyarakat kurang mampu',
                'academic_year_id' => $academicYear->id,
                'semester' => '2',
                'dimension' => 'beriman',
                'start_date' => now()->startOfYear()->addMonths(14),
                'end_date' => now()->startOfYear()->addMonths(16),
            ],
        ];

        foreach ($projects as $project) {
            P5Project::create($project);
        }

        $this->command->info('P5 Projects seeded successfully!');
    }
}
