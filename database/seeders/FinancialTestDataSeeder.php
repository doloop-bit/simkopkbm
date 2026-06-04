<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\BudgetPlan;
use App\Models\BudgetPlanItem;
use App\Models\FeeCategory;
use App\Models\Level;
use App\Models\StudentBilling;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FinancialTestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Generating Financial Test Data for Dashboard...');

        $admin = User::where('role', 'admin')->first();
        if (!$admin) {
            $admin = User::factory()->create([
                'name' => 'Super Admin',
                'email' => 'admin@test.com',
                'role' => 'admin',
            ]);
        }

        $academicYear = AcademicYear::where('is_active', true)->first();
        if (!$academicYear) {
            $academicYear = AcademicYear::create([
                'name' => '2025/2026',
                'start_date' => '2025-07-01',
                'end_date' => '2026-06-30',
                'is_active' => true,
                'status' => 'open',
            ]);
        }

        $levels = Level::all();
        if ($levels->isEmpty()) {
            $this->command->error('No levels found. Please run LevelSeeder or DummyDataSeeder first.');
            return;
        }

        foreach ($levels as $level) {
            $this->command->info("Processing Level: {$level->name}");

            // 1. Fee Category (SPP)
            $feeCategory = FeeCategory::firstOrCreate(
                ['level_id' => $level->id, 'name' => "SPP {$level->name}"],
                ['code' => "SPP-" . strtoupper(str_replace(' ', '', $level->name)), 'default_amount' => 250000]
            );

            // 2. Budget Plan
            $budgetPlan = BudgetPlan::firstOrCreate(
                [
                    'level_id' => $level->id,
                    'academic_year_id' => $academicYear->id,
                    'title' => "RAB Operasional {$level->name} " . $academicYear->name,
                ],
                [
                    'total_amount' => 75000000,
                    'status' => 'approved',
                    'is_active' => true,
                    'submitted_by' => $admin->id,
                    'approved_by' => $admin->id,
                ]
            );

            // 3. Budget Plan Items & Expenses
            $items = [
                ['name' => 'Pengadaan Modul Belajar', 'total' => 15000000],
                ['name' => 'Kegiatan Outbound', 'total' => 20000000],
                ['name' => 'Honor Tutor Tamu', 'total' => 25000000],
                ['name' => 'Pemeliharaan Sarana', 'total' => 15000000],
            ];

            $standardItems = \App\Models\StandardBudgetItem::all();

            foreach ($items as $index => $itemData) {
                $bpItem = BudgetPlanItem::firstOrCreate(
                    [
                        'budget_plan_id' => $budgetPlan->id,
                        'name' => $itemData['name'],
                    ],
                    [
                        'standard_budget_item_id' => $standardItems->random()->id ?? 1,
                        'quantity' => 1,
                        'unit' => 'Paket',
                        'amount' => $itemData['total'],
                        'total' => $itemData['total'],
                    ]
                );

                // Create some past expenses (rolling 6 months)
                for ($i = 5; $i >= 0; $i--) {
                    $date = now()->subMonths($i)->day(rand(1, 28));
                    
                    // Avoid duplicate transactions if run twice by checking notes/date/amount
                    $exists = Transaction::where('type', 'expense')
                        ->where('budget_plan_id', $budgetPlan->id)
                        ->where('budget_plan_item_id', $bpItem->id)
                        ->where('payment_date', $date->toDateString())
                        ->exists();

                    if (!$exists) {
                        Transaction::create([
                            'type' => 'expense',
                            'budget_plan_id' => $budgetPlan->id,
                            'budget_plan_item_id' => $bpItem->id,
                            'user_id' => $admin->id,
                            'amount' => rand(500000, 2000000),
                            'payment_date' => $date,
                            'payment_method' => 'cash',
                            'notes' => "Pengeluaran rutin {$bpItem->name}",
                        ]);
                    }
                }
            }

            // 4. Student Billings & Income Transactions
            $students = User::where('role', 'siswa')
                ->whereHas('studentProfile.classroom', function($q) use ($level) {
                    $q->where('level_id', $level->id);
                })->get();

            if ($students->isEmpty()) {
                $this->command->warn("No students found for level {$level->name}. Skipping billings.");
            }

            foreach ($students as $student) {
                // Create billings for last 4 months
                for ($i = 3; $i >= 0; $i--) {
                    $monthDate = now()->subMonths($i)->startOfMonth();
                    $billing = StudentBilling::firstOrCreate(
                        [
                            'student_id' => $student->id,
                            'fee_category_id' => $feeCategory->id,
                            'academic_year_id' => $academicYear->id,
                            'due_date' => $monthDate->copy()->addDays(10)->toDateString(),
                        ],
                        [
                            'amount' => $feeCategory->default_amount,
                            'paid_amount' => 0,
                            'status' => 'unpaid',
                        ]
                    );

                    // Randomly mark as paid or partially paid if not already paid
                    if ($billing->status === 'unpaid') {
                        $rand = rand(1, 10);
                        if ($rand > 2) { // 80% chance some payment
                            $isFull = $rand > 4;
                            $paidAmount = $isFull ? $billing->amount : ($billing->amount * 0.6);
                            
                            Transaction::create([
                                'type' => 'income',
                                'student_billing_id' => $billing->id,
                                'fee_category_id' => $feeCategory->id,
                                'user_id' => $admin->id,
                                'amount' => $paidAmount,
                                'payment_date' => $monthDate->copy()->addDays(rand(1, 15)),
                                'payment_method' => 'transfer',
                                'notes' => "Pembayaran {$feeCategory->name} - " . $monthDate->translatedFormat('F Y'),
                            ]);

                            $billing->update([
                                'paid_amount' => $paidAmount,
                                'status' => ($paidAmount >= $billing->amount) ? 'paid' : 'partial',
                            ]);
                        }
                    }
                }
            }

            // 5. Global Income (Donation)
            $donationCat = FeeCategory::firstOrCreate(
                ['code' => "DONASI-" . strtoupper(str_replace(' ', '', $level->name))],
                ['name' => "Donasi {$level->name}", 'level_id' => $level->id, 'default_amount' => 0]
            );

            $donationDate = now()->subDays(rand(1, 10))->toDateString();
            $existsDonation = Transaction::where('type', 'income')
                ->where('fee_category_id', $donationCat->id)
                ->where('payment_date', $donationDate)
                ->exists();

            if (!$existsDonation) {
                Transaction::create([
                    'type' => 'income',
                    'fee_category_id' => $donationCat->id,
                    'user_id' => $admin->id,
                    'amount' => 2500000,
                    'payment_date' => $donationDate,
                    'payment_method' => 'cash',
                    'notes' => 'Donasi pengembangan sarana',
                ]);
            }
        }

        $this->command->info('Financial Test Data Generated successfully!');
    }
}
