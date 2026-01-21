<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Level;
use App\Models\ScoreCategory;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Seeder;

class DummySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting Dummy Seeder for Report Card Testing...');

        // 1. Create Academic Year if not exists
        $academicYear = AcademicYear::where('is_active', true)->first();
        if (!$academicYear) {
            $academicYear = AcademicYear::factory()->create([
                'name' => '2024/2025',
                'is_active' => true,
                'status' => 'open',
            ]);
            $this->command->info('Active Academic Year created.');
        }

        // 2. Create Score Categories if not exists
        if (ScoreCategory::count() === 0) {
            $categories = [
                ['name' => 'Tugas', 'weight' => 20],
                ['name' => 'Kuis', 'weight' => 10],
                ['name' => 'UTS', 'weight' => 30],
                ['name' => 'UAS', 'weight' => 40],
            ];
            foreach ($categories as $cat) {
                ScoreCategory::create($cat);
            }
            $this->command->info('Score Categories created.');
        }

        // 3. Create Levels (Standard setup for PKBM)
        $levelNames = [
            'Paket A (Kelas 6)' => 'subject_teacher',
            'Paket B (Kelas 9)' => 'subject_teacher',
            'Paket C (Kelas 12)' => 'subject_teacher',
        ];

        foreach ($levelNames as $name => $type) {
            Level::firstOrCreate(['name' => $name], ['type' => $type]);
        }
        $this->command->info('Levels created/verified.');

        // 4. Run Subject Seeder to populate subjects for these levels
        $this->call(SubjectSeeder::class);
        $this->command->info('Subjects populated via SubjectSeeder.');

        // 5. Create Classrooms and Students
        $levels = Level::whereIn('name', array_keys($levelNames))->get();
        
        foreach ($levels as $level) {
            // Create 1 classroom per level
            $classroom = Classroom::factory()->create([
                'name' => 'Kelas ' . str_replace(['Paket ', ' (Kelas ', ')'], '', $level->name) . '-1',
                'academic_year_id' => $academicYear->id,
                'level_id' => $level->id,
            ]);

            $this->command->info("Creating students for classroom: {$classroom->name}");

            // Create 5 students for this classroom
            User::factory(5)->create(['role' => 'siswa'])->each(function ($user) use ($classroom) {
                $profile = StudentProfile::factory()->create([
                    'classroom_id' => $classroom->id,
                ]);

                $user->profiles()->create([
                    'profileable_id' => $profile->id,
                    'profileable_type' => StudentProfile::class,
                ]);
            });
        }

        // 6. Finally, run ScoreAndReportCardSeeder to generate scores and report cards
        $this->command->info('Generating scores and report cards...');
        $this->call(ScoreAndReportCardSeeder::class);

        $this->command->info('Dummy data for testing report cards created successfully!');
    }
}
