<?php

use App\Models\Facility;
use App\Models\SchoolProfile;
use App\Models\StaffMember;

describe('SchoolProfile Model', function () {
    test('can create a school profile', function () {
        $profile = SchoolProfile::create([
            'name' => 'PKBM Test',
            'address' => 'Jl. Test No. 123',
            'phone' => '08123456789',
            'email' => 'test@pkbm.test',
            'vision' => 'Visi sekolah',
            'mission' => 'Misi sekolah',
            'is_active' => true,
        ]);

        expect($profile)->toBeInstanceOf(SchoolProfile::class)
            ->and($profile->name)->toBe('PKBM Test')
            ->and($profile->is_active)->toBeTrue();
    });

    test('casts is_active to boolean', function () {
        $profile = SchoolProfile::create([
            'name' => 'PKBM Test',
            'address' => 'Jl. Test No. 123',
            'phone' => '08123456789',
            'email' => 'test@pkbm.test',
            'vision' => 'Visi sekolah',
            'mission' => 'Misi sekolah',
            'is_active' => 1,
        ]);

        expect($profile->is_active)->toBeTrue()
            ->and($profile->is_active)->toBeBool();
    });

    test('casts latitude and longitude to decimal', function () {
        $profile = SchoolProfile::create([
            'name' => 'PKBM Test',
            'address' => 'Jl. Test No. 123',
            'phone' => '08123456789',
            'email' => 'test@pkbm.test',
            'vision' => 'Visi sekolah',
            'mission' => 'Misi sekolah',
            'latitude' => -6.12345678,
            'longitude' => 106.12345678,
        ]);

        expect($profile->latitude)->toBe('-6.12345678')
            ->and($profile->longitude)->toBe('106.12345678');
    });

    test('has staff members relationship', function () {
        $profile = SchoolProfile::create([
            'name' => 'PKBM Test',
            'address' => 'Jl. Test No. 123',
            'phone' => '08123456789',
            'email' => 'test@pkbm.test',
            'vision' => 'Visi sekolah',
            'mission' => 'Misi sekolah',
        ]);

        $staff = StaffMember::create([
            'school_profile_id' => $profile->id,
            'name' => 'John Doe',
            'position' => 'Kepala Sekolah',
            'order' => 1,
        ]);

        expect($profile->staffMembers)->toHaveCount(1)
            ->and($profile->staffMembers->first()->name)->toBe('John Doe');
    });

    test('has facilities relationship', function () {
        $profile = SchoolProfile::create([
            'name' => 'PKBM Test',
            'address' => 'Jl. Test No. 123',
            'phone' => '08123456789',
            'email' => 'test@pkbm.test',
            'vision' => 'Visi sekolah',
            'mission' => 'Misi sekolah',
        ]);

        $facility = Facility::create([
            'school_profile_id' => $profile->id,
            'name' => 'Ruang Kelas',
            'description' => 'Ruang kelas yang nyaman',
            'order' => 1,
        ]);

        expect($profile->facilities)->toHaveCount(1)
            ->and($profile->facilities->first()->name)->toBe('Ruang Kelas');
    });

    test('active method returns active profile', function () {
        SchoolProfile::create([
            'name' => 'PKBM Inactive',
            'address' => 'Jl. Test No. 123',
            'phone' => '08123456789',
            'email' => 'test1@pkbm.test',
            'vision' => 'Visi sekolah',
            'mission' => 'Misi sekolah',
            'is_active' => false,
        ]);

        $activeProfile = SchoolProfile::create([
            'name' => 'PKBM Active',
            'address' => 'Jl. Test No. 456',
            'phone' => '08123456789',
            'email' => 'test2@pkbm.test',
            'vision' => 'Visi sekolah',
            'mission' => 'Misi sekolah',
            'is_active' => true,
        ]);

        $result = SchoolProfile::active();

        expect($result)->not->toBeNull()
            ->and($result->id)->toBe($activeProfile->id)
            ->and($result->name)->toBe('PKBM Active');
    });

    test('only one profile can be active at a time when creating', function () {
        $profile1 = SchoolProfile::create([
            'name' => 'PKBM First',
            'address' => 'Jl. Test No. 123',
            'phone' => '08123456789',
            'email' => 'test1@pkbm.test',
            'vision' => 'Visi sekolah',
            'mission' => 'Misi sekolah',
            'is_active' => true,
        ]);

        expect($profile1->is_active)->toBeTrue();

        $profile2 = SchoolProfile::create([
            'name' => 'PKBM Second',
            'address' => 'Jl. Test No. 456',
            'phone' => '08123456789',
            'email' => 'test2@pkbm.test',
            'vision' => 'Visi sekolah',
            'mission' => 'Misi sekolah',
            'is_active' => true,
        ]);

        // Refresh profile1 from database
        $profile1->refresh();

        expect($profile2->is_active)->toBeTrue()
            ->and($profile1->is_active)->toBeFalse()
            ->and(SchoolProfile::where('is_active', true)->count())->toBe(1);
    });

    test('only one profile can be active at a time when updating', function () {
        $profile1 = SchoolProfile::create([
            'name' => 'PKBM First',
            'address' => 'Jl. Test No. 123',
            'phone' => '08123456789',
            'email' => 'test1@pkbm.test',
            'vision' => 'Visi sekolah',
            'mission' => 'Misi sekolah',
            'is_active' => true,
        ]);

        $profile2 = SchoolProfile::create([
            'name' => 'PKBM Second',
            'address' => 'Jl. Test No. 456',
            'phone' => '08123456789',
            'email' => 'test2@pkbm.test',
            'vision' => 'Visi sekolah',
            'mission' => 'Misi sekolah',
            'is_active' => false,
        ]);

        expect($profile1->is_active)->toBeTrue()
            ->and($profile2->is_active)->toBeFalse();

        // Activate profile2
        $profile2->update(['is_active' => true]);

        // Refresh profile1 from database
        $profile1->refresh();

        expect($profile2->is_active)->toBeTrue()
            ->and($profile1->is_active)->toBeFalse()
            ->and(SchoolProfile::where('is_active', true)->count())->toBe(1);
    });

    test('updating other fields does not affect active status', function () {
        $profile1 = SchoolProfile::create([
            'name' => 'PKBM First',
            'address' => 'Jl. Test No. 123',
            'phone' => '08123456789',
            'email' => 'test1@pkbm.test',
            'vision' => 'Visi sekolah',
            'mission' => 'Misi sekolah',
            'is_active' => true,
        ]);

        $profile2 = SchoolProfile::create([
            'name' => 'PKBM Second',
            'address' => 'Jl. Test No. 456',
            'phone' => '08123456789',
            'email' => 'test2@pkbm.test',
            'vision' => 'Visi sekolah',
            'mission' => 'Misi sekolah',
            'is_active' => false,
        ]);

        // Update profile2's name without changing is_active
        $profile2->update(['name' => 'PKBM Second Updated']);

        // Refresh both profiles
        $profile1->refresh();
        $profile2->refresh();

        expect($profile1->is_active)->toBeTrue()
            ->and($profile2->is_active)->toBeFalse()
            ->and($profile2->name)->toBe('PKBM Second Updated');
    });

    test('staff members are ordered by order field', function () {
        $profile = SchoolProfile::create([
            'name' => 'PKBM Test',
            'address' => 'Jl. Test No. 123',
            'phone' => '08123456789',
            'email' => 'test@pkbm.test',
            'vision' => 'Visi sekolah',
            'mission' => 'Misi sekolah',
        ]);

        StaffMember::create([
            'school_profile_id' => $profile->id,
            'name' => 'Staff C',
            'position' => 'Position C',
            'order' => 3,
        ]);

        StaffMember::create([
            'school_profile_id' => $profile->id,
            'name' => 'Staff A',
            'position' => 'Position A',
            'order' => 1,
        ]);

        StaffMember::create([
            'school_profile_id' => $profile->id,
            'name' => 'Staff B',
            'position' => 'Position B',
            'order' => 2,
        ]);

        $staffMembers = $profile->staffMembers;

        expect($staffMembers)->toHaveCount(3)
            ->and($staffMembers->first()->name)->toBe('Staff A')
            ->and($staffMembers->last()->name)->toBe('Staff C');
    });

    test('facilities are ordered by order field', function () {
        $profile = SchoolProfile::create([
            'name' => 'PKBM Test',
            'address' => 'Jl. Test No. 123',
            'phone' => '08123456789',
            'email' => 'test@pkbm.test',
            'vision' => 'Visi sekolah',
            'mission' => 'Misi sekolah',
        ]);

        Facility::create([
            'school_profile_id' => $profile->id,
            'name' => 'Facility C',
            'description' => 'Description C',
            'order' => 3,
        ]);

        Facility::create([
            'school_profile_id' => $profile->id,
            'name' => 'Facility A',
            'description' => 'Description A',
            'order' => 1,
        ]);

        Facility::create([
            'school_profile_id' => $profile->id,
            'name' => 'Facility B',
            'description' => 'Description B',
            'order' => 2,
        ]);

        $facilities = $profile->facilities;

        expect($facilities)->toHaveCount(3)
            ->and($facilities->first()->name)->toBe('Facility A')
            ->and($facilities->last()->name)->toBe('Facility C');
    });
});
