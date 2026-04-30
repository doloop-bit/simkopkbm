<?php

use App\Models\AcademicYear;
use App\Models\FeeCategory;
use App\Models\StudentBilling;
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

it('can delete a single billing and reset once-off discount', function () {
    $discount = StudentFeeDiscount::factory()->create([
        'student_id' => $this->student->id,
        'fee_category_id' => $this->category->id,
        'frequency' => 'once',
        'is_applied' => true,
    ]);

    $billing = StudentBilling::create([
        'student_id' => $this->student->id,
        'fee_category_id' => $this->category->id,
        'academic_year_id' => $this->academicYear->id,
        'month' => '2026-01',
        'amount' => 90000,
        'status' => 'unpaid',
    ]);

    Livewire::actingAs($this->admin)
        ->test('admin.financial.billings')
        ->call('deleteBilling', $billing->id);

    expect(StudentBilling::find($billing->id))->toBeNull();
    expect($discount->fresh()->is_applied)->toBeFalse();
});

it('can bulk delete billings and reset once-off discounts', function () {
    $discount = StudentFeeDiscount::factory()->create([
        'student_id' => $this->student->id,
        'fee_category_id' => $this->category->id,
        'frequency' => 'once',
        'is_applied' => true,
    ]);

    $billing = StudentBilling::create([
        'student_id' => $this->student->id,
        'fee_category_id' => $this->category->id,
        'academic_year_id' => $this->academicYear->id,
        'month' => '2026-01',
        'amount' => 90000,
        'status' => 'unpaid',
    ]);

    Livewire::actingAs($this->admin)
        ->test('admin.financial.billings')
        ->set('classroom_id', $this->classroom->id)
        ->set('fee_category_id', $this->category->id)
        ->set('month', '2026-01')
        ->call('bulkDelete');

    expect(StudentBilling::count())->toBe(0);
    expect($discount->fresh()->is_applied)->toBeFalse();
});
