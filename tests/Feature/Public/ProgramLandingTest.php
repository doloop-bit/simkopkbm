<?php

declare(strict_types=1);

use App\Models\Level;
use App\Models\Program;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
});

test('program landing page loads by slug at root level', function () {
    $level = Level::factory()->create(['education_level' => 'paud']);
    $program = Program::factory()->create([
        'level_id' => $level->id,
        'slug' => 'paud-ceria',
        'is_active' => true,
    ]);

    $response = $this->get('/paud-ceria');

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Public/Landing/paud', false)
        ->where('programName', $program->name)
    );
});

test('program with different education level uses different template', function () {
    $level = Level::factory()->create(['education_level' => 'sd']);
    $program = Program::factory()->create([
        'level_id' => $level->id,
        'slug' => 'sd-unggul',
        'is_active' => true,
    ]);

    $response = $this->get('/sd-unggul');

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Public/Landing/paketa', false)
        ->where('programName', $program->name)
    );
});

test('program with no specific template uses default template', function () {
    $level = Level::factory()->create(['education_level' => 'smp']);
    $program = Program::factory()->create([
        'level_id' => $level->id,
        'slug' => 'smp-hebat',
        'is_active' => true,
    ]);

    $response = $this->get('/smp-hebat');

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Public/ProgramsShow', false)
        ->has('program')
    );
});

test('changing slug updates the landing page URL', function () {
    $level = Level::factory()->create(['education_level' => 'paud']);
    $program = Program::factory()->create([
        'level_id' => $level->id,
        'slug' => 'paud-old',
        'is_active' => true,
    ]);

    $this->get('/paud-old')->assertSuccessful();

    $program->update(['slug' => 'paud-new']);

    $this->get('/paud-old')->assertNotFound();
    $this->get('/paud-new')->assertSuccessful();
});
