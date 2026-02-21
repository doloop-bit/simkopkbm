<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Classroom;
use App\Models\AcademicYear;
use App\Models\Level;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('Configuring core application data...');

        // 1. Core Authentication & Users
        $this->call([
            AdminSeeder::class,
        ]);

        // 2. Academic Infrastructure
        \App\Models\AcademicYear::firstOrCreate(
            ['is_active' => true],
            [
                'name' => '2024/2025',
                'start_date' => '2024-07-01',
                'end_date' => '2025-06-30',
                'status' => 'open',
            ]
        );

        if (\App\Models\ScoreCategory::count() === 0) {
            $categories = [
                ['name' => 'Tugas', 'weight' => 20],
                ['name' => 'Kuis', 'weight' => 10],
                ['name' => 'UTS', 'weight' => 30],
                ['name' => 'UAS', 'weight' => 40],
            ];
            foreach ($categories as $cat) {
                \App\Models\ScoreCategory::create($cat);
            }
        }

        // 3. Run Core Seeders
        $this->command->info('Running Level and Subject seeders...');
        $this->call([
            LevelSeeder::class,
            SubjectSeeder::class,
            LearningAchievementSeeder::class, // Extends levels with phase_map and creates CPs
        ]);

        // 4. Kurikulum Merdeka Seeders
        $this->command->info('Running Kurikulum Merdeka seeders...');
        $this->call([
            DevelopmentalAspectsSeeder::class,     // For PAUD
            ExtracurricularActivitiesSeeder::class, // For Extracurriculars
            P5ProjectsSeeder::class,               // For P5
        ]);

        // 5. Financial Infrastructure
        $this->command->info('Running Financial seeders...');
        $this->call([
            FinancialSeeder::class,
        ]);

        $this->command->info('Database seeding completed successfully!');
    }
}
