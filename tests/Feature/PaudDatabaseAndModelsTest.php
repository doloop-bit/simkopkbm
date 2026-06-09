<?php

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\PaudCpElement;
use App\Models\PaudReportCard;
use App\Models\PaudSklItem;
use App\Models\PaudTp;
use App\Models\PaudTpAssessment;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('database seeds PAUD CP elements and SKL items correctly', function () {
    $this->seed(\Database\Seeders\PaudCpElementSeeder::class);
    $this->seed(\Database\Seeders\PaudSklItemSeeder::class);

    expect(PaudCpElement::count())->toBe(3);
    expect(PaudSklItem::count())->toBe(8);

    expect(PaudCpElement::where('code', 'agama')->exists())->toBeTrue();
    expect(PaudSklItem::where('code', 'keimanan')->exists())->toBeTrue();
});

test('can create PAUD models using factories and relationships work', function () {
    $cp = PaudCpElement::factory()->create(['name' => 'CP Test', 'code' => 'cp-test']);
    $skl = PaudSklItem::factory()->create(['name' => 'SKL Test', 'code' => 'skl-test']);
    $classroom = Classroom::factory()->create();
    $academicYear = AcademicYear::factory()->create();

    $tp = PaudTp::factory()->create([
        'paud_cp_element_id' => $cp->id,
        'paud_skl_item_id' => $skl->id,
        'classroom_id' => $classroom->id,
        'academic_year_id' => $academicYear->id,
        'semester' => '1',
        'code' => 'TP-1',
        'description' => 'Test TP description',
    ]);

    expect($tp->cpElement->name)->toBe('CP Test');
    expect($tp->sklItem->name)->toBe('SKL Test');
    expect($tp->classroom->id)->toBe($classroom->id);
    expect($tp->academicYear->id)->toBe($academicYear->id);

    $student = User::factory()->create();
    $assessor = User::factory()->create();

    $assessment = PaudTpAssessment::factory()->create([
        'paud_tp_id' => $tp->id,
        'student_id' => $student->id,
        'level' => 'BSB',
        'notes' => 'Sangat berkembang dengan baik.',
        'photos' => ['photo1.jpg', 'photo2.jpg'],
        'assessed_by' => $assessor->id,
    ]);

    expect($assessment->tp->id)->toBe($tp->id);
    expect($assessment->student->id)->toBe($student->id);
    expect($assessment->assessor->id)->toBe($assessor->id);
    expect($assessment->photos)->toBe(['photo1.jpg', 'photo2.jpg']);

    $reportCard = PaudReportCard::factory()->create([
        'student_id' => $student->id,
        'classroom_id' => $classroom->id,
        'academic_year_id' => $academicYear->id,
        'semester' => '1',
        'display_mode' => 'cp',
    ]);

    expect($reportCard->student->id)->toBe($student->id);
    expect($reportCard->classroom->id)->toBe($classroom->id);
    expect($reportCard->academicYear->id)->toBe($academicYear->id);
});
