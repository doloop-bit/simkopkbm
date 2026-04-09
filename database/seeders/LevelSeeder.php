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
        $phaseMaps = [
            'paud' => [
                '0' => 'Fondasi',
            ],
            'sd' => [
                '1' => 'A',
                '2' => 'A',
                '3' => 'B',
                '4' => 'B',
                '5' => 'C',
                '6' => 'C',
            ],
            'smp' => [
                '7' => 'D',
                '8' => 'D',
                '9' => 'D',
            ],
            'sma' => [
                '10' => 'E',
                '11' => 'F',
                '12' => 'F',
            ],
        ];

        foreach ($phaseMaps as $edLevel => $map) {
            Level::where('education_level', $edLevel)->update([
                'phase_map' => $map,
            ]);
        }
    }
}
