<?php

use App\Models\AcademicYear;
use App\Models\FeeCategory;
use App\Models\StudentFeeDiscount;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->academicYear = AcademicYear::factory()->create(['is_active' => true]);
    $this->category = FeeCategory::factory()->create([
        'name' => 'SPP',
        'default_amount' => 100000,
        'billing_type' => 'monthly',
    ]);
    $this->student = User::factory()->create(['role' => 'siswa']);
    $this->classroom = \App\Models\Classroom::factory()->create([
        'academic_year_id' => $this->academicYear->id,
    ]);

    \App\Models\Profile::factory()->create([
        'user_id' => $this->student->id,
        'profileable_type' => \App\Models\StudentProfile::class,
        'profileable_id' => \App\Models\StudentProfile::factory()->create([
            'classroom_id' => $this->classroom->id,
        ])->id,
    ]);
});

it('applies a recurring discount multiple times', function () {
    $discount = StudentFeeDiscount::factory()->create([
        'student_id' => $this->student->id,
        'fee_category_id' => $this->category->id,
        'amount' => 10000,
        'discount_type' => 'fixed',
        'frequency' => 'recurring',
    ]);

    Livewire::actingAs($this->admin)
        ->test('admin.financial.billings')
        ->set('fee_category_id', $this->category->id)
        ->set('academic_year_id', $this->academicYear->id)
        ->set('classroom_id', $this->classroom->id)
        ->set('amount', 100000)
        ->set('month', '2026-01')
        ->call('generateBillings');

    expect($this->student->billings()->count())->toBe(1);
    expect($this->student->billings()->first()->amount)->toEqual(90000.0);
    expect($discount->fresh()->is_applied)->toBeFalse();

    // Second month
    Livewire::actingAs($this->admin)
        ->test('admin.financial.billings')
        ->set('fee_category_id', $this->category->id)
        ->set('academic_year_id', $this->academicYear->id)
        ->set('classroom_id', $this->classroom->id)
        ->set('amount', 100000)
        ->set('month', '2026-02')
        ->call('generateBillings');

    expect($this->student->billings()->count())->toBe(2);
    expect($this->student->billings()->latest('id')->first()->amount)->toEqual(90000.0);
});

it('applies a once-off discount only once', function () {
    $discount = StudentFeeDiscount::factory()->create([
        'student_id' => $this->student->id,
        'fee_category_id' => $this->category->id,
        'amount' => 10000,
        'discount_type' => 'fixed',
        'frequency' => 'once',
        'is_applied' => false,
    ]);

    Livewire::actingAs($this->admin)
        ->test('admin.financial.billings')
        ->set('fee_category_id', $this->category->id)
        ->set('academic_year_id', $this->academicYear->id)
        ->set('classroom_id', $this->classroom->id)
        ->set('amount', 100000)
        ->set('month', '2026-01')
        ->call('generateBillings');

    expect($this->student->billings()->count())->toBe(1);
    expect($this->student->billings()->first()->amount)->toEqual(90000.0);
    expect($discount->fresh()->is_applied)->toBeTrue();

    // Second month - discount should NOT be applied
    Livewire::actingAs($this->admin)
        ->test('admin.financial.billings')
        ->set('fee_category_id', $this->category->id)
        ->set('academic_year_id', $this->academicYear->id)
        ->set('classroom_id', $this->classroom->id)
        ->set('amount', 100000)
        ->set('month', '2026-02')
        ->call('generateBillings');

    expect($this->student->billings()->count())->toBe(2);
    expect($this->student->billings()->latest('id')->first()->amount)->toEqual(100000.0);
});
