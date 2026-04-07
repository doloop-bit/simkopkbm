<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\StudentBilling;
use App\Models\FeeCategory;
use App\Models\Student;
use App\Models\Level;
use App\Models\Classroom;
use Livewire\Livewire;
use Livewire\Volt\Volt;

beforeEach(function () {
    $this->user = User::factory()->create(['role' => 'admin']);
});

it('can select all arrears billings', function () {
    actingAs($this->user);

    $level = Level::factory()->create();
    $classroom = Classroom::factory()->create(['level_id' => $level->id]);
    $student = Student::factory()->create();
    $student->studentProfile()->create(['classroom_id' => $classroom->id]);
    
    $feeCategory = FeeCategory::factory()->create(['level_id' => $level->id]);
    
    $billing1 = StudentBilling::factory()->create([
        'student_id' => $student->id,
        'fee_category_id' => $feeCategory->id,
        'status' => 'unpaid',
        'amount' => 1000000,
        'paid_amount' => 0,
    ]);
    
    $billing2 = StudentBilling::factory()->create([
        'student_id' => $student->id,
        'fee_category_id' => $feeCategory->id,
        'status' => 'partial',
        'amount' => 500000,
        'paid_amount' => 200000,
    ]);

    $component = Volt::test('admin.reports')
        ->set('tab', 'arrears')
        ->set('selectAll', true);
    
    expect($component->get('selected_billings'))->toContain((string) $billing1->id, (string) $billing2->id);
});

it('can deselect all arrears billings', function () {
    actingAs($this->user);

    $level = Level::factory()->create();
    $classroom = Classroom::factory()->create(['level_id' => $level->id]);
    $student = Student::factory()->create();
    $student->studentProfile()->create(['classroom_id' => $classroom->id]);
    
    $feeCategory = FeeCategory::factory()->create(['level_id' => $level->id]);
    
    $billing = StudentBilling::factory()->create([
        'student_id' => $student->id,
        'fee_category_id' => $feeCategory->id,
        'status' => 'unpaid',
        'amount' => 1000000,
        'paid_amount' => 0,
    ]);

    $component = Volt::test('admin.reports')
        ->set('tab', 'arrears')
        ->set('selected_billings', [(string) $billing->id])
        ->set('selectAll', false);
    
    expect($component->get('selected_billings'))->toBe([]);
});

it('select all respects level filter', function () {
    actingAs($this->user);

    $level1 = Level::factory()->create();
    $level2 = Level::factory()->create();
    
    $classroom1 = Classroom::factory()->create(['level_id' => $level1->id]);
    $classroom2 = Classroom::factory()->create(['level_id' => $level2->id]);
    
    $student1 = Student::factory()->create();
    $student1->studentProfile()->create(['classroom_id' => $classroom1->id]);
    
    $student2 = Student::factory()->create();
    $student2->studentProfile()->create(['classroom_id' => $classroom2->id]);
    
    $feeCategory1 = FeeCategory::factory()->create(['level_id' => $level1->id]);
    $feeCategory2 = FeeCategory::factory()->create(['level_id' => $level2->id]);
    
    $billing1 = StudentBilling::factory()->create([
        'student_id' => $student1->id,
        'fee_category_id' => $feeCategory1->id,
        'status' => 'unpaid',
        'amount' => 1000000,
        'paid_amount' => 0,
    ]);
    
    $billing2 = StudentBilling::factory()->create([
        'student_id' => $student2->id,
        'fee_category_id' => $feeCategory2->id,
        'status' => 'unpaid',
        'amount' => 1000000,
        'paid_amount' => 0,
    ]);

    $component = Volt::test('admin.reports')
        ->set('tab', 'arrears')
        ->set('level_id', $level1->id)
        ->set('selectAll', true);
    
    expect($component->get('selected_billings'))->toContain((string) $billing1->id)
        ->and($component->get('selected_billings'))->not->toContain((string) $billing2->id);
});
