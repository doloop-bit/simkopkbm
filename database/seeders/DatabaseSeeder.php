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
        // === CORE APPLICATION DATA (ESSENTIAL) ===
        // Data yang tidak memiliki CRUD atau wajib ada untuk infrastruktur aplikasi.
        $this->command->info('Configuring core application data...');
        $this->call([
            AdminSeeder::class,                 // Akun Admin Utama
            LevelSeeder::class,                 // Jenjang & Phase Map (Kurikulum Merdeka)
            DevelopmentalAspectsSeeder::class,  // Indikator Penilaian PAUD
        ]);

        $this->command->info('Database seeding completed successfully!');
    }
}
