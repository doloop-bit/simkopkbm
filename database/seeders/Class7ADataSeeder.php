<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Subject;
use App\Models\ExtracurricularActivity;
use App\Models\P5Project;
use Carbon\Carbon;

class Class7ADataSeeder extends Seeder
{
    public function run(): void
    {
        $academicYearId = 1;
        $classroomId = 3; // Kelas 7A
        $levelId = 2; // SMP / Paket B
        $semester = '1';
        $studentUserIds = [14, 15, 16, 17, 18];

        echo "Seeding data for Class 7A (ID: $classroomId)...\n";

        // 1. Create Subjects for Level 2 (SMP) if not exist
        // Note: Code must be unique across all levels
        $subjects = [
            ['name' => 'Pendidikan Agama dan Budi Pekerti', 'code' => 'PABP-PB'],
            ['name' => 'Pendidikan Pancasila', 'code' => 'PP-PB'],
            ['name' => 'Bahasa Indonesia', 'code' => 'BIN-PB'],
            ['name' => 'Matematika', 'code' => 'MAT-PB'],
            ['name' => 'Ilmu Pengetahuan Alam', 'code' => 'IPA-PB'],
            ['name' => 'Ilmu Pengetahuan Sosial', 'code' => 'IPS-PB'],
            ['name' => 'Bahasa Inggris', 'code' => 'BIG-PB'],
            ['name' => 'Seni Budaya', 'code' => 'SBK-PB'],
            ['name' => 'Pendidikan Jasmani, Olahraga, dan Kesehatan', 'code' => 'PJOK-PB'],
            ['name' => 'Informatika', 'code' => 'INF-PB'],
        ];

        $subjectIds = [];
        foreach ($subjects as $sub) {
            $subject = Subject::firstOrCreate(
                ['name' => $sub['name'], 'level_id' => $levelId],
                ['code' => $sub['code']]
            );
            $subjectIds[] = $subject->id;
        }
        
        echo "Subjects checked/created.\n";

        // 2. Competency Assessments (Grades)
        $competencyLevels = ['BB', 'MB', 'BSH', 'SB'];
        $descriptions = [
            'BB' => 'Perlu bimbingan lebih lanjut dalam memahami materi.',
            'MB' => 'Mulai memahami materi namun masih perlu latihan.',
            'BSH' => 'Sudah memahami materi dengan baik sesuai target.',
            'SB' => 'Sangat baik dalam memahami dan menerapkan materi.',
        ];

        foreach ($studentUserIds as $studentId) {
            foreach ($subjectIds as $subjectId) {
                // Check if exists
                $exists = DB::table('competency_assessments')
                    ->where('student_id', $studentId)
                    ->where('subject_id', $subjectId)
                    ->where('academic_year_id', $academicYearId)
                    ->where('semester', $semester)
                    ->exists();

                if (!$exists) {
                    $level = $competencyLevels[array_rand($competencyLevels)];
                    DB::table('competency_assessments')->insert([
                        'student_id' => $studentId,
                        'subject_id' => $subjectId,
                        'academic_year_id' => $academicYearId,
                        'classroom_id' => $classroomId,
                        'semester' => $semester,
                        'competency_level' => $level,
                        'achievement_description' => $descriptions[$level],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
        echo "Competency Assessments created.\n";

        // 3. Extracurriculars
        $extras = ['Pramuka', 'Futsal', 'Tari'];
        $extraIds = [];
        foreach ($extras as $extraName) {
            $extra = ExtracurricularActivity::firstOrCreate(
                ['name' => $extraName],
                ['description' => "Kegiatan $extraName", 'is_active' => true]
            );
            $extraIds[] = $extra->id;
        }

        foreach ($studentUserIds as $studentId) {
            // Assign 1 random extra
            $extraId = $extraIds[array_rand($extraIds)];
            
            $exists = DB::table('extracurricular_assessments')
                ->where('student_id', $studentId)
                ->where('academic_year_id', $academicYearId)
                ->where('semester', $semester)
                ->exists();

            if (!$exists) {
                DB::table('extracurricular_assessments')->insert([
                    'student_id' => $studentId,
                    'extracurricular_activity_id' => $extraId,
                    'academic_year_id' => $academicYearId,
                    'semester' => $semester,
                    'achievement_level' => 'BSH',
                    'description' => 'Aktif mengikuti kegiatan.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
        echo "Extracurricular Assessments created.\n";

        // 4. P5 Projects
        $p5Project = P5Project::firstOrCreate(
            ['name' => 'Sampahku Tanggung Jawabku', 'academic_year_id' => $academicYearId, 'semester' => $semester],
            [
                'description' => 'Projek pengelolaan sampah',
                'dimension' => 'beriman',
                'start_date' => now()->subMonths(3),
                'end_date' => now()->subMonths(1),
            ]
        );

        foreach ($studentUserIds as $studentId) {
             $exists = DB::table('p5_assessments')
                ->where('student_id', $studentId)
                ->where('p5_project_id', $p5Project->id)
                ->exists();
                
             if (!$exists) {
                DB::table('p5_assessments')->insert([
                    'student_id' => $studentId,
                    'p5_project_id' => $p5Project->id,
                    'academic_year_id' => $academicYearId,
                    'classroom_id' => $classroomId,
                    'semester' => $semester,
                    'achievement_level' => 'BSH',
                    'description' => 'Siswa aktif berpartisipasi dalam projek.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
             }
        }
        echo "P5 Assessments created.\n";

        // 5. Attendance (Dummy 10 Items)
        // Note: This is simpler than creating full daily attendance for now, 
        // assuming report card generator might calculate from `attendance_items` 
        // OR the user might manual input report card attendance if feature not fully automated.
        // But let's create some 'attendances' and 'attendance_items' to be safe.
        
        $startDate = Carbon::now()->subMonth();
        // Create 5 fake attendance sessions for a subject (e.g. subject[0])
        $attendanceSubjectId = $subjectIds[0];
        
        for ($i = 0; $i < 5; $i++) {
            $date = $startDate->copy()->addDays($i);
            
            // Check if attendance exists
            $attId = DB::table('attendances')->insertGetId([
                'classroom_id' => $classroomId,
                'academic_year_id' => $academicYearId,
                'subject_id' => $attendanceSubjectId,
                'teacher_id' => 1, // Assume admin/teacher with ID 1
                'date' => $date,
                'notes' => 'Pembelajaran harian',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            foreach ($studentUserIds as $studentId) {
                // Random status: mostly 'h' (hadir/present)
                $rand = rand(1, 100);
                if ($rand > 95) $status = 'a'; // alpha
                elseif ($rand > 90) $status = 's'; // sakit
                elseif ($rand > 85) $status = 'i'; // izin
                else $status = 'h'; // hadir
                
                DB::table('attendance_items')->insert([
                    'attendance_id' => $attId,
                    'student_id' => $studentId,
                    'status' => $status,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
        echo "Attendance data created.\n";
    }
}
