<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\CompetencyAssessment;
use App\Models\Level;
use App\Models\P5Assessment;
use App\Models\P5Project;
use App\Models\Profile;
use App\Models\ReportAttendance;
use App\Models\ReportCard;
use App\Models\Score;
use App\Models\ScoreCategory;
use App\Models\StudentProfile;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestReportCardSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating high-quality dummy data for Report Card testing...');

        // 1. Academic Year
        $academicYear = AcademicYear::firstOrCreate(
            ['name' => '2024/2025'],
            [
                'start_date' => '2024-07-01',
                'end_date' => '2025-06-30',
                'is_active' => true,
                'status' => 'open'
            ]
        );

        // 2. Score Categories
        $categories = [
            ['name' => 'Tugas', 'weight' => 20],
            ['name' => 'UTS', 'weight' => 30],
            ['name' => 'UAS', 'weight' => 50],
        ];
        foreach ($categories as $cat) {
            ScoreCategory::firstOrCreate(['name' => $cat['name']], ['weight' => $cat['weight']]);
        }
        $scoreCategories = ScoreCategory::all();

        // 3. Level & Subjects
        $level = Level::firstOrCreate(
            ['name' => 'Paket C (Kelas 12)'],
            ['type' => 'subject_teacher', 'education_level' => 'sma']
        );

        $subjectData = [
            'Matematika' => 'MTK',
            'Bahasa Indonesia' => 'BIND',
            'Bahasa Inggris' => 'BING',
            'Informatika' => 'INFO',
            'Ekonomi' => 'EKO'
        ];
        $subjects = [];
        foreach ($subjectData as $name => $code) {
            $subjects[] = Subject::firstOrCreate(
                ['name' => $name, 'level_id' => $level->id],
                ['code' => $code]
            );
        }

        // 4. Classroom
        $classroom = Classroom::firstOrCreate(
            ['name' => '12-IPA-1', 'academic_year_id' => $academicYear->id],
            ['level_id' => $level->id]
        );

        // 5. Test Student
        $studentUser = User::firstOrCreate(
            ['email' => 'student.test@example.com'],
            [
                'name' => 'Budi Santoso',
                'password' => Hash::make('password'),
                'role' => 'siswa',
                'email_verified_at' => now(),
            ]
        );

        $studentProfile = StudentProfile::firstOrCreate(
            ['classroom_id' => $classroom->id, 'nis' => 'SN-001'],
            [
                'nisn' => '1234567890',
                'phone' => '08123456789',
                'address' => 'Jl. Test No. 123',
                'status' => 'baru'
            ]
        );

        Profile::firstOrCreate(
            ['user_id' => $studentUser->id],
            [
                'profileable_id' => $studentProfile->id,
                'profileable_type' => StudentProfile::class
            ]
        );

        // 6. RAW DATA: Conventional (Scores)
        foreach ($subjects as $subject) {
            foreach ($scoreCategories as $category) {
                Score::updateOrCreate(
                    [
                        'student_id' => $studentUser->id,
                        'subject_id' => $subject->id,
                        'classroom_id' => $classroom->id,
                        'academic_year_id' => $academicYear->id,
                        'score_category_id' => $category->id,
                    ],
                    ['score' => rand(70, 95)]
                );
            }
        }

        // 7. RAW DATA: Merdeka (Competency)
        $competencyLevels = ['SB', 'BSH', 'MB', 'BB'];
        foreach ($subjects as $subject) {
            CompetencyAssessment::updateOrCreate(
                [
                    'student_id' => $studentUser->id,
                    'subject_id' => $subject->id,
                    'academic_year_id' => $academicYear->id,
                    'semester' => '1',
                ],
                [
                    'classroom_id' => $classroom->id,
                    'competency_level' => $competencyLevels[array_rand($competencyLevels)],
                    'achievement_description' => "Menunjukkan penguasaan yang sangat baik dalam materi {$subject->name} pada semester ini."
                ]
            );
        }

        // 8. RAW DATA: P5 Projects
        $p5Projects = [
            ['name' => 'Kearifan Lokal: Batik', 'dimension' => 'berkebinekaan'],
            ['name' => 'Gaya Hidup Berkelanjutan', 'dimension' => 'mandiri'],
        ];

        foreach ($p5Projects as $p5) {
            $project = P5Project::firstOrCreate(
                ['name' => $p5['name'], 'academic_year_id' => $academicYear->id],
                ['dimension' => $p5['dimension'], 'semester' => '1', 'description' => 'Project description for ' . $p5['name']]
            );

            P5Assessment::updateOrCreate(
                ['student_id' => $studentUser->id, 'p5_project_id' => $project->id],
                [
                    'academic_year_id' => $academicYear->id,
                    'classroom_id' => $classroom->id,
                    'semester' => '1',
                    'achievement_level' => 'SB',
                    'description' => 'Siswa sangat aktif dan menunjukkan kepemimpinan dalam project ini.'
                ]
            );
        }

        // 9. RAW DATA: Attendance
        ReportAttendance::updateOrCreate(
            [
                'student_id' => $studentUser->id,
                'classroom_id' => $classroom->id,
                'academic_year_id' => $academicYear->id,
                'semester' => '1',
            ],
            [
                'sick' => rand(0, 2),
                'permission' => rand(0, 1),
                'absent' => 0,
            ]
        );

        $this->command->info('Dummy data for Test Report Card created successfully!');
        $this->command->info('User: student.test@example.com');
    }
}
