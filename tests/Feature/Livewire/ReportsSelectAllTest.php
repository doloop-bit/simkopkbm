<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\StudentBilling;
use App\Models\FeeCategory;
use App\Models\Profile;
use App\Models\StudentProfile;
use App\Models\Level;
use App\Models\Classroom;
use Livewire\Livewire;
use Livewire\Volt\Volt;

beforeEach(function () {
    $this->user = User::factory()->create(['role' => 'admin']);
});

it('can select all arrears billings', function () {
    $this->actingAs($this->user);

    $level = Level::factory()->create();
    $classroom = Classroom::factory()->create(['level_id' => $level->id]);
    $student = User::factory()->create(['role' => 'student']);
    $studentProfile = StudentProfile::factory()->create(['classroom_id' => $classroom->id]);
    Profile::create([
        'user_id' => $student->id,
        'profileable_id' => $studentProfile->id,
        'profileable_type' => StudentProfile::class,
    ]);
    
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

    $component = Livewire::test('admin.reports')
        ->set('tab', 'arrears')
        ->set('selectAll', true);
    
    expect($component->get('selected_billings'))->toContain((string) $billing1->id, (string) $billing2->id);
});

it('can deselect all arrears billings', function () {
    $this->actingAs($this->user);

    $level = Level::factory()->create();
    $classroom = Classroom::factory()->create(['level_id' => $level->id]);
    $student = User::factory()->create(['role' => 'student']);
    $studentProfile = StudentProfile::factory()->create(['classroom_id' => $classroom->id]);
    Profile::create([
        'user_id' => $student->id,
        'profileable_id' => $studentProfile->id,
        'profileable_type' => StudentProfile::class,
    ]);
    
    $feeCategory = FeeCategory::factory()->create(['level_id' => $level->id]);
    
    $billing = StudentBilling::factory()->create([
        'student_id' => $student->id,
        'fee_category_id' => $feeCategory->id,
        'status' => 'unpaid',
        'amount' => 1000000,
        'paid_amount' => 0,
    ]);

    $component = Livewire::test('admin.reports')
        ->set('tab', 'arrears')
        ->set('selected_billings', [(string) $billing->id])
        ->set('selectAll', false);
    
    expect($component->get('selected_billings'))->toBe([]);
});

it('select all respects level filter', function () {
    $this->actingAs($this->user);

    $level1 = Level::factory()->create();
    $level2 = Level::factory()->create();
    
    $classroom1 = Classroom::factory()->create(['level_id' => $level1->id]);
    $classroom2 = Classroom::factory()->create(['level_id' => $level2->id]);
    
    $student1 = User::factory()->create(['role' => 'student']);
    $studentProfile1 = StudentProfile::factory()->create(['classroom_id' => $classroom1->id]);
    Profile::create([
        'user_id' => $student1->id,
        'profileable_id' => $studentProfile1->id,
        'profileable_type' => StudentProfile::class,
    ]);
    
    $student2 = User::factory()->create(['role' => 'student']);
    $studentProfile2 = StudentProfile::factory()->create(['classroom_id' => $classroom2->id]);
    Profile::create([
        'user_id' => $student2->id,
        'profileable_id' => $studentProfile2->id,
        'profileable_type' => StudentProfile::class,
    ]);
    
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

    $component = Livewire::test('admin.reports')
        ->set('tab', 'arrears')
        ->set('level_id', $level1->id)
        ->set('selectAll', true);
    
    expect($component->get('selected_billings'))->toContain((string) $billing1->id)
        ->and($component->get('selected_billings'))->not->toContain((string) $billing2->id);
});
