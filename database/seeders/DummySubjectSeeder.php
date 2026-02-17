<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Subject;
use App\Models\LearningAchievement;
use App\Models\SubjectTp;
use App\Models\SubjectGrade;

class DummySubjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Truncating existing subject and TP data...');

        Schema::disableForeignKeyConstraints();
        SubjectGrade::truncate();
        SubjectTp::truncate();
        LearningAchievement::truncate();
        Subject::truncate();
        Schema::enableForeignKeyConstraints();

        $phases = ['A', 'B', 'C'];
        $subjectsList = [
            'Bahasa Indonesia',
            'Matematika',
            'Pendidikan Pancasila',
            'Seni Musik',
            'PJOK'
        ];

        foreach ($phases as $phase) {
            foreach ($subjectsList as $index => $name) {
                // Create Subject
                $subject = Subject::create([
                    'name' => "{$name} (Fase {$phase})",
                    'code' => strtoupper(substr($name, 0, 3)) . "-{$phase}",
                    'phase' => $phase,
                ]);

                // Create 2 Learning Achievement (CP) per subject
                for ($cpIndex = 1; $cpIndex <= 2; $cpIndex++) {
                    $cp = LearningAchievement::create([
                        'subject_id' => $subject->id,
                        'phase' => $phase,
                        'description' => "Peserta didik mampu memahami dan menguasai elemen kunci dari {$name} bagian {$cpIndex} pada Fase {$phase}.",
                    ]);

                    // Create 5 TPs per CP with slightly simpler description for dropdown
                    for ($tpIndex = 1; $tpIndex <= 5; $tpIndex++) {
                        SubjectTp::create([
                            'learning_achievement_id' => $cp->id,
                            'code' => "TP.{$phase}.{$index}.{$cpIndex}.{$tpIndex}",
                            'description' => "Mempraktikkan keterampilan {$tpIndex} dari elemen {$cpIndex} dalam {$name}.",
                        ]);
                    }
                }
            }
        }

        $this->command->info('Dummy Subjects and TPs seeded successfully!');
    }
}
