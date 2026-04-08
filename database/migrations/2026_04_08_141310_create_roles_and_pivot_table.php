<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('role_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            $table->unique(['user_id', 'role_id']);
        });

        // Seed initial roles
        $roles = [
            ['name' => 'Administrator', 'slug' => 'admin', 'description' => 'Administrator sistem dengan akses penuh'],
            ['name' => 'Bendahara', 'slug' => 'bendahara', 'description' => 'Bendahara sekolah yang mengelola keuangan'],
            ['name' => 'Guru', 'slug' => 'guru', 'description' => 'Guru dengan akses ke fitur akademik'],
            ['name' => 'Kepala Sekolah', 'slug' => 'kepsek', 'description' => 'Kepala sekolah dengan akses pengawasan'],
            ['name' => 'Siswa', 'slug' => 'siswa', 'description' => 'Akses untuk siswa'],
            ['name' => 'Yayasan', 'slug' => 'yayasan', 'description' => 'Perwakilan yayasan pembina'],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->insert(array_merge($role, ['created_at' => now(), 'updated_at' => now()]));
        }

        // Migrate existing roles from users table
        $usersWithRoles = DB::table('users')->whereNotNull('role')->get();
        foreach ($usersWithRoles as $user) {
            $role = DB::table('roles')->where('slug', $user->role)->first();
            if ($role) {
                DB::table('role_user')->insert([
                    'user_id' => $user->id,
                    'role_id' => $role->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('roles');
    }
};
