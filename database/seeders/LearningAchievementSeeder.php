<?php

namespace Database\Seeders;

use App\Models\Level;
use App\Models\Subject;
use App\Models\LearningAchievement;
use Illuminate\Database\Seeder;

class LearningAchievementSeeder extends Seeder
{
    /**
     * Phase maps for each level (paket).
     * Maps class_level (tingkat kelas) to Kurikulum Merdeka phase.
     */
    private array $phaseMaps = [
        'Paket A' => [
            '1' => 'A', '2' => 'A',
            '3' => 'B', '4' => 'B',
            '5' => 'C', '6' => 'C',
        ],
        'Paket B' => [
            '1' => 'D', '2' => 'D', '3' => 'D',
        ],
        'Paket C' => [
            '1' => 'E',
            '2' => 'F', '3' => 'F',
        ],
    ];

    public function run(): void
    {
        // 1. Update levels with phase_map
        $levels = Level::all();

        foreach ($levels as $level) {
            foreach ($this->phaseMaps as $paketName => $map) {
                if (str_contains($level->name, $paketName)) {
                    $level->update(['phase_map' => $map]);
                    break;
                }
            }
        }

        // 2. Create default CPs (learning_achievements) for each subject
        $subjects = Subject::with('level')->get();

        foreach ($subjects as $subject) {
            if (!$subject->level_id) {
                continue;
            }

            $level = $subject->level;
            if (!$level || !$level->phase_map) {
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
