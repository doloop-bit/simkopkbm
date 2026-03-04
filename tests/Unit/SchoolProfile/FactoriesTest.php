<?php

declare(strict_types=1);

use App\Models\ContactInquiry;
use App\Models\Facility;
use App\Models\GalleryPhoto;
use App\Models\NewsArticle;
use App\Models\Program;
use App\Models\SchoolProfile;
use App\Models\StaffMember;
use App\Models\User;

beforeEach(function () {
    $this->withoutVite();
});

describe('School Profile Factories', function () {
    test('SchoolProfile factory creates valid record', function () {
        $profile = SchoolProfile::factory()->create();

        expect($profile)->toBeInstanceOf(SchoolProfile::class)
            ->and($profile->name)->not->toBeEmpty()
            ->and($profile->address)->not->toBeEmpty()
            ->and($profile->phone)->not->toBeEmpty()
            ->and($profile->email)->not->toBeEmpty()
            ->and($profile->vision)->not->toBeEmpty()
            ->and($profile->mission)->not->toBeEmpty()
            ->and($profile->is_active)->toBeBool();
    });

    test('SchoolProfile factory active state works', function () {
        $profile = SchoolProfile::factory()->active()->create();

        expect($profile->is_active)->toBeTrue();
    });

    test('SchoolProfile factory withLogo state works', function () {
        $profile = SchoolProfile::factory()->withLogo()->create();

        expect($profile->logo_path)->not->toBeNull();
    });

    test('SchoolProfile factory withSocialMedia state works', function () {
        $profile = SchoolProfile::factory()->withSocialMedia()->create();

        expect($profile->facebook_url)->not->toBeNull()
            ->and($profile->instagram_url)->not->toBeNull()
            ->and($profile->youtube_url)->not->toBeNull()
            ->and($profile->twitter_url)->not->toBeNull();
    });

    test('NewsArticle factory creates valid record', function () {
        $article = NewsArticle::factory()->create();

        expect($article)->toBeInstanceOf(NewsArticle::class)
            ->and($article->title)->not->toBeEmpty()
            ->and($article->slug)->not->toBeEmpty()
            ->and($article->content)->not->toBeEmpty()
            ->and($article->status)->toBe('published')
            ->and($article->author_id)->not->toBeNull();
    });

    test('NewsArticle factory draft state works', function () {
        $article = NewsArticle::factory()->draft()->create();

        expect($article->status)->toBe('draft')
            ->and($article->published_at)->toBeNull();
    });

    test('NewsArticle factory published state works', function () {
        $article = NewsArticle::factory()->published()->create();

        expect($article->status)->toBe('published')
            ->and($article->published_at)->not->toBeNull();
    });

    test('NewsArticle factory withFeaturedImage state works', function () {
        $article = NewsArticle::factory()->withFeaturedImage()->create();

        expect($article->featured_image_path)->not->toBeNull();
    });

    test('GalleryPhoto factory creates valid record', function () {
        $photo = GalleryPhoto::factory()->create();

        expect($photo)->toBeInstanceOf(GalleryPhoto::class)
            ->and($photo->title)->not->toBeEmpty()
            ->and($photo->category)->not->toBeEmpty()
            ->and($photo->original_path)->not->toBeEmpty()
            ->and($photo->thumbnail_path)->not->toBeEmpty()
            ->and($photo->web_path)->not->toBeEmpty()
            ->and($photo->is_published)->toBeTrue();
    });

    test('GalleryPhoto factory unpublished state works', function () {
        $photo = GalleryPhoto::factory()->unpublished()->create();

        expect($photo->is_published)->toBeFalse();
    });

    test('GalleryPhoto factory category state works', function () {
        $photo = GalleryPhoto::factory()->category('Kegiatan Belajar')->create();

        expect($photo->category)->toBe('Kegiatan Belajar');
    });

    test('Program factory creates valid record', function () {
        $program = Program::factory()->create();

        expect($program)->toBeInstanceOf(Program::class)
            ->and($program->name)->not->toBeEmpty()
            ->and($program->slug)->not->toBeEmpty()
            ->and($program->level)->toBeIn(['paud', 'paket_a', 'paket_b', 'paket_c'])
            ->and($program->description)->not->toBeEmpty()
            ->and($program->is_active)->toBeTrue();
    });

    test('Program factory inactive state works', function () {
        $program = Program::factory()->inactive()->create();

        expect($program->is_active)->toBeFalse();
    });

    test('Program factory withImage state works', function () {
        $program = Program::factory()->withImage()->create();

        expect($program->image_path)->not->toBeNull();
    });

    test('Program factory paud state works', function () {
        $program = Program::factory()->paud()->create();

        expect($program->level)->toBe('paud')
            ->and($program->order)->toBe(1);
    });

    test('Program factory paketA state works', function () {
        $program = Program::factory()->paketA()->create();

        expect($program->level)->toBe('paket_a')
            ->and($program->order)->toBe(2);
    });

    test('Program factory paketB state works', function () {
        $program = Program::factory()->paketB()->create();

        expect($program->level)->toBe('paket_b')
            ->and($program->order)->toBe(3);
    });

    test('Program factory paketC state works', function () {
        $program = Program::factory()->paketC()->create();

        expect($program->level)->toBe('paket_c')
            ->and($program->order)->toBe(4);
    });

    test('StaffMember factory creates valid record', function () {
        $staff = StaffMember::factory()->create();

        expect($staff)->toBeInstanceOf(StaffMember::class)
            ->and($staff->name)->not->toBeEmpty()
            ->and($staff->position)->not->toBeEmpty()
            ->and($staff->school_profile_id)->not->toBeNull();
    });

    test('StaffMember factory withPhoto state works', function () {
        $staff = StaffMember::factory()->withPhoto()->create();

        expect($staff->photo_path)->not->toBeNull();
    });

    test('StaffMember factory headOfSchool state works', function () {
        $staff = StaffMember::factory()->headOfSchool()->create();

        expect($staff->position)->toBe('Kepala PKBM')
            ->and($staff->order)->toBe(1);
    });

    test('StaffMember factory secretary state works', function () {
        $staff = StaffMember::factory()->secretary()->create();

        expect($staff->position)->toBe('Sekretaris')
            ->and($staff->order)->toBe(2);
    });

    test('StaffMember factory treasurer state works', function () {
        $staff = StaffMember::factory()->treasurer()->create();

        expect($staff->position)->toBe('Bendahara')
            ->and($staff->order)->toBe(3);
    });

    test('Facility factory creates valid record', function () {
        $facility = Facility::factory()->create();

        expect($facility)->toBeInstanceOf(Facility::class)
            ->and($facility->name)->not->toBeEmpty()
            ->and($facility->description)->not->toBeEmpty()
            ->and($facility->school_profile_id)->not->toBeNull();
    });

    test('Facility factory withImage state works', function () {
        $facility = Facility::factory()->withImage()->create();

        expect($facility->image_path)->not->toBeNull();
    });

    test('ContactInquiry factory creates valid record', function () {
        $inquiry = ContactInquiry::factory()->create();

        expect($inquiry)->toBeInstanceOf(ContactInquiry::class)
            ->and($inquiry->name)->not->toBeEmpty()
            ->and($inquiry->email)->not->toBeEmpty()
            ->and($inquiry->phone)->not->toBeEmpty()
            ->and($inquiry->subject)->not->toBeEmpty()
            ->and($inquiry->message)->not->toBeEmpty()
            ->and($inquiry->is_read)->toBeFalse();
    });

    test('ContactInquiry factory read state works', function () {
        $inquiry = ContactInquiry::factory()->read()->create();

        expect($inquiry->is_read)->toBeTrue();
    });

    test('ContactInquiry factory unread state works', function () {
        $inquiry = ContactInquiry::factory()->unread()->create();

        expect($inquiry->is_read)->toBeFalse();
    });
});

describe('Factory Relationships', function () {
    test('SchoolProfile factory creates staff members relationship', function () {
        $profile = SchoolProfile::factory()
            ->has(StaffMember::factory()->count(3))
            ->create();

        expect($profile->staffMembers)->toHaveCount(3);
    });

    test('SchoolProfile factory creates facilities relationship', function () {
        $profile = SchoolProfile::factory()
            ->has(Facility::factory()->count(5))
            ->create();

        expect($profile->facilities)->toHaveCount(5);
    });

    test('NewsArticle factory creates with author relationship', function () {
        $user = User::factory()->create();
        $article = NewsArticle::factory()->for($user, 'author')->create();

        expect($article->author_id)->toBe($user->id)
            ->and($article->author)->toBeInstanceOf(User::class);
    });

    test('StaffMember factory creates with school profile relationship', function () {
        $profile = SchoolProfile::factory()->create();
        $staff = StaffMember::factory()->for($profile, 'schoolProfile')->create();

        expect($staff->school_profile_id)->toBe($profile->id)
            ->and($staff->schoolProfile)->toBeInstanceOf(SchoolProfile::class);
    });

    test('Facility factory creates with school profile relationship', function () {
        $profile = SchoolProfile::factory()->create();
        $facility = Facility::factory()->for($profile, 'schoolProfile')->create();

        expect($facility->school_profile_id)->toBe($profile->id)
            ->and($facility->schoolProfile)->toBeInstanceOf(SchoolProfile::class);
    });
});

describe('Factory Data Quality', function () {
    test('SchoolProfile factory generates realistic Indonesian data', function () {
        $profile = SchoolProfile::factory()->create();

        expect($profile->name)->toContain('PKBM')
            ->and($profile->phone)->toMatch('/^\(0\d{3}\) \d{4}-\d{4}$/')
            ->and($profile->operating_hours)->toContain('Senin');
    });

    test('NewsArticle factory generates realistic Indonesian titles', function () {
        $article = NewsArticle::factory()->create();

        // Check that title is in Indonesian (contains common Indonesian words)
        $indonesianWords = ['Kegiatan', 'PKBM', 'Peserta', 'Didik', 'Program', 'Pendidikan', 'Pembelajaran'];
        $containsIndonesian = false;

        foreach ($indonesianWords as $word) {
            if (str_contains($article->title, $word)) {
                $containsIndonesian = true;
                break;
            }
        }

        expect($containsIndonesian)->toBeTrue();
    });

    test('GalleryPhoto factory generates Indonesian categories', function () {
        $photo = GalleryPhoto::factory()->create();

        $validCategories = [
            'Kegiatan Belajar',
            'Upacara',
            'Olahraga',
            'Seni dan Budaya',
            'Kegiatan Ekstrakurikuler',
            'Fasilitas',
            'Wisuda',
            'Kunjungan',
        ];

        expect($photo->category)->toBeIn($validCategories);
    });

    test('Program factory generates correct level-specific data', function () {
        $paud = Program::factory()->paud()->create();
        $paketA = Program::factory()->paketA()->create();
        $paketB = Program::factory()->paketB()->create();
        $paketC = Program::factory()->paketC()->create();

        expect($paud->name)->toContain('PAUD')
            ->and($paketA->name)->toContain('Paket A')
            ->and($paketB->name)->toContain('Paket B')
            ->and($paketC->name)->toContain('Paket C');
    });

    test('StaffMember factory generates realistic positions', function () {
        $staff = StaffMember::factory()->create();

        $validPositions = [
            'Kepala PKBM', 'Wakil Kepala PKBM', 'Sekretaris', 'Bendahara',
            'Koordinator Program PAUD', 'Koordinator Program Paket A',
            'Koordinator Program Paket B', 'Koordinator Program Paket C',
            'Tutor PAUD', 'Tutor Paket A', 'Tutor Paket B', 'Tutor Paket C',
            'Tutor Bahasa Indonesia', 'Tutor Matematika', 'Tutor Bahasa Inggris',
            'Tutor IPA', 'Tutor IPS', 'Staf Administrasi', 'Staf Tata Usaha',
            'Pustakawan',
        ];

        expect($staff->position)->toBeIn($validPositions);
    });

    test('ContactInquiry factory generates realistic Indonesian phone numbers', function () {
        $inquiry = ContactInquiry::factory()->create();

        expect($inquiry->phone)->toMatch('/^08\d{10}$/');
    });
});
