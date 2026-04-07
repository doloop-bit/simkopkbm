<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
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
        ]);
    }

    /**
     * Indicate that the user is a teacher (guru).
     */
    public function guru(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'guru',
        ]);
    }

    /**
     * Indicate that the user is a student (siswa).
     */
    public function siswa(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'siswa',
        ])->afterCreating(function (\App\Models\User $user) {
            if (!$user->profiles()->exists()) {
                $studentProfile = \App\Models\StudentProfile::factory()->create();
                \App\Models\Profile::create([
                    'user_id' => $user->id,
                    'profileable_type' => \App\Models\StudentProfile::class,
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
        ]);
    }

    /**
     * Indicate that the user is a headmaster (kepsek).
     */
    public function kepsek(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'kepsek',
        ]);
    }

    /**
     * Indicate that the user is foundation (yayasan).
     */
    public function yayasan(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'yayasan',
        ]);
    }
}
