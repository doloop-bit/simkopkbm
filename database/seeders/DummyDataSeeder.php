<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AcademicYear;
use App\Models\Level;
use App\Models\Classroom;
use App\Models\User;
use App\Models\StudentProfile;
use App\Models\Profile;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;

class DummyDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('id_ID');
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

        $levels = Level::all();
        $password = Hash::make('password');

        foreach ($levels as $level) {
            // Create a class for this level
            $classroom = Classroom::firstOrCreate([
                'name' => 'Kelas A - ' . $level->name,
                'level_id' => $level->id,
                'academic_year_id' => $academicYear->id,
            ]);

            $this->command->info('Membuat data untuk: ' . $classroom->name);

            // Create 10 students for this class
            for ($i = 1; $i <= 10; $i++) {
                $student = User::firstOrCreate(
                    ['email' => 'siswa' . $i . '_level' . $level->id . '@example.com'],
                    [
                        'name' => $faker->name,
                        'password' => $password,
                        'role' => 'siswa',
                        'is_active' => true,
                    ]
                );

                if (! $student->profiles()->exists()) {
                    // Create profile
                    $profile = StudentProfile::create([
                        'nis' => $faker->unique()->numerify('######'),
                        'nisn' => $faker->unique()->numerify('##########'),
                        'phone' => $faker->phoneNumber(),
                        'address' => $faker->address(),
                        'dob' => $faker->date(),
                        'pob' => $faker->city(),
                        'father_name' => collect([$faker->name('male'), null])->random(),
                        'mother_name' => collect([$faker->name('female'), null])->random(),
                        'status' => 'baru',
                        'classroom_id' => $classroom->id,
                    ]);

                    // Link User and Profile
                    Profile::create([
                        'user_id' => $student->id,
                        'profileable_type' => StudentProfile::class,
                        'profileable_id' => $profile->id,
                    ]);
                }
            }
        }
        
        $this->command->info('Berhasil membuat data kelas tiap tingkat dan siswa 10 tiap kelas.');
    }
}
