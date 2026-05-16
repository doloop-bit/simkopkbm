<?php

use App\Models\AcademicYear;
use App\Models\CalendarEvent;
use App\Models\Level;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\withoutVite;

beforeEach(fn () => withoutVite());

test('authenticated user can access calendar page', function () {
    $user = User::factory()->admin()->create();

    actingAs($user)
        ->get(route('calendar.index'))
        ->assertOk()
        ->assertSeeLivewire('admin.academic.calendar');
});

test('guest cannot access calendar page', function () {
    $this->get(route('calendar.index'))
        ->assertRedirect(route('login'));
});

test('admin can create a calendar event', function () {
    $user = User::factory()->admin()->create();
    $year = AcademicYear::factory()->create();
    $level = Level::factory()->create();

    Livewire\Livewire::actingAs($user)
        ->test('admin.academic.calendar')
        ->set('academicYearId', $year->id)
        ->call('createNew')
        ->assertSet('eventModal', true)
        ->set('title', 'Rapat Guru Paket A')
        ->set('type', 'rapat_jenjang')
        ->set('scope', 'level')
        ->set('levelId', $level->id)
        ->set('startDate', now()->addDays(5)->format('Y-m-d'))
        ->set('location', 'Ruang Guru')
        ->call('save')
        ->assertSet('eventModal', false)
        ->assertHasNoErrors();

    $this->assertDatabaseHas('calendar_events', [
        'title' => 'Rapat Guru Paket A',
        'type' => 'rapat_jenjang',
        'scope' => 'level',
        'level_id' => $level->id,
    ]);
});

test('admin can create a pkbm-wide event', function () {
    $user = User::factory()->admin()->create();
    $year = AcademicYear::factory()->create();

    Livewire\Livewire::actingAs($user)
        ->test('admin.academic.calendar')
        ->set('academicYearId', $year->id)
        ->call('createNew')
        ->set('title', 'Rapat Pleno PKBM')
        ->set('type', 'rapat_gabungan')
        ->set('startDate', now()->addDays(10)->format('Y-m-d'))
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('calendar_events', [
        'title' => 'Rapat Pleno PKBM',
        'type' => 'rapat_gabungan',
        'scope' => 'pkbm',
        'level_id' => null,
    ]);
});

test('type auto-scope sets scope correctly', function () {
    $user = User::factory()->admin()->create();
    $year = AcademicYear::factory()->create();

    Livewire\Livewire::actingAs($user)
        ->test('admin.academic.calendar')
        ->set('academicYearId', $year->id)
        ->call('createNew')
        ->set('type', 'rapat_gabungan')
        ->assertSet('scope', 'pkbm')
        ->set('type', 'rapat_yayasan')
        ->assertSet('scope', 'yayasan')
        ->set('type', 'rapat_jenjang')
        ->assertSet('scope', 'level');
});

test('admin can edit a calendar event', function () {
    $user = User::factory()->admin()->create();
    $year = AcademicYear::factory()->create();
    $event = CalendarEvent::factory()->rapatGabungan()->create([
        'academic_year_id' => $year->id,
        'title' => 'Original Title',
        'created_by' => $user->id,
    ]);

    Livewire\Livewire::actingAs($user)
        ->test('admin.academic.calendar')
        ->set('academicYearId', $year->id)
        ->call('edit', $event->id)
        ->assertSet('eventModal', true)
        ->assertSet('title', 'Original Title')
        ->set('title', 'Updated Title')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('calendar_events', [
        'id' => $event->id,
        'title' => 'Updated Title',
    ]);
});

test('admin can delete a calendar event', function () {
    $user = User::factory()->admin()->create();
    $year = AcademicYear::factory()->create();
    $event = CalendarEvent::factory()->rapatGabungan()->create([
        'academic_year_id' => $year->id,
        'created_by' => $user->id,
    ]);

    Livewire\Livewire::actingAs($user)
        ->test('admin.academic.calendar')
        ->set('academicYearId', $year->id)
        ->call('delete', $event->id);

    $this->assertDatabaseMissing('calendar_events', [
        'id' => $event->id,
    ]);
});

test('validation requires title and start date', function () {
    $user = User::factory()->admin()->create();
    $year = AcademicYear::factory()->create();

    Livewire\Livewire::actingAs($user)
        ->test('admin.academic.calendar')
        ->set('academicYearId', $year->id)
        ->call('createNew')
        ->set('title', '')
        ->set('startDate', '')
        ->call('save')
        ->assertHasErrors(['title', 'startDate']);
});

test('level-scoped event requires level_id', function () {
    $user = User::factory()->admin()->create();
    $year = AcademicYear::factory()->create();

    Livewire\Livewire::actingAs($user)
        ->test('admin.academic.calendar')
        ->set('academicYearId', $year->id)
        ->call('createNew')
        ->set('title', 'Test Event')
        ->set('type', 'rapat_jenjang')
        ->set('scope', 'level')
        ->set('levelId', null)
        ->set('startDate', now()->addDay()->format('Y-m-d'))
        ->call('save')
        ->assertHasErrors(['levelId']);
});

test('calendar event model scopes work correctly', function () {
    $year = AcademicYear::factory()->create();
    $level = Level::factory()->create();

    $levelEvent = CalendarEvent::factory()->rapatJenjang()->create([
        'academic_year_id' => $year->id,
        'level_id' => $level->id,
        'start_date' => now(),
    ]);

    $pkbmEvent = CalendarEvent::factory()->rapatGabungan()->create([
        'academic_year_id' => $year->id,
        'start_date' => now(),
    ]);

    $yayasanEvent = CalendarEvent::factory()->rapatYayasan()->create([
        'academic_year_id' => $year->id,
        'start_date' => now(),
    ]);

    $results = CalendarEvent::forLevel($level->id)->get();

    expect($results)->toHaveCount(3)
        ->and($results->pluck('id'))->toContain($levelEvent->id, $pkbmEvent->id, $yayasanEvent->id);
});

test('date range scope returns correct events', function () {
    $year = AcademicYear::factory()->create();
    $today = now()->format('Y-m-d');
    $nextWeek = now()->addWeek()->format('Y-m-d');
    $nextMonth = now()->addMonth()->format('Y-m-d');

    $inRange = CalendarEvent::factory()->rapatGabungan()->create([
        'academic_year_id' => $year->id,
        'start_date' => now()->addDays(3),
    ]);

    $outOfRange = CalendarEvent::factory()->rapatGabungan()->create([
        'academic_year_id' => $year->id,
        'start_date' => now()->addMonths(2),
    ]);

    $results = CalendarEvent::inDateRange($today, $nextWeek)->get();

    expect($results->pluck('id'))->toContain($inRange->id)
        ->and($results->pluck('id'))->not->toContain($outOfRange->id);
});
