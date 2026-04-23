<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            ['name' => 'Administrator', 'slug' => 'admin', 'description' => 'Administrator sistem dengan akses penuh'],
            ['name' => 'Bendahara', 'slug' => 'bendahara', 'description' => 'Bendahara sekolah yang mengelola keuangan'],
            ['name' => 'Guru', 'slug' => 'guru', 'description' => 'Guru dengan akses ke fitur akademik'],
            ['name' => 'Kepala Sekolah', 'slug' => 'kepsek', 'description' => 'Kepala sekolah dengan akses pengawasan'],
            ['name' => 'Siswa', 'slug' => 'siswa', 'description' => 'Akses untuk siswa'],
            ['name' => 'Yayasan', 'slug' => 'yayasan', 'description' => 'Perwakilan yayasan pembina'],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['slug' => $role['slug']], $role);
        }
    }
}
