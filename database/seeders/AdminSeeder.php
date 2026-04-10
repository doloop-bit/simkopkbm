<?php

namespace Database\Seeders;

use App\Models\Role;
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

        $admin = User::updateOrCreate(
            ['email' => 'admin@pkbm.com'],
            [
                'name' => 'Administrator PKBM',
                'role' => 'admin', // Keep legacy column for compatibility
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        $adminRole = Role::where('slug', 'admin')->first();
        if ($adminRole) {
            $admin->roles()->sync([$adminRole->id]);
        }

        $this->command->info('Admin account created successfully!');
    }
}
