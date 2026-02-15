<?php

namespace Database\Seeders;

use App\Models\BudgetCategory;
use App\Models\BudgetPlan;
use App\Models\BudgetPlanItem;
use App\Models\FeeCategory;
use App\Models\Level;
use App\Models\StandardBudgetItem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class FinancialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create Yayasan User
        User::firstOrCreate(
            ['email' => 'yayasan@sekolah.com'],
            [
                'name' => 'Ketua Yayasan',
                'password' => Hash::make('password'),
                'role' => 'yayasan',
                'is_active' => true,
            ]
        );
        $this->command->info('User Yayasan created.');

        // 2. Ensure Levels exist (should be created by DummySeeder)
        $levels = Level::all();
        if ($levels->isEmpty()) {
            $this->command->warn('No levels found! Please run DummySeeder first.');
            return;
        }

        // 3. Create Treasurer and Headmaster for each level
        foreach ($levels as $level) {
            $levelSlug = Str::slug($level->name);
            
            // Treasurer
            User::firstOrCreate(
                ['email' => "bendahara.{$levelSlug}@sekolah.com"],
                [
                    'name' => "Bendahara {$level->name}",
                    'password' => Hash::make('password'),
                    'role' => 'bendahara',
                    'managed_level_id' => $level->id,
                    'is_active' => true,
                ]
            );

            // Headmaster (Kepsek)
            User::firstOrCreate(
                ['email' => "kepsek.{$levelSlug}@sekolah.com"],
                [
                    'name' => "Kepsek {$level->name}",
                    'password' => Hash::make('password'),
                    'role' => 'kepsek',
                    'managed_level_id' => $level->id,
                    'is_active' => true,
                ]
            );

            // Create Sample Fee Category for this level
            FeeCategory::firstOrCreate(
                ['code' => "SPP-{$levelSlug}"],
                [
                    'name' => "SPP Bulan {$level->name}",
                    'level_id' => $level->id,
                    'description' => "Sumbangan Pembinaan Pendidikan untuk {$level->name}",
                    'default_amount' => match($level->education_level) {
                        'paud' => 150000,
                        'sd' => 250000,
                        'smp' => 350000,
                        'sma' => 450000,
                        default => 200000,
                    }
                ]
            );
        }
        $this->command->info('Treasurers, Headmasters, and Fee Categories created.');

        // 4. Create Budget Master Data (Categories & Standard Items)
        $categories = [
            'ADM' => [
                'name' => 'Belanja Administrasi',
                'items' => [
                    ['name' => 'Kertas HVS A4', 'unit' => 'Rim', 'price' => 50000],
                    ['name' => 'Tinta Printer Epson', 'unit' => 'Botol', 'price' => 120000],
                    ['name' => 'Map Plastik', 'unit' => 'Lusin', 'price' => 25000],
                    ['name' => 'Pulpen Standard', 'unit' => 'Pak', 'price' => 30000],
                ]
            ],
            'OPS' => [
                'name' => 'Operasional & Kegiatan',
                'items' => [
                    ['name' => 'Snack Rapat', 'unit' => 'Kotak', 'price' => 15000],
                    ['name' => 'Air Mineral Gelas', 'unit' => 'Dus', 'price' => 25000],
                    ['name' => 'Spanduk Kegiatan', 'unit' => 'Meter', 'price' => 35000],
                    ['name' => 'Transportasi Dinas', 'unit' => 'Orang', 'price' => 100000],
                ]
            ],
            'GAJI' => [
                'name' => 'Belanja Pegawai/Honor',
                'items' => [
                    ['name' => 'Honor Guru Tetap', 'unit' => 'Bulan', 'price' => 2500000],
                    ['name' => 'Honor Guru Ekstra', 'unit' => 'Jam', 'price' => 50000],
                    ['name' => 'Tunjangan Wali Kelas', 'unit' => 'Bulan', 'price' => 200000],
                ]
            ],
        ];

        foreach ($categories as $code => $data) {
            $cat = BudgetCategory::firstOrCreate(
                ['code' => $code],
                ['name' => $data['name']]
            );

            foreach ($data['items'] as $item) {
                StandardBudgetItem::firstOrCreate(
                    ['name' => $item['name'], 'budget_category_id' => $cat->id],
                    [
                        'unit' => $item['unit'],
                        'default_price' => $item['price'],
                        'is_active' => true,
                    ]
                );
            }
        }
        $this->command->info('Budget Master Data (Categories & Items) created.');
    }
}
