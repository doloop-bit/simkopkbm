<?php

namespace Database\Factories;

use App\Models\Profile;
use App\Models\Role;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the model has two-factor authentication configured.
     */
    public function withTwoFactor(): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_secret' => encrypt('secret'),
            'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code-1'])),
            'two_factor_confirmed_at' => now(),
        ]);
    }

    /**
     * Indicate that the user is an admin.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
        ])->afterCreating(function (User $user) {
            $role = Role::where('slug', 'admin')->first();
            if ($role) {
                $user->roles()->syncWithoutDetaching([$role->id]);
            }
        });
    }

    /**
     * Indicate that the user is a teacher (guru).
     */
    public function guru(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'guru',
        ])->afterCreating(function (User $user) {
            $role = Role::where('slug', 'guru')->first();
            if ($role) {
                $user->roles()->syncWithoutDetaching([$role->id]);
            }
        });
    }

    /**
     * Indicate that the user is a student (siswa).
     */
    public function siswa(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'siswa',
        ])->afterCreating(function (User $user) {
            $role = Role::where('slug', 'siswa')->first();
            if ($role) {
                $user->roles()->syncWithoutDetaching([$role->id]);
            }

            if (! $user->profiles()->exists()) {
                $studentProfile = StudentProfile::factory()->create();
                Profile::create([
                    'user_id' => $user->id,
                    'profileable_type' => StudentProfile::class,
                    'profileable_id' => $studentProfile->id,
                ]);
            }
        });
    }

    /**
     * Indicate that the user is a treasurer (bendahara).
     */
    public function bendahara(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'bendahara',
        ])->afterCreating(function (User $user) {
            $role = Role::where('slug', 'bendahara')->first();
            if ($role) {
                $user->roles()->syncWithoutDetaching([$role->id]);
            }
        });
    }

    /**
     * Indicate that the user is a headmaster (kepsek).
     */
    public function kepsek(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'kepsek',
        ])->afterCreating(function (User $user) {
            $role = Role::where('slug', 'kepsek')->first();
            if ($role) {
                $user->roles()->syncWithoutDetaching([$role->id]);
            }
        });
    }

    /**
     * Indicate that the user is foundation (yayasan).
     */
    public function yayasan(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'yayasan',
        ])->afterCreating(function (User $user) {
            $role = Role::where('slug', 'yayasan')->first();
            if ($role) {
                $user->roles()->syncWithoutDetaching([$role->id]);
            }
        });
    }
}
