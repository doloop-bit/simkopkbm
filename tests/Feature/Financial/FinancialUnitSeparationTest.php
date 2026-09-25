<?php

declare(strict_types=1);

use App\Models\FeeCategory;
use App\Models\FinancialUnit;
use App\Models\Level;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\FinancialUnitSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->withoutVite();
    (new FinancialUnitSeeder)->run();
});

it('seeds three financial units and combines Paket B and Paket C into one unit', function () {
    $unitPaud = FinancialUnit::where('code', 'PAUD')->first();
    $unitPaketA = FinancialUnit::where('code', 'PAKET_A')->first();
    $unitPaketBC = FinancialUnit::where('code', 'PAKET_BC')->first();

    expect($unitPaud)->not->toBeNull()
        ->and($unitPaketA)->not->toBeNull()
        ->and($unitPaketBC)->not->toBeNull();

    $levelPaud = Level::firstOrCreate(
        ['name' => 'PAUD'],
        ['education_level' => 'paud', 'type' => 'class_teacher']
    );
    $levelPaketA = Level::firstOrCreate(
        ['name' => 'Paket A'],
        ['education_level' => 'sd', 'type' => 'class_teacher']
    );
    $levelPaketB = Level::firstOrCreate(
        ['name' => 'Paket B'],
        ['education_level' => 'smp', 'type' => 'subject_teacher']
    );
    $levelPaketC = Level::firstOrCreate(
        ['name' => 'Paket C'],
        ['education_level' => 'sma', 'type' => 'subject_teacher']
    );

    (new FinancialUnitSeeder)->run();

    $levelPaud->refresh();
    $levelPaketA->refresh();
    $levelPaketB->refresh();
    $levelPaketC->refresh();

    expect($levelPaud->financial_unit_id)->toBe($unitPaud->id)
        ->and($levelPaketA->financial_unit_id)->toBe($unitPaketA->id)
        ->and($levelPaketB->financial_unit_id)->toBe($unitPaketBC->id)
        ->and($levelPaketC->financial_unit_id)->toBe($unitPaketBC->id)
        ->and($levelPaketB->financial_unit_id)->toBe($levelPaketC->financial_unit_id);
});

it('automatically tags financial_unit_id when recording income and expense transactions', function () {
    $unitBC = FinancialUnit::where('code', 'PAKET_BC')->first();
    $levelB = Level::firstOrCreate(
        ['name' => 'Paket B'],
        ['education_level' => 'smp', 'type' => 'subject_teacher', 'financial_unit_id' => $unitBC->id]
    );

    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $category = FeeCategory::create([
        'name' => 'Iuran Paket B',
        'code' => 'IPB-'.uniqid(),
        'level_id' => $levelB->id,
        'default_amount' => 150000,
    ]);

    // Test Livewire transactions recording
    Livewire::test('admin.financial.transactions')
        ->set('type', 'income')
        ->set('is_global', true)
        ->set('fee_category_id', $category->id)
        ->set('pay_amount', 150000)
        ->set('payment_method', 'cash')
        ->set('payment_date', now()->format('Y-m-d'))
        ->call('recordTransaction')
        ->assertHasNoErrors();

    $tx = Transaction::where('fee_category_id', $category->id)->latest()->first();
    expect($tx)->not->toBeNull()
        ->and($tx->financial_unit_id)->toBe($unitBC->id);
});

it('restricts bendahara of Paket B and C to transactions of their financial unit and includes both Paket B and Paket C', function () {
    $unitBC = FinancialUnit::where('code', 'PAKET_BC')->first();
    $unitPaud = FinancialUnit::where('code', 'PAUD')->first();

    $levelB = Level::firstOrCreate(
        ['name' => 'Paket B'],
        ['education_level' => 'smp', 'type' => 'subject_teacher', 'financial_unit_id' => $unitBC->id]
    );
    $levelC = Level::firstOrCreate(
        ['name' => 'Paket C'],
        ['education_level' => 'sma', 'type' => 'subject_teacher', 'financial_unit_id' => $unitBC->id]
    );
    $levelPaud = Level::firstOrCreate(
        ['name' => 'PAUD'],
        ['education_level' => 'paud', 'type' => 'class_teacher', 'financial_unit_id' => $unitPaud->id]
    );

    $catB = FeeCategory::create(['name' => 'SPP Paket B', 'code' => 'SPPB-'.uniqid(), 'level_id' => $levelB->id, 'default_amount' => 100000]);
    $catC = FeeCategory::create(['name' => 'SPP Paket C', 'code' => 'SPPC-'.uniqid(), 'level_id' => $levelC->id, 'default_amount' => 120000]);
    $catPaud = FeeCategory::create(['name' => 'SPP PAUD', 'code' => 'SPPPAUD-'.uniqid(), 'level_id' => $levelPaud->id, 'default_amount' => 80000]);

    $admin = User::factory()->admin()->create();

    $txB = Transaction::create([
        'financial_unit_id' => $unitBC->id,
        'fee_category_id' => $catB->id,
        'type' => 'income',
        'amount' => 100000,
        'payment_date' => now(),
        'payment_method' => 'cash',
        'user_id' => $admin->id,
    ]);

    $txC = Transaction::create([
        'financial_unit_id' => $unitBC->id,
        'fee_category_id' => $catC->id,
        'type' => 'income',
        'amount' => 120000,
        'payment_date' => now(),
        'payment_method' => 'cash',
        'user_id' => $admin->id,
    ]);

    $txPaud = Transaction::create([
        'financial_unit_id' => $unitPaud->id,
        'fee_category_id' => $catPaud->id,
        'type' => 'income',
        'amount' => 80000,
        'payment_date' => now(),
        'payment_method' => 'cash',
        'user_id' => $admin->id,
    ]);

    $bendaharaBC = User::factory()->bendahara()->create([
        'managed_financial_unit_id' => $unitBC->id,
        'managed_level_id' => $levelB->id,
    ]);

    $this->actingAs($bendaharaBC);

    // Livewire transactions table should see txB and txC, but NOT txPaud
    Livewire::test('admin.financial.transactions')
        ->assertSee('SPP Paket B')
        ->assertSee('SPP Paket C')
        ->assertDontSee('SPP PAUD');

    // Livewire recap should also scope to Kas Paket B & C
    Livewire::test('admin.financial.recap')
        ->assertSee('SPP Paket B')
        ->assertSee('SPP Paket C')
        ->assertDontSee('SPP PAUD');
});
