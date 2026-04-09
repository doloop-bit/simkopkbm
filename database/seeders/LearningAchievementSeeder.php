<?php

namespace Database\Seeders;

use App\Models\LearningAchievement;
use App\Models\Level;
use App\Models\Subject;
use Illuminate\Database\Seeder;

class LearningAchievementSeeder extends Seeder
{
    /**
     * Phase maps for each level (paket).
     * Maps class_level (tingkat kelas) to Kurikulum Merdeka phase.
     */
    private array $phaseMaps = [
        'PAUD' => [
            '0' => 'Fondasi',
        ],
        'Paket A' => [
            '1' => 'A',
            '2' => 'A',
            '3' => 'B',
            '4' => 'B',
            '5' => 'C',
            '6' => 'C',
        ],
        'Paket B' => [
            '7' => 'D',
            '8' => 'D',
            '9' => 'D',
        ],
        'Paket C' => [
            '10' => 'E',
            '11' => 'F',
            '12' => 'F',
        ],
    ];

    public function run(): void
    {
        // 1. Create default CPs (learning_achievements) for each subject
        // This is a dummy creation for initial setup. CP management is available in the UI.
        $subjects = Subject::with('level')->get();

        foreach ($subjects as $subject) {
            if (! $subject->level_id) {
                continue;
            }

            $level = $subject->level;
            if (! $level || ! $level->phase_map) {
                continue;
            }

            // Get unique phases for this level
            $phases = array_unique(array_values($level->phase_map));
            sort($phases);

            foreach ($phases as $phase) {
                LearningAchievement::firstOrCreate(
                    [
                        'subject_id' => $subject->id,
                        'phase' => $phase,
                    ],
                    [
                        'description' => "CP Fase {$phase} - {$subject->name}",
                    ]
                );
            }
        }

        $this->command->info('Learning achievements (CP) seeded successfully.');
        $this->command->info('Phase maps updated for levels.');
    }
}
