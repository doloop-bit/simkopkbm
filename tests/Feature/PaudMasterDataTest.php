<?php

use App\Models\PaudCpElement;
use App\Models\PaudSklItem;
use App\Models\PaudTp;
use App\Models\User;
use Livewire\Livewire;

// Import removed since we're using Volt named component in testing

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('unauthorized users cannot access master data page', function () {
    $user = User::factory()->create(['role' => 'siswa']);
    $this->actingAs($user);

    Livewire::test('admin.report-card.paud.paud-master-data')
        ->assertStatus(403);
});

test('admin can view, create, edit and delete CP Elements and SKL Items', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    // Initial state
    expect(PaudCpElement::count())->toBe(0);
    expect(PaudSklItem::count())->toBe(0);

    // Test creating CP Element
    Livewire::test('admin.report-card.paud.paud-master-data')
        ->set('cpName', 'Nilai Agama')
        ->set('cpCode', 'agama')
        ->set('cpDescription', 'Description')
        ->set('cpOrder', 1)
        ->call('saveCp')
        ->assertHasNoErrors()
        ->assertSee('Elemen CP berhasil ditambahkan');

    expect(PaudCpElement::count())->toBe(1);
    $cp = PaudCpElement::first();
    expect($cp->name)->toBe('Nilai Agama');
    expect($cp->code)->toBe('agama');

    // Test editing CP Element
    Livewire::test('admin.report-card.paud.paud-master-data')
        ->call('editCp', $cp->id)
        ->assertSet('cpName', 'Nilai Agama')
        ->set('cpName', 'Nilai Agama Updated')
        ->call('saveCp')
        ->assertHasNoErrors();

    expect($cp->fresh()->name)->toBe('Nilai Agama Updated');

    // Test creating SKL Item
    Livewire::test('admin.report-card.paud.paud-master-data')
        ->set('activeTab', 'skl')
        ->set('sklName', 'Keimanan')
        ->set('sklCode', 'keimanan')
        ->set('sklDescription', 'Description')
        ->set('sklOrder', 1)
        ->call('saveSkl')
        ->assertHasNoErrors()
        ->assertSee('Item SKL berhasil ditambahkan');

    expect(PaudSklItem::count())->toBe(1);
    $skl = PaudSklItem::first();
    expect($skl->name)->toBe('Keimanan');

    // Test deleting CP Element
    Livewire::test('admin.report-card.paud.paud-master-data')
        ->call('deleteCp', $cp->id)
        ->assertSee('Elemen CP berhasil dihapus');

    expect(PaudCpElement::count())->toBe(0);

    // Test deleting SKL Item
    Livewire::test('admin.report-card.paud.paud-master-data')
        ->call('deleteSkl', $skl->id)
        ->assertSee('Item SKL berhasil dihapus');

    expect(PaudSklItem::count())->toBe(0);
});

test('cannot delete CP element or SKL item with related TP', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    $cp = PaudCpElement::factory()->create();
    $skl = PaudSklItem::factory()->create();
    $tp = PaudTp::factory()->create([
        'paud_cp_element_id' => $cp->id,
        'paud_skl_item_id' => $skl->id,
    ]);

    Livewire::test('admin.report-card.paud.paud-master-data')
        ->call('deleteCp', $cp->id)
        ->assertSee('tidak dapat dihapus karena memiliki TP terkait');

    expect(PaudCpElement::count())->toBe(1);

    Livewire::test('admin.report-card.paud.paud-master-data')
        ->call('deleteSkl', $skl->id)
        ->assertSee('tidak dapat dihapus karena memiliki TP terkait');

    expect(PaudSklItem::count())->toBe(1);
});
