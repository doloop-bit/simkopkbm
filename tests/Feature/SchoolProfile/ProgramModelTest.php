<?php

use App\Models\Program;

describe('Program Model', function () {
    test('can create a program', function () {
        $program = Program::create([
            'name' => 'PAUD',
            'slug' => 'paud',
            'level' => 'paud',
            'description' => 'Program Pendidikan Anak Usia Dini',
            'curriculum_overview' => 'Kurikulum PAUD yang komprehensif',
            'duration' => '2 tahun',
            'requirements' => 'Usia 4-6 tahun',
            'order' => 1,
            'is_active' => true,
        ]);

        expect($program)->toBeInstanceOf(Program::class)
            ->and($program->name)->toBe('PAUD')
            ->and($program->level)->toBe('paud')
            ->and($program->is_active)->toBeTrue();
    });

    test('casts is_active to boolean', function () {
        $program = Program::create([
            'name' => 'Paket A',
            'slug' => 'paket-a',
            'level' => 'paket_a',
            'description' => 'Program Paket A',
            'is_active' => 1,
        ]);

        expect($program->is_active)->toBeTrue()
            ->and($program->is_active)->toBeBool();
    });

    test('casts order to integer', function () {
        $program = Program::create([
            'name' => 'Paket B',
            'slug' => 'paket-b',
            'level' => 'paket_b',
            'description' => 'Program Paket B',
            'order' => '5',
        ]);

        expect($program->order)->toBe(5)
            ->and($program->order)->toBeInt();
    });

    test('active scope filters active programs', function () {
        // Create active program
        Program::create([
            'name' => 'Active Program',
            'slug' => 'active-program',
            'level' => 'paud',
            'description' => 'This is an active program',
            'is_active' => true,
        ]);

        // Create inactive program
        Program::create([
            'name' => 'Inactive Program',
            'slug' => 'inactive-program',
            'level' => 'paket_a',
            'description' => 'This is an inactive program',
            'is_active' => false,
        ]);

        $activePrograms = Program::active()->get();

        expect($activePrograms)->toHaveCount(1)
            ->and($activePrograms->first()->name)->toBe('Active Program');
    });

    test('ordered scope orders programs by order field', function () {
        Program::create([
            'name' => 'Program C',
            'slug' => 'program-c',
            'level' => 'paket_c',
            'description' => 'Third program',
            'order' => 3,
        ]);

        Program::create([
            'name' => 'Program A',
            'slug' => 'program-a',
            'level' => 'paud',
            'description' => 'First program',
            'order' => 1,
        ]);

        Program::create([
            'name' => 'Program B',
            'slug' => 'program-b',
            'level' => 'paket_a',
            'description' => 'Second program',
            'order' => 2,
        ]);

        $programs = Program::ordered()->get();

        expect($programs)->toHaveCount(3)
            ->and($programs->get(0)->name)->toBe('Program A')
            ->and($programs->get(1)->name)->toBe('Program B')
            ->and($programs->get(2)->name)->toBe('Program C');
    });

    test('scopes can be combined', function () {
        // Active program with order 1
        Program::create([
            'name' => 'Active First',
            'slug' => 'active-first',
            'level' => 'paud',
            'description' => 'Active program first',
            'order' => 1,
            'is_active' => true,
        ]);

        // Inactive program with order 2
        Program::create([
            'name' => 'Inactive Second',
            'slug' => 'inactive-second',
            'level' => 'paket_a',
            'description' => 'Inactive program second',
            'order' => 2,
            'is_active' => false,
        ]);

        // Active program with order 3
        Program::create([
            'name' => 'Active Third',
            'slug' => 'active-third',
            'level' => 'paket_b',
            'description' => 'Active program third',
            'order' => 3,
            'is_active' => true,
        ]);

        $programs = Program::active()->ordered()->get();

        expect($programs)->toHaveCount(2)
            ->and($programs->first()->name)->toBe('Active First')
            ->and($programs->last()->name)->toBe('Active Third');
    });

    test('slug must be unique', function () {
        Program::create([
            'name' => 'Program 1',
            'slug' => 'unique-slug',
            'level' => 'paud',
            'description' => 'First program',
        ]);

        expect(fn () => Program::create([
            'name' => 'Program 2',
            'slug' => 'unique-slug',
            'level' => 'paket_a',
            'description' => 'Second program',
        ]))->toThrow(Exception::class);
    });

    test('level accepts valid enum values', function () {
        $levels = ['paud', 'paket_a', 'paket_b', 'paket_c'];

        foreach ($levels as $index => $level) {
            $program = Program::create([
                'name' => "Program {$level}",
                'slug' => "program-{$level}",
                'level' => $level,
                'description' => "Program for {$level}",
                'order' => $index + 1,
            ]);

            expect($program->level)->toBe($level);
        }

        expect(Program::count())->toBe(4);
    });

    test('optional fields can be null', function () {
        $program = Program::create([
            'name' => 'Minimal Program',
            'slug' => 'minimal-program',
            'level' => 'paud',
            'description' => 'Program with minimal fields',
        ]);

        expect($program->curriculum_overview)->toBeNull()
            ->and($program->duration)->toBeNull()
            ->and($program->requirements)->toBeNull()
            ->and($program->image_path)->toBeNull();
    });

    test('default order is 0', function () {
        $program = Program::create([
            'name' => 'Test Program',
            'slug' => 'test-program',
            'level' => 'paud',
            'description' => 'Test program',
        ]);

        expect($program->order)->toBe(0);
    });

    test('default is_active is true', function () {
        $program = Program::create([
            'name' => 'Test Program',
            'slug' => 'test-program',
            'level' => 'paud',
            'description' => 'Test program',
        ]);

        expect($program->is_active)->toBeTrue();
    });

    test('can create program with all fields', function () {
        $program = Program::create([
            'name' => 'Complete Program',
            'slug' => 'complete-program',
            'level' => 'paket_c',
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
            ->and($program->level)->toBe('paket_c')
            ->and($program->description)->toBe('This is a complete program with all fields')
            ->and($program->curriculum_overview)->toBe('Comprehensive curriculum overview')
            ->and($program->duration)->toBe('3 tahun')
            ->and($program->requirements)->toBe('Lulus Paket B atau SMP')
            ->and($program->image_path)->toBe('programs/complete-program.jpg')
            ->and($program->order)->toBe(10)
            ->and($program->is_active)->toBeTrue();
    });

    test('multiple programs can have same order value', function () {
        Program::create([
            'name' => 'Program 1',
            'slug' => 'program-1',
            'level' => 'paud',
            'description' => 'First program',
            'order' => 5,
        ]);

        Program::create([
            'name' => 'Program 2',
            'slug' => 'program-2',
            'level' => 'paket_a',
            'description' => 'Second program',
            'order' => 5,
        ]);

        $programs = Program::where('order', 5)->get();

        expect($programs)->toHaveCount(2);
    });

    test('can update program fields', function () {
        $program = Program::create([
            'name' => 'Original Name',
            'slug' => 'original-slug',
            'level' => 'paud',
            'description' => 'Original description',
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
        $program = Program::create([
            'name' => 'To Be Deleted',
            'slug' => 'to-be-deleted',
            'level' => 'paud',
            'description' => 'This program will be deleted',
        ]);

        $id = $program->id;
        $program->delete();

        expect(Program::find($id))->toBeNull();
    });

    test('ordered scope maintains stable sort', function () {
        // Create programs with same order
        $program1 = Program::create([
            'name' => 'Program 1',
            'slug' => 'program-1',
            'level' => 'paud',
            'description' => 'First program',
            'order' => 1,
        ]);

        $program2 = Program::create([
            'name' => 'Program 2',
            'slug' => 'program-2',
            'level' => 'paket_a',
            'description' => 'Second program',
            'order' => 1,
        ]);

        $programs = Program::ordered()->get();

        // Both should be returned, order between them is stable
        expect($programs)->toHaveCount(2)
            ->and($programs->pluck('order')->unique())->toHaveCount(1)
            ->and($programs->pluck('order')->first())->toBe(1);
    });

    test('active scope does not affect ordering', function () {
        Program::create([
            'name' => 'Active C',
            'slug' => 'active-c',
            'level' => 'paket_c',
            'description' => 'Third active program',
            'order' => 3,
            'is_active' => true,
        ]);

        Program::create([
            'name' => 'Active A',
            'slug' => 'active-a',
            'level' => 'paud',
            'description' => 'First active program',
            'order' => 1,
            'is_active' => true,
        ]);

        Program::create([
            'name' => 'Inactive B',
            'slug' => 'inactive-b',
            'level' => 'paket_a',
            'description' => 'Second inactive program',
            'order' => 2,
            'is_active' => false,
        ]);

        $programs = Program::active()->ordered()->get();

        expect($programs)->toHaveCount(2)
            ->and($programs->first()->name)->toBe('Active A')
            ->and($programs->last()->name)->toBe('Active C');
    });

    test('can filter by level', function () {
        Program::create([
            'name' => 'PAUD Program',
            'slug' => 'paud-program',
            'level' => 'paud',
            'description' => 'PAUD program',
        ]);

        Program::create([
            'name' => 'Paket A Program',
            'slug' => 'paket-a-program',
            'level' => 'paket_a',
            'description' => 'Paket A program',
        ]);

        Program::create([
            'name' => 'Another PAUD',
            'slug' => 'another-paud',
            'level' => 'paud',
            'description' => 'Another PAUD program',
        ]);

        $paudPrograms = Program::where('level', 'paud')->get();

        expect($paudPrograms)->toHaveCount(2)
            ->and($paudPrograms->every(fn ($p) => $p->level === 'paud'))->toBeTrue();
    });
});
