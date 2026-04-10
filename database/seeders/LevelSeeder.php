<?php

namespace Database\Seeders;

use App\Models\Level;
use Illuminate\Database\Seeder;

class LevelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $phaseMaps = Level::defaultPhases();

        foreach ($phaseMaps as $edLevel => $map) {
            Level::where('education_level', $edLevel)->update([
                'phase_map' => $map,
            ]);
        }
    }
}
