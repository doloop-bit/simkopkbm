<?php

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\PaudCpElement;
use App\Models\PaudSklItem;
use App\Models\PaudTp;
use App\Models\PaudTpAssessment;
use App\Models\User;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('unauthorized users cannot access PAUD TP management', function () {
    $user = User::factory()->create(['role' => 'siswa']);
    $this->actingAs($user);

    Livewire::test('admin.report-card.paud.paud-tp-management')
        ->assertStatus(403);
});

test('admin can manage PAUD TPs', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    $cp = PaudCpElement::factory()->create();
    $skl = PaudSklItem::factory()->create();
    $classroom = Classroom::factory()->create();
    $academicYear = AcademicYear::factory()->create();

    // Verify initial count
    expect(PaudTp::count())->toBe(0);

    // Load component and create TP
    Livewire::test('admin.report-card.paud.paud-tp-management')
        ->set('academic_year_id', $academicYear->id)
        ->set('classroom_id', $classroom->id)
        ->set('semester', '1')
        ->call('openCreateModal', $cp->id)
        ->set('code', 'TP-TEST')
        ->set('description', 'Test Description')
        ->set('paud_skl_item_id', $skl->id)
        ->set('order', 1)
        ->call('saveTp')
        ->assertHasNoErrors()
        ->assertSee('Tujuan Pembelajaran berhasil ditambahkan');

    expect(PaudTp::count())->toBe(1);
    $tp = PaudTp::first();
    expect($tp->code)->toBe('TP-TEST');
    expect($tp->description)->toBe('Test Description');
    expect($tp->paud_skl_item_id)->toBe($skl->id);

    // Edit TP
    Livewire::test('admin.report-card.paud.paud-tp-management')
        ->set('academic_year_id', $academicYear->id)
        ->set('classroom_id', $classroom->id)
        ->set('semester', '1')
        ->call('editTp', $tp->id)
        ->assertSet('code', 'TP-TEST')
        ->set('description', 'Updated Description')
        ->call('saveTp')
        ->assertHasNoErrors();

    expect($tp->fresh()->description)->toBe('Updated Description');

    // Delete TP
    Livewire::test('admin.report-card.paud.paud-tp-management')
        ->set('academic_year_id', $academicYear->id)
        ->set('classroom_id', $classroom->id)
        ->set('semester', '1')
        ->call('deleteTp', $tp->id)
        ->assertSee('Tujuan Pembelajaran berhasil dihapus');

    expect(PaudTp::count())->toBe(0);
});

test('cannot delete TP with student assessments', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    $tp = PaudTp::factory()->create();
    $assessment = PaudTpAssessment::factory()->create([
        'paud_tp_id' => $tp->id,
    ]);

    Livewire::test('admin.report-card.paud.paud-tp-management')
        ->set('academic_year_id', $tp->academic_year_id)
        ->set('classroom_id', $tp->classroom_id)
        ->set('semester', $tp->semester)
        ->call('deleteTp', $tp->id)
        ->assertSee('tidak dapat dihapus karena sudah ada nilai siswa terkait');

    expect(PaudTp::count())->toBe(1);
});
