<?php

namespace Database\Factories;

use App\Models\SchoolProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StaffMember>
 */
class StaffMemberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $positions = [
            'Kepala PKBM',
            'Wakil Kepala PKBM',
            'Sekretaris',
            'Bendahara',
            'Koordinator Program PAUD',
            'Koordinator Program Paket A',
            'Koordinator Program Paket B',
            'Koordinator Program Paket C',
            'Tutor PAUD',
            'Tutor Paket A',
            'Tutor Paket B',
            'Tutor Paket C',
            'Tutor Bahasa Indonesia',
            'Tutor Matematika',
            'Tutor Bahasa Inggris',
            'Tutor IPA',
            'Tutor IPS',
            'Staf Administrasi',
            'Staf Tata Usaha',
            'Pustakawan',
        ];

        $titles = ['Drs.', 'S.Pd.', 'S.Pd., M.Pd.', 'S.Kom.', 'S.S.', 'S.Sos.', 'S.E.'];
        $name = fake()->name();

        // 70% chance to have a title
        if (fake()->boolean(70)) {
            $name = fake()->randomElement($titles).' '.$name;
        }

        return [
            'school_profile_id' => SchoolProfile::factory(),
            'name' => $name,
            'position' => fake()->randomElement($positions),
            'photo_path' => null,
            'order' => fake()->numberBetween(1, 100),
        ];
    }

    /**
     * Indicate that the staff member has a photo.
     */
    public function withPhoto(): static
    {
        return $this->state(fn (array $attributes) => [
            'photo_path' => 'staff/'.fake()->uuid().'.jpg',
        ]);
    }

    /**
     * Create a staff member with a specific position.
     */
    public function position(string $position): static
    {
        return $this->state(fn (array $attributes) => [
            'position' => $position,
        ]);
    }

    /**
     * Create a staff member as the head of PKBM.
     */
    public function headOfSchool(): static
    {
        return $this->state(fn (array $attributes) => [
            'position' => 'Kepala PKBM',
            'order' => 1,
        ]);
    }

    /**
     * Create a staff member as secretary.
     */
    public function secretary(): static
    {
        return $this->state(fn (array $attributes) => [
            'position' => 'Sekretaris',
            'order' => 2,
        ]);
    }

    /**
     * Create a staff member as treasurer.
     */
    public function treasurer(): static
    {
        return $this->state(fn (array $attributes) => [
            'position' => 'Bendahara',
            'order' => 3,
        ]);
    }
}
