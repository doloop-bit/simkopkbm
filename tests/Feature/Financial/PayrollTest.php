<?php

use App\Models\AcademicYear;
use App\Models\Payroll;
use App\Models\SalaryTemplate;
use App\Models\SalaryTemplateComponent;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\withoutVite;

beforeEach(fn () => withoutVite());

// ── Route Access Tests ──────────────────────────────────────────────────────

test('admin can access salary templates page', function () {
    $user = User::factory()->admin()->create();

    actingAs($user)
        ->get(route('financial.salary-templates'))
        ->assertOk()
        ->assertSeeLivewire('admin.financial.salary-templates');
});

test('bendahara can access salary templates page', function () {
    $user = User::factory()->bendahara()->create();

    actingAs($user)
        ->get(route('financial.salary-templates'))
        ->assertOk();
});

test('admin can access payroll process page', function () {
    $user = User::factory()->admin()->create();

    actingAs($user)
        ->get(route('financial.payroll-process'))
        ->assertOk()
        ->assertSeeLivewire('admin.financial.payroll-process');
});

test('siswa cannot access payroll pages', function () {
    $user = User::factory()->siswa()->create();

    actingAs($user)
        ->get(route('financial.salary-templates'))
        ->assertForbidden();
});

// ── Salary Template CRUD Tests ──────────────────────────────────────────────

test('admin can save salary template for a PTK user', function () {
    $admin = User::factory()->admin()->create();
    $guru = User::factory()->guru()->create();

    Livewire::actingAs($admin)
        ->test('admin.financial.salary-templates')
        ->call('editTemplate', $guru->id)
        ->assertSet('editingUserId', $guru->id)
        ->set('base_salary', 3000000)
        ->set('effective_date', '2026-01-01')
        ->set('notes', 'Gaji awal')
        ->set('allowances', [
            ['type' => 'allowance', 'name' => 'Tunjangan Transport', 'amount' => 500000, 'description' => ''],
        ])
        ->set('deductions', [
            ['type' => 'deduction', 'name' => 'BPJS', 'amount' => 100000, 'description' => 'Potongan BPJS bulanan'],
        ])
        ->call('save')
        ->assertSet('templateModal', false)
        ->assertHasNoErrors();

    $template = SalaryTemplate::where('user_id', $guru->id)->first();
    expect($template)->not->toBeNull()
        ->and($template->base_salary)->toBe(3000000)
        ->and($template->components)->toHaveCount(2);

    $allowance = $template->allowances()->first();
    expect($allowance->name)->toBe('Tunjangan Transport')
        ->and($allowance->amount)->toBe(500000);

    $deduction = $template->deductions()->first();
    expect($deduction->name)->toBe('BPJS')
        ->and($deduction->amount)->toBe(100000);
});

test('admin can update existing salary template', function () {
    $admin = User::factory()->admin()->create();
    $guru = User::factory()->guru()->create();

    $template = SalaryTemplate::create([
        'user_id' => $guru->id,
        'base_salary' => 2000000,
        'effective_date' => '2025-01-01',
    ]);

    SalaryTemplateComponent::create([
        'salary_template_id' => $template->id,
        'type' => 'allowance',
        'name' => 'Transport Lama',
        'amount' => 300000,
    ]);

    Livewire::actingAs($admin)
        ->test('admin.financial.salary-templates')
        ->call('editTemplate', $guru->id)
        ->set('base_salary', 3500000)
        ->set('allowances', [
            ['type' => 'allowance', 'name' => 'Transport Baru', 'amount' => 600000, 'description' => ''],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $template->refresh();
    expect($template->base_salary)->toBe(3500000);
    expect($template->components)->toHaveCount(1);
    expect($template->components->first()->name)->toBe('Transport Baru');
});

test('add and remove allowance items in template modal', function () {
    $admin = User::factory()->admin()->create();
    $guru = User::factory()->guru()->create();

    $component = Livewire::actingAs($admin)
        ->test('admin.financial.salary-templates')
        ->call('editTemplate', $guru->id)
        ->assertSet('allowances', []);

    $component->call('addAllowance')
        ->assertCount('allowances', 1);

    $component->call('addAllowance')
        ->assertCount('allowances', 2);

    $component->call('removeAllowance', 0)
        ->assertCount('allowances', 1);
});

// ── Payroll Process Tests ───────────────────────────────────────────────────

test('admin can generate payroll slips from templates', function () {
    $admin = User::factory()->admin()->create();
    $guru = User::factory()->guru()->create();
    $academicYear = AcademicYear::factory()->create(['is_active' => true]);

    SalaryTemplate::create([
        'user_id' => $guru->id,
        'base_salary' => 3000000,
        'effective_date' => '2026-01-01',
    ]);

    SalaryTemplateComponent::create([
        'salary_template_id' => SalaryTemplate::where('user_id', $guru->id)->first()->id,
        'type' => 'allowance',
        'name' => 'Transport',
        'amount' => 500000,
    ]);

    $month = now()->format('Y-m');

    Livewire::actingAs($admin)
        ->test('admin.financial.payroll-process')
        ->set('selectedMonth', $month)
        ->set('selectedAcademicYearId', $academicYear->id)
        ->call('generatePayrolls')
        ->assertHasNoErrors();

    $payroll = Payroll::where('user_id', $guru->id)->where('month', $month)->first();
    expect($payroll)->not->toBeNull()
        ->and($payroll->base_salary)->toBe(3000000)
        ->and($payroll->total_allowances)->toBe(500000)
        ->and($payroll->total_deductions)->toBe(0)
        ->and($payroll->net_salary)->toBe(3500000)
        ->and($payroll->status)->toBe('draft');
});

test('generating payrolls skips existing slips', function () {
    $admin = User::factory()->admin()->create();
    $guru = User::factory()->guru()->create();
    $academicYear = AcademicYear::factory()->create(['is_active' => true]);

    SalaryTemplate::create([
        'user_id' => $guru->id,
        'base_salary' => 3000000,
        'effective_date' => '2026-01-01',
    ]);

    $month = now()->format('Y-m');
    Payroll::create([
        'user_id' => $guru->id,
        'academic_year_id' => $academicYear->id,
        'month' => $month,
        'base_salary' => 3000000,
        'net_salary' => 3000000,
    ]);

    Livewire::actingAs($admin)
        ->test('admin.financial.payroll-process')
        ->set('selectedMonth', $month)
        ->set('selectedAcademicYearId', $academicYear->id)
        ->call('generatePayrolls');

    expect(Payroll::where('user_id', $guru->id)->where('month', $month)->count())->toBe(1);
});

test('admin can edit draft payroll slip', function () {
    $admin = User::factory()->admin()->create();
    $guru = User::factory()->guru()->create();
    $academicYear = AcademicYear::factory()->create(['is_active' => true]);

    $payroll = Payroll::create([
        'user_id' => $guru->id,
        'academic_year_id' => $academicYear->id,
        'month' => now()->format('Y-m'),
        'base_salary' => 3000000,
        'components' => [['type' => 'allowance', 'name' => 'Transport', 'amount' => 500000, 'description' => null]],
        'total_allowances' => 500000,
        'total_deductions' => 0,
        'net_salary' => 3500000,
        'status' => 'draft',
    ]);

    Livewire::actingAs($admin)
        ->test('admin.financial.payroll-process')
        ->call('showDetail', $payroll->id)
        ->set('detail_base_salary', 3500000)
        ->set('detail_allowances', [
            ['type' => 'allowance', 'name' => 'Transport', 'amount' => 600000, 'description' => ''],
        ])
        ->call('saveDetail')
        ->assertHasNoErrors();

    $payroll->refresh();
    expect($payroll->base_salary)->toBe(3500000)
        ->and($payroll->total_allowances)->toBe(600000)
        ->and($payroll->net_salary)->toBe(4100000);
});

test('admin can finalize a payroll slip', function () {
    $admin = User::factory()->admin()->create();
    $guru = User::factory()->guru()->create();
    $academicYear = AcademicYear::factory()->create(['is_active' => true]);

    $payroll = Payroll::create([
        'user_id' => $guru->id,
        'academic_year_id' => $academicYear->id,
        'month' => now()->format('Y-m'),
        'base_salary' => 3000000,
        'net_salary' => 3000000,
        'status' => 'draft',
    ]);

    Livewire::actingAs($admin)
        ->test('admin.financial.payroll-process')
        ->call('finalizePayroll', $payroll->id);

    $payroll->refresh();
    expect($payroll->status)->toBe('finalized');
});

test('admin can finalize all draft payrolls at once', function () {
    $admin = User::factory()->admin()->create();
    $academicYear = AcademicYear::factory()->create(['is_active' => true]);
    $month = now()->format('Y-m');

    foreach (range(1, 3) as $i) {
        $guru = User::factory()->guru()->create();
        Payroll::create([
            'user_id' => $guru->id,
            'academic_year_id' => $academicYear->id,
            'month' => $month,
            'base_salary' => 3000000,
            'net_salary' => 3000000,
            'status' => 'draft',
        ]);
    }

    Livewire::actingAs($admin)
        ->test('admin.financial.payroll-process')
        ->set('selectedMonth', $month)
        ->call('finalizeAll');

    expect(Payroll::where('month', $month)->where('status', 'finalized')->count())->toBe(3);
});

// ── Model Tests ─────────────────────────────────────────────────────────────

test('payroll model correctly groups components', function () {
    $payroll = new Payroll([
        'components' => [
            ['type' => 'allowance', 'name' => 'Transport', 'amount' => 500000, 'description' => null],
            ['type' => 'deduction', 'name' => 'BPJS', 'amount' => 100000, 'description' => 'Potongan bulanan'],
            ['type' => 'allowance', 'name' => 'Makan', 'amount' => 300000, 'description' => null],
        ],
    ]);

    $grouped = $payroll->getGroupedComponents();

    expect($grouped['allowances'])->toHaveCount(2)
        ->and($grouped['deductions'])->toHaveCount(1);
});

test('salary template computes net salary correctly', function () {
    $guru = User::factory()->guru()->create();

    $template = SalaryTemplate::create([
        'user_id' => $guru->id,
        'base_salary' => 3000000,
        'effective_date' => '2026-01-01',
    ]);

    SalaryTemplateComponent::create([
        'salary_template_id' => $template->id,
        'type' => 'allowance',
        'name' => 'Transport',
        'amount' => 500000,
    ]);

    SalaryTemplateComponent::create([
        'salary_template_id' => $template->id,
        'type' => 'deduction',
        'name' => 'BPJS',
        'amount' => 100000,
    ]);

    expect($template->net_salary)->toBe(3400000);
});
