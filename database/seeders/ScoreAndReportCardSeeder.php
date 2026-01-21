<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\ReportCard;
use App\Models\Score;
use App\Models\ScoreCategory;
use App\Models\StudentProfile;
use App\Models\Subject;
use Illuminate\Database\Seeder;

class ScoreAndReportCardSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $academicYear = AcademicYear::where('is_active', true)->first();
        
        if (!$academicYear) {
            $this->command->error('No active academic year found!');
            return;
        }

        $scoreCategories = ScoreCategory::all();
        
        if ($scoreCategories->isEmpty()) {
            $this->command->error('No score categories found!');
            return;
        }

        // Get classrooms with their levels
        $classrooms = Classroom::with('level')
            ->where('academic_year_id', $academicYear->id)
            ->get();

        if ($classrooms->isEmpty()) {
            $this->command->error('No classrooms found for this academic year!');
            return;
        }

        $this->command->info('Creating scores and report cards...');
        $bar = $this->command->getOutput()->createProgressBar($classrooms->count());

        foreach ($classrooms as $classroom) {
            // Get subjects for this classroom's level
            $subjects = Subject::where('level_id', $classroom->level_id)->get();

            if ($subjects->isEmpty()) {
                $bar->advance();
                continue;
            }

            // Get students in this classroom
            $studentProfiles = StudentProfile::where('classroom_id', $classroom->id)
                ->with(['profile.user'])
                ->get();

            foreach ($studentProfiles as $studentProfile) {
                $user = $studentProfile->profile?->user;
                
                if (!$user) {
                    continue;
                }

                // Create scores for each subject and category
                $aggregatedScores = [];
                $totalScore = 0;
                $scoreCount = 0;

                foreach ($subjects as $subject) {
                    $subjectTotal = 0;
                    $categoryCount = 0;

                    foreach ($scoreCategories as $category) {
                        // Generate random score between 60-100
                        $randomScore = fake()->randomFloat(2, 60, 100);

                        Score::firstOrCreate(
                            [
                                'student_id' => $user->id,
                                'subject_id' => $subject->id,
                                'classroom_id' => $classroom->id,
                                'academic_year_id' => $academicYear->id,
                                'score_category_id' => $category->id,
                            ],
                            [
                                'score' => $randomScore,
                                'notes' => null,
                            ]
                        );

                        $subjectTotal += $randomScore;
                        $categoryCount++;
                    }

                    // Calculate average for this subject
                    $avgScore = $categoryCount > 0 ? round($subjectTotal / $categoryCount, 2) : 0;
                    $aggregatedScores[$subject->id] = [
                        'subject_name' => $subject->name,
                        'score' => $avgScore,
                    ];

                    $totalScore += $avgScore;
                    $scoreCount++;
                }

                // Calculate GPA
                $gpa = $scoreCount > 0 ? round($totalScore / $scoreCount, 2) : 0;

                // Create report card for semester 1
                ReportCard::firstOrCreate(
                    [
                        'student_id' => $user->id,
                        'classroom_id' => $classroom->id,
                        'academic_year_id' => $academicYear->id,
                        'semester' => '1',
                    ],
                    [
                        'scores' => $aggregatedScores,
                        'gpa' => $gpa,
                        'teacher_notes' => fake()->randomElement([
                            'Siswa menunjukkan perkembangan yang baik.',
                            'Perlu lebih rajin dalam mengerjakan tugas.',
                            'Aktif dalam kegiatan kelas.',
                            'Semangat belajar yang tinggi.',
                            'Perlu ditingkatkan dalam hal kehadiran.',
                            null,
                        ]),
                        'principal_notes' => fake()->randomElement([
                            'Terus tingkatkan prestasi.',
                            'Pertahankan kedisiplinan.',
                            'Semoga semakin sukses.',
                            null,
                        ]),
                        'status' => fake()->randomElement(['draft', 'finalized']),
                    ]
                );
            }

            $bar->advance();
        }

        $bar->finish();
        $this->command->newLine();
        $this->command->info('Scores and report cards created successfully!');
    }
}
