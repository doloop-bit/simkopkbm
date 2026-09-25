<?php

namespace Database\Seeders;

use App\Models\FinancialUnit;
use App\Models\Level;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;

class FinancialUnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = [
            [
                'code' => 'PAUD',
                'name' => 'Kas PAUD',
                'description' => 'Unit Pengelolaan Kas PAUD',
            ],
            [
                'code' => 'PAKET_A',
                'name' => 'Kas Paket A',
                'description' => 'Unit Pengelolaan Kas Paket A (Setara SD)',
            ],
            [
                'code' => 'PAKET_BC',
                'name' => 'Kas Paket B & C',
                'description' => 'Unit Pengelolaan Kas Gabungan Paket B (Setara SMP) dan Paket C (Setara SMA)',
            ],
        ];

        $unitModels = [];
        foreach ($units as $unitData) {
            $unitModels[$unitData['code']] = FinancialUnit::firstOrCreate(
                ['code' => $unitData['code']],
                ['name' => $unitData['name'], 'description' => $unitData['description']]
            );
        }

        // Link Levels to Financial Units
        // 1. PAUD
        Level::where(function ($q) {
            $q->where('education_level', 'paud')->orWhere('name', 'like', '%paud%');
        })->update(['financial_unit_id' => $unitModels['PAUD']->id]);

        // 2. Paket A (SD)
        Level::where(function ($q) {
            $q->where('education_level', 'sd')->orWhere('name', 'like', '%paket a%');
        })->update(['financial_unit_id' => $unitModels['PAKET_A']->id]);

        // 3. Paket B & C (SMP, SMA)
        Level::where(function ($q) {
            $q->whereIn('education_level', ['smp', 'sma'])
                ->orWhere('name', 'like', '%paket b%')
                ->orWhere('name', 'like', '%paket c%');
        })->update(['financial_unit_id' => $unitModels['PAKET_BC']->id]);

        // If any remaining levels have no financial_unit_id, default to PAKET_BC or first
        Level::whereNull('financial_unit_id')->update(['financial_unit_id' => $unitModels['PAKET_BC']->id]);

        // Update Users (bendahara with managed_level_id)
        $levels = Level::whereNotNull('financial_unit_id')->get()->keyBy('id');
        User::whereNotNull('managed_level_id')->each(function (User $user) use ($levels) {
            if (isset($levels[$user->managed_level_id])) {
                $user->update(['managed_financial_unit_id' => $levels[$user->managed_level_id]->financial_unit_id]);
            }
        });

        // Backfill existing transactions
        Transaction::whereNull('financial_unit_id')->with([
            'billing.student.studentProfile.classroom.level',
            'feeCategory.level',
            'budgetPlan.level',
            'user',
        ])->chunk(100, function ($transactions) {
            foreach ($transactions as $tx) {
                $unitId = $tx->billing?->student?->studentProfile?->classroom?->level?->financial_unit_id
                    ?? $tx->feeCategory?->level?->financial_unit_id
                    ?? $tx->budgetPlan?->level?->financial_unit_id
                    ?? $tx->user?->managedFinancialUnitId();

                if ($unitId) {
                    $tx->update(['financial_unit_id' => $unitId]);
                }
            }
        });
    }
}
