<?php

declare(strict_types=1);

use App\Models\SchoolProfile;

beforeEach(function () {
    $this->withoutVite();
});

it('ensures only one school profile can be active at a time when creating new profile', function () {
    // Feature: school-profile-website, Property 17: School Profile Uniqueness

    // Create first active profile
    $firstProfile = SchoolProfile::factory()->create(['is_active' => true]);
    expect($firstProfile->is_active)->toBeTrue();

    // Create second profile as active - should deactivate first
    $secondProfile = SchoolProfile::factory()->create(['is_active' => true]);

    // Refresh first profile from database
    $firstProfile->refresh();

    // First profile should now be inactive
    expect($firstProfile->is_active)->toBeFalse();
    expect($secondProfile->is_active)->toBeTrue();

    // Only one active profile should exist
    expect(SchoolProfile::where('is_active', true)->count())->toBe(1);
})->repeat(50);

it('ensures only one school profile can be active at a time when updating existing profile', function () {
    // Feature: school-profile-website, Property 17: School Profile Uniqueness

    // Create multiple inactive profiles
    $profiles = SchoolProfile::factory()->count(3)->create(['is_active' => false]);

    // Activate one profile
    $profiles[1]->update(['is_active' => true]);

    // Verify only one is active
    expect(SchoolProfile::where('is_active', true)->count())->toBe(1);
    expect($profiles[1]->fresh()->is_active)->toBeTrue();

    // Activate another profile
    $profiles[2]->update(['is_active' => true]);

    // Verify the previous active profile is now inactive
    expect($profiles[1]->fresh()->is_active)->toBeFalse();
    expect($profiles[2]->fresh()->is_active)->toBeTrue();
    expect(SchoolProfile::where('is_active', true)->count())->toBe(1);
})->repeat(50);

it('allows multiple inactive profiles to exist', function () {
    // Feature: school-profile-website, Property 17: School Profile Uniqueness

    // Create multiple inactive profiles
    SchoolProfile::factory()->count(5)->create(['is_active' => false]);

    // All should remain inactive
    expect(SchoolProfile::where('is_active', false)->count())->toBe(5);
    expect(SchoolProfile::where('is_active', true)->count())->toBe(0);
})->repeat(30);

it('handles edge case when updating non-active field on active profile', function () {
    // Feature: school-profile-website, Property 17: School Profile Uniqueness

    // Create active profile
    $activeProfile = SchoolProfile::factory()->create(['is_active' => true]);

    // Create inactive profile
    $inactiveProfile = SchoolProfile::factory()->create(['is_active' => false]);

    // Update non-active field on active profile
    $activeProfile->update(['name' => 'Updated School Name']);

    // Both profiles should maintain their status
    expect($activeProfile->fresh()->is_active)->toBeTrue();
    expect($inactiveProfile->fresh()->is_active)->toBeFalse();
    expect(SchoolProfile::where('is_active', true)->count())->toBe(1);
})->repeat(30);
