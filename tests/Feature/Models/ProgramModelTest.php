<?php

use App\Models\Level;
use App\Models\Program;

describe('Program Model', function () {
    test('can create a program linked to a level', function () {
        $level = Level::factory()->create(['name' => 'PAUD']);

        $program = Program::create([
            'level_id' => $level->id,
            'name' => 'PAUD',
            'slug' => 'paud',
            'description' => 'Program Pendidikan Anak Usia Dini',
            'curriculum_overview' => 'Kurikulum PAUD yang komprehensif',
            'duration' => '2 tahun',
            'requirements' => 'Usia 4-6 tahun',
            'order' => 1,
            'is_active' => true,
        ]);

        expect($program)->toBeInstanceOf(Program::class)
            ->and($program->name)->toBe('PAUD')
            ->and($program->level_id)->toBe($level->id)
            ->and($program->is_active)->toBeTrue();
    });

    test('program belongs to a level', function () {
        $level = Level::factory()->create(['name' => 'Paket A']);
        $program = Program::factory()->forLevel($level)->create();

        expect($program->level)->toBeInstanceOf(Level::class)
            ->and($program->level->id)->toBe($level->id);
    });

    test('casts is_active to boolean', function () {
        $program = Program::factory()->create(['is_active' => 1]);

        expect($program->is_active)->toBeTrue()
            ->and($program->is_active)->toBeBool();
    });

    test('casts order to integer', function () {
        $program = Program::factory()->create(['order' => '5']);

        expect($program->order)->toBe(5)
            ->and($program->order)->toBeInt();
    });

    test('active scope filters active programs', function () {
        Program::factory()->create(['is_active' => true, 'name' => 'Active Program']);
        Program::factory()->create(['is_active' => false, 'name' => 'Inactive Program']);

        $activePrograms = Program::active()->get();

        expect($activePrograms)->toHaveCount(1)
            ->and($activePrograms->first()->name)->toBe('Active Program');
    });

    test('ordered scope orders programs by order field', function () {
        Program::factory()->create(['name' => 'Program C', 'order' => 3]);
        Program::factory()->create(['name' => 'Program A', 'order' => 1]);
        Program::factory()->create(['name' => 'Program B', 'order' => 2]);

        $programs = Program::ordered()->get();

        expect($programs)->toHaveCount(3)
            ->and($programs->get(0)->name)->toBe('Program A')
            ->and($programs->get(1)->name)->toBe('Program B')
            ->and($programs->get(2)->name)->toBe('Program C');
    });

    test('scopes can be combined', function () {
        Program::factory()->create(['name' => 'Active First', 'order' => 1, 'is_active' => true]);
        Program::factory()->create(['name' => 'Inactive Second', 'order' => 2, 'is_active' => false]);
        Program::factory()->create(['name' => 'Active Third', 'order' => 3, 'is_active' => true]);

        $programs = Program::active()->ordered()->get();

        expect($programs)->toHaveCount(2)
            ->and($programs->first()->name)->toBe('Active First')
            ->and($programs->last()->name)->toBe('Active Third');
    });

    test('slug must be unique', function () {
        Program::factory()->create(['slug' => 'unique-slug']);

        expect(fn () => Program::factory()->create(['slug' => 'unique-slug']))
            ->toThrow(Exception::class);
    });

    test('optional fields can be null', function () {
        $program = Program::factory()->create([
            'curriculum_overview' => null,
            'duration' => null,
            'requirements' => null,
            'image_path' => null,
        ]);

        expect($program->curriculum_overview)->toBeNull()
            ->and($program->duration)->toBeNull()
            ->and($program->requirements)->toBeNull()
            ->and($program->image_path)->toBeNull();
    });

    test('default order is 0', function () {
        $program = Program::create([
            'level_id' => Level::factory()->create()->id,
            'name' => 'Test Program',
            'slug' => 'test-program',
            'description' => 'Test program',
        ]);

        expect($program->order)->toBe(0);
    });

    test('default is_active is true', function () {
        $program = Program::create([
            'level_id' => Level::factory()->create()->id,
            'name' => 'Test Program',
            'slug' => 'test-program',
            'description' => 'Test program',
        ]);

        expect($program->is_active)->toBeTrue();
    });

    test('can create program with all fields', function () {
        $level = Level::factory()->create();

        $program = Program::create([
            'level_id' => $level->id,
            'name' => 'Complete Program',
            'slug' => 'complete-program',
            'description' => 'This is a complete program with all fields',
            'curriculum_overview' => 'Comprehensive curriculum overview',
            'duration' => '3 tahun',
            'requirements' => 'Lulus Paket B atau SMP',
            'image_path' => 'programs/complete-program.jpg',
            'order' => 10,
            'is_active' => true,
        ]);

        expect($program->name)->toBe('Complete Program')
            ->and($program->slug)->toBe('complete-program')
            ->and($program->level_id)->toBe($level->id)
            ->and($program->description)->toBe('This is a complete program with all fields')
            ->and($program->curriculum_overview)->toBe('Comprehensive curriculum overview')
            ->and($program->duration)->toBe('3 tahun')
            ->and($program->requirements)->toBe('Lulus Paket B atau SMP')
            ->and($program->image_path)->toBe('programs/complete-program.jpg')
            ->and($program->order)->toBe(10)
            ->and($program->is_active)->toBeTrue();
    });

    test('multiple programs can have same order value', function () {
        Program::factory()->create(['order' => 5]);
        Program::factory()->create(['order' => 5]);

        $programs = Program::where('order', 5)->get();

        expect($programs)->toHaveCount(2);
    });

    test('can update program fields', function () {
        $program = Program::factory()->create([
            'name' => 'Original Name',
            'is_active' => true,
        ]);

        $program->update([
            'name' => 'Updated Name',
            'description' => 'Updated description',
            'is_active' => false,
        ]);

        expect($program->name)->toBe('Updated Name')
            ->and($program->description)->toBe('Updated description')
            ->and($program->is_active)->toBeFalse();
    });

    test('can delete program', function () {
        $program = Program::factory()->create();

        $id = $program->id;
        $program->delete();

        expect(Program::find($id))->toBeNull();
    });

    test('level has one program relationship', function () {
        $level = Level::factory()->create();
        $program = Program::factory()->forLevel($level)->create();

        expect($level->program)->toBeInstanceOf(Program::class)
            ->and($level->program->id)->toBe($program->id);
    });
});
