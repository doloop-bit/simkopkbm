<?php

declare(strict_types=1);

use App\Models\AcademicYear;
use App\Models\BudgetPlan;
use App\Models\BudgetPlanItem;
use App\Models\FeeCategory;
use App\Models\Level;
use App\Models\StudentBilling;
use App\Models\Transaction;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->withoutVite();
});

it('renders financial charts section for admin', function () {
    $user = User::factory()->admin()->create();
    $this->actingAs($user);

    Livewire::test('admin.dashboard')
        ->assertSee('Selamat Datang, '.$user->name)
        ->assertSee('Analisis Keuangan')
        ->assertSee('Arus Kas Bulanan')
        ->assertSee('Komposisi Pemasukan')
        ->assertSee('Komposisi Pengeluaran')
        ->assertSee('Tingkat Koleksi Tagihan')
        ->assertSee('Realisasi RAB')
        ->assertSee('Tren RAB Tahunan');
});

it('renders financial charts section for bendahara', function () {
    $level = Level::first() ?? Level::factory()->create();
    $user = User::factory()->bendahara()->create(['managed_level_id' => $level->id]);
    $this->actingAs($user);

    Livewire::test('admin.dashboard')
        ->assertSee('Analisis Keuangan')
        ->assertSee('Arus Kas Bulanan');
});

it('renders financial charts section for yayasan without debtor table', function () {
    $user = User::factory()->yayasan()->create();
    $this->actingAs($user);

    Livewire::test('admin.dashboard')
        ->assertSee('Analisis Keuangan')
        ->assertDontSee('Siswa Menunggak');
});

it('renders financial charts section for kepsek without debtor table', function () {
    $user = User::factory()->kepsek()->create();
    $this->actingAs($user);

    Livewire::test('admin.dashboard')
        ->assertSee('Analisis Keuangan')
        ->assertDontSee('Siswa Menunggak');
});

it('shows debtors table for admin when debtors exist', function () {
    $user = User::factory()->admin()->create();
    $this->actingAs($user);

    $student = User::factory()->siswa()->create();

    $activeYear = AcademicYear::where('is_active', true)->first();
    if (! $activeYear) {
        $activeYear = AcademicYear::create([
            'name' => '2025/2026',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'is_active' => true,
            'status' => 'active',
        ]);
    }

    $category = FeeCategory::first() ?? FeeCategory::create([
        'name' => 'SPP',
        'code' => 'SPP',
        'default_amount' => 200000,
    ]);

    StudentBilling::create([
        'student_id' => $student->id,
        'fee_category_id' => $category->id,
        'academic_year_id' => $activeYear->id,
        'amount' => 200000,
        'paid_amount' => 0,
        'status' => 'unpaid',
    ]);

    Livewire::test('admin.dashboard')
        ->assertSee('Siswa Menunggak')
        ->assertSee($student->name);
});

it('displays correct cash flow data with transactions', function () {
    $user = User::factory()->admin()->create();
    $this->actingAs($user);

    $category = FeeCategory::first() ?? FeeCategory::create([
        'name' => 'SPP',
        'code' => 'SPP',
        'default_amount' => 100000,
    ]);

    Transaction::create([
        'type' => 'income',
        'fee_category_id' => $category->id,
        'user_id' => $user->id,
        'amount' => 500000,
        'payment_date' => now(),
        'payment_method' => 'cash',
    ]);

    Livewire::test('admin.dashboard')
        ->assertSee('Arus Kas Bulanan')
        ->assertSee('Rp 500.000');
});

it('handles empty financial data gracefully', function () {
    $user = User::factory()->admin()->create();
    $this->actingAs($user);

    Livewire::test('admin.dashboard')
        ->assertSee('Analisis Keuangan')
        ->assertSee('Belum ada pemasukan bulan ini')
        ->assertSee('Belum ada pengeluaran bulan ini');
});

it('shows budget realization when active budget plans exist', function () {
    $user = User::factory()->admin()->create();
    $this->actingAs($user);

    $level = Level::first() ?? Level::factory()->create();
    $activeYear = AcademicYear::where('is_active', true)->first();
    if (! $activeYear) {
        $activeYear = AcademicYear::create([
            'name' => '2025/2026',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'is_active' => true,
            'status' => 'active',
        ]);
    }

    $plan = BudgetPlan::create([
        'level_id' => $level->id,
        'academic_year_id' => $activeYear->id,
        'title' => 'RAB Operasional 2026',
        'total_amount' => 5000000,
        'status' => 'approved',
        'is_active' => true,
        'submitted_by' => $user->id,
    ]);

    $budgetCategory = \App\Models\BudgetCategory::create([
        'name' => 'Operasional',
        'code' => 'OPS',
        'is_active' => true,
    ]);

    $standardItem = \App\Models\StandardBudgetItem::create([
        'budget_category_id' => $budgetCategory->id,
        'name' => 'ATK',
        'unit' => 'paket',
        'default_price' => 500000,
        'is_active' => true,
    ]);

    BudgetPlanItem::create([
        'budget_plan_id' => $plan->id,
        'standard_budget_item_id' => $standardItem->id,
        'name' => 'ATK',
        'quantity' => 1,
        'unit' => 'paket',
        'amount' => 500000,
        'total' => 500000,
    ]);

    Livewire::test('admin.dashboard')
        ->assertSee('Realisasi RAB')
        ->assertSee('Tren RAB Tahunan');
});
