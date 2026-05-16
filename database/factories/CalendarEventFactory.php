<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\CalendarEvent;
use App\Models\Level;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CalendarEvent>
 */
class CalendarEventFactory extends Factory
{
    protected $model = CalendarEvent::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(array_keys(CalendarEvent::TYPE_LABELS));
        $scope = CalendarEvent::AUTO_SCOPE_TYPES[$type] ?? fake()->randomElement(['level', 'pkbm', 'yayasan']);
        $isAllDay = fake()->boolean(40);
        $startDate = fake()->dateTimeBetween('now', '+6 months');

        return [
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'type' => $type,
            'scope' => $scope,
            'level_id' => $scope === 'level' ? Level::inRandomOrder()->first()?->id : null,
            'academic_year_id' => AcademicYear::where('is_active', true)->first()?->id
                ?? AcademicYear::factory(),
            'start_date' => $startDate,
            'end_date' => fake()->optional(0.3)->dateTimeBetween($startDate, (clone $startDate)->modify('+5 days')),
            'start_time' => $isAllDay ? null : fake()->time('H:i'),
            'end_time' => $isAllDay ? null : fake()->time('H:i'),
            'location' => fake()->optional()->randomElement([
                'Ruang Rapat Utama',
                'Aula PKBM',
                'Ruang Guru',
                'Lab Komputer',
                'Kantor Yayasan',
                'Online (Zoom)',
            ]),
            'color' => null,
            'is_all_day' => $isAllDay,
            'recurrence_type' => 'none',
            'recurrence_config' => null,
            'recurrence_end_date' => null,
            'parent_event_id' => null,
            'attachment' => null,
            'created_by' => User::where('role', 'admin')->first()?->id,
        ];
    }

    /**
     * State: rapat jenjang.
     */
    public function rapatJenjang(): static
    {
        return $this->state(fn () => [
            'type' => 'rapat_jenjang',
            'scope' => 'level',
            'level_id' => Level::inRandomOrder()->first()?->id,
        ]);
    }

    /**
     * State: rapat gabungan (PKBM-wide).
     */
    public function rapatGabungan(): static
    {
        return $this->state(fn () => [
            'type' => 'rapat_gabungan',
            'scope' => 'pkbm',
            'level_id' => null,
        ]);
    }

    /**
     * State: rapat yayasan.
     */
    public function rapatYayasan(): static
    {
        return $this->state(fn () => [
            'type' => 'rapat_yayasan',
            'scope' => 'yayasan',
            'level_id' => null,
        ]);
    }

    /**
     * State: ujian dinas.
     */
    public function ujianDinas(): static
    {
        return $this->state(fn () => [
            'type' => 'ujian_dinas',
            'title' => 'Ujian CBT '.fake()->word(),
        ]);
    }

    /**
     * State: ujian sekolah.
     */
    public function ujianSekolah(): static
    {
        return $this->state(fn () => [
            'type' => 'ujian_sekolah',
            'scope' => 'level',
            'level_id' => Level::inRandomOrder()->first()?->id,
        ]);
    }

    /**
     * State: all-day event.
     */
    public function allDay(): static
    {
        return $this->state(fn () => [
            'is_all_day' => true,
            'start_time' => null,
            'end_time' => null,
        ]);
    }

    /**
     * State: recurring weekly event.
     */
    public function recurringWeekly(): static
    {
        return $this->state(fn () => [
            'recurrence_type' => 'weekly',
            'recurrence_config' => ['day_of_week' => fake()->numberBetween(0, 6)],
            'recurrence_end_date' => fake()->dateTimeBetween('+1 month', '+6 months'),
        ]);
    }

    /**
     * State: recurring monthly event.
     */
    public function recurringMonthly(): static
    {
        return $this->state(fn () => [
            'recurrence_type' => 'monthly',
            'recurrence_config' => ['week_of_month' => fake()->numberBetween(1, 4), 'day_of_week' => fake()->numberBetween(0, 6)],
            'recurrence_end_date' => fake()->dateTimeBetween('+2 months', '+12 months'),
        ]);
    }
}
