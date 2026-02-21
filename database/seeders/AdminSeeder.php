<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating Administrator account...');

        User::firstOrCreate(
            ['email' => 'admin@pkbm.com'],
            [
                'name' => 'Administrator PKBM',
                'role' => 'admin',
                'password' => bcrypt('password'),
            ]
        );

        $this->command->info('Admin account created successfully!');
    }
}
