<?php

namespace Database\Seeders;

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

        // 2. Run Core Seeders
        $this->command->info('Running Level and Subject seeders...');
        $this->call([
            LevelSeeder::class,
            SubjectSeeder::class,
            LearningAchievementSeeder::class, // Extends levels with phase_map and creates CPs
        ]);

        // 3. Kurikulum Merdeka Seeders
        $this->command->info('Running Kurikulum Merdeka seeders...');
        $this->call([
            DevelopmentalAspectsSeeder::class,     // For PAUD
            ExtracurricularActivitiesSeeder::class, // For Extracurriculars
        ]);

        // 4. Financial Infrastructure
        $this->command->info('Running Financial seeders...');
        $this->call([
            FinancialSeeder::class,
        ]);

        $this->command->info('Database seeding completed successfully!');
    }
}
