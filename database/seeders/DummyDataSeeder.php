<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Facility;
use App\Models\GalleryPhoto;
use App\Models\Level;
use App\Models\NewsArticle;
use App\Models\Program;
use App\Models\SchoolProfile;
use App\Models\StaffMember;
use App\Models\User;
use Illuminate\Database\Seeder;

class DummyDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Configuring School Profile...');
        $schoolProfile = SchoolProfile::firstOrCreate(
            ['is_active' => true],
            [
                'name' => 'PKBM Baitusyukur',
                'address' => 'Jl. Pendidikan No. 123, Jakarta Selatan',
                'phone' => '021-5551234',
                'email' => 'info@baitusyukur.sch.id',
                'vision' => 'Mencetak generasi cerdas, mandiri, dan berakhlak mulia.',
                'mission' => "1. Menyelenggarakan pendidikan inklusif.\n2. Mengembangkan bakat dan minat siswa.\n3. Membangun karakter positif.",
                'history' => [
                    ['year' => '2010', 'title' => 'Pendirian', 'description' => 'PKBM didirikan dengan semangat kepedulian terhadap pendidikan masyarakat.', 'image_path' => null],
                    ['year' => '2015', 'title' => 'Akreditasi A', 'description' => 'Berhasil memperoleh nilai akreditasi terbaik untuk seluruh program.', 'image_path' => null],
                    ['year' => '2020', 'title' => 'Era Digital', 'description' => 'Meluncurkan sistem pembelajaran online terpadu.', 'image_path' => null],
                    ['year' => '2025', 'title' => 'Modernisasi KBM', 'description' => 'Renovasi fasilitas dan implementasi kurikulum merdeka.', 'image_path' => null],
                ],
                'operating_hours' => 'Senin - Jumat: 08:00 - 15:00',
            ]
        );

        if ($schoolProfile->staffMembers()->count() === 0) {
            $this->command->info('Seeding Staff Members...');
            StaffMember::factory()->count(6)->for($schoolProfile)->create();
        }

        if ($schoolProfile->facilities()->count() === 0) {
            $this->command->info('Seeding Facilities...');
            Facility::factory()->count(4)->for($schoolProfile)->create();
        }

        $this->command->info('Creating Dummy Data (Teachers, Students, News, Programs)...');

        $academicYear = AcademicYear::where('is_active', true)->first();

        if (! $academicYear) {
            $academicYear = AcademicYear::create([
                'name' => '2025/2026',
                'start_date' => '2025-07-01',
                'end_date' => '2026-06-30',
                'is_active' => true,
                'status' => 'open',
            ]);
        }

        // 1. Ensure levels exist with phase maps
        if (Level::count() === 0) {
            $this->command->info('Creating 4 essential levels with phase maps...');
            $phases = Level::defaultPhases();
            Level::create(['name' => 'PAUD', 'education_level' => 'paud', 'type' => 'class_teacher', 'phase_map' => $phases['paud']]);
            Level::create(['name' => 'Paket A', 'education_level' => 'sd', 'type' => 'class_teacher', 'phase_map' => $phases['sd']]);
            Level::create(['name' => 'Paket B', 'education_level' => 'smp', 'type' => 'subject_teacher', 'phase_map' => $phases['smp']]);
            Level::create(['name' => 'Paket C', 'education_level' => 'sma', 'type' => 'subject_teacher', 'phase_map' => $phases['sma']]);
        }

        $levels = Level::all();
        if ($levels->isEmpty()) {
            $this->command->warn('No Levels exist! Did you run DatabaseSeeder first?');

            return;
        }

        // 2. Create dummy Guru (Teachers)
        $this->command->info('Creating 5 dummy teachers...');
        $teachers = User::factory()->count(5)->guru()->create();

        // 3. Create Dummy Programs
        if (Program::count() === 0) {
            $this->command->info('Creating realistic public programs for each level...');
            foreach ($levels as $level) {
                Program::factory()
                    ->forLevel($level)
                    ->levelSpecific()
                    ->withBranding()
                    ->create(['is_active' => true]);
            }
        }

        // 4. Create Dummy News
        if (NewsArticle::count() === 0) {
            $this->command->info('Creating 10 news articles...');
            NewsArticle::factory()->count(10)->create();
        }

        // 5. Create Dummy Gallery
        if (GalleryPhoto::count() === 0) {
            $this->command->info('Creating 12 gallery photos...');
            GalleryPhoto::factory()->count(12)->create();
        }

        // 6. Create Classrooms & Students
        $this->command->info('Creating classrooms for each class level...');
        foreach ($levels as $level) {
            if (! $level->phase_map) {
                continue;
            }

            foreach ($level->phase_map as $classLevel => $phase) {
                $classroom = Classroom::firstOrCreate(
                    [
                        'level_id' => $level->id,
                        'academic_year_id' => $academicYear->id,
                        'class_level' => (int) $classLevel,
                    ],
                    [
                        'name' => "Kelas {$classLevel} - {$level->name}",
                    ]
                );

                // Create Homeroom Teacher assignment if not exists
                if ($classroom->teacherAssignments()->where('type', 'class_teacher')->count() === 0) {
                    \App\Models\TeacherAssignment::create([
                        'teacher_id' => $teachers->random()->id,
                        'classroom_id' => $classroom->id,
                        'academic_year_id' => $academicYear->id,
                        'type' => 'class_teacher',
                    ]);
                }

                // Create some students if count < 5
                $currentStudentCount = $classroom->students()->count();
                if ($currentStudentCount < 5) {
                    $this->command->info("Membuat siswa untuk: {$classroom->name}");
                    $students = User::factory()->count(5 - $currentStudentCount)->siswa()->create();

                    foreach ($students as $student) {
                        $student->studentProfile->update([
                            'classroom_id' => $classroom->id,
                        ]);
                    }
                }
            }
        }

        $this->command->info('Berhasil membuat dummy data untuk local development.');
    }
}
