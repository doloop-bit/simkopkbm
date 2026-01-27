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
        // User::factory(10)->create();

        User::firstOrCreate(
            ['email' => 'admin@pkbm.com'],
            [
                'name' => 'Administrator PKBM',
                'role' => 'admin',
                'password' => bcrypt('password'), // Default password for seeded users
            ]
        );

        $categories = [
            ['name' => 'Tugas', 'weight' => 20],
            ['name' => 'Kuis', 'weight' => 10],
            ['name' => 'UTS', 'weight' => 30],
            ['name' => 'UAS', 'weight' => 40],
        ];

        foreach ($categories as $cat) {
            \App\Models\ScoreCategory::firstOrCreate(['name' => $cat['name']], $cat);
        }

        $students = [
            ['name' => 'Student One', 'email' => 'student1@pkbm.com'],
            ['name' => 'Student Two', 'email' => 'student2@pkbm.com'],
        ];

        foreach ($students as $s) {
            $user = User::firstOrCreate(
                ['email' => $s['email']],
                [
                    'name' => $s['name'],
                    'role' => 'siswa',
                    'password' => bcrypt('password'), // Default password for seeded users
                ]
            );

            // Check if profile already exists for this user to avoid duplicates
            if (!$user->profiles()->exists()) {
                // First, ensure there's at least one classroom
                $classroom = \App\Models\Classroom::first();
                if (!$classroom) {
                    // Create required related records first
                    $academicYear = \App\Models\AcademicYear::first();
                    if (!$academicYear) {
                        $academicYear = \App\Models\AcademicYear::create([
                            'name' => '2024/2025',
                            'start_date' => '2024-08-01',
                            'end_date' => '2025-06-30',
                        ]);
                    }

                    $level = \App\Models\Level::first();
                    if (!$level) {
                        $level = \App\Models\Level::create([
                            'name' => 'Basic Level',
                            'type' => 'regular', // Assuming 'regular' as default type
                        ]);
                    }

                    $teacher = \App\Models\User::where('role', 'guru')->first();
                    if (!$teacher) {
                        $teacher = User::firstOrCreate(
                            ['email' => 'teacher@pkbm.com'],
                            [
                                'name' => 'Default Teacher',
                                'role' => 'guru',
                                'password' => bcrypt('password'),
                            ]
                        );
                    }

                    $classroom = \App\Models\Classroom::create([
                        'name' => 'Default Classroom',
                        'academic_year_id' => $academicYear->id,
                        'level_id' => $level->id,
                        'homeroom_teacher_id' => $teacher->id,
                    ]);
                }

                $profile = \App\Models\StudentProfile::create([
                    'classroom_id' => $classroom->id,
                ]);

                $user->profiles()->create([
                    'profileable_id' => $profile->id,
                    'profileable_type' => \App\Models\StudentProfile::class,
                ]);
            }
        }
    }
}
