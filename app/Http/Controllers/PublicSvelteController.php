<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\CalendarEvent;
use App\Models\GalleryPhoto;
use App\Models\Level;
use App\Models\NewsArticle;
use App\Models\Program;
use App\Models\Registration;
use App\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Inertia;
use Laravolt\Indonesia\Models\City;
use Laravolt\Indonesia\Models\District;
use Laravolt\Indonesia\Models\Province;
use Laravolt\Indonesia\Models\Village;

class PublicSvelteController extends Controller
{
    public function __construct(
        protected CacheService $cacheService
    ) {}

    public function home()
    {
        return Inertia::render('Public/Home', [
            'latestNews' => $this->cacheService->getLatestNews(3),
            'programs' => $this->cacheService->getActivePrograms(),
            'featuredPhotos' => $this->cacheService->getFeaturedPhotos(6),
        ]);
    }

    public function about()
    {
        return Inertia::render('Public/About');
    }

    public function staff()
    {
        $profile = $this->cacheService->getSchoolProfile();
        $staffMembers = $profile?->staffMembers ?? collect();

        return Inertia::render('Public/Staff', [
            'staffMembers' => $staffMembers,
        ]);
    }

    public function facilities()
    {
        $profile = $this->cacheService->getSchoolProfile();
        $facilities = $profile?->facilities ?? collect();

        return Inertia::render('Public/Facilities', [
            'facilities' => $facilities,
        ]);
    }

    public function programs()
    {
        return Inertia::render('Public/ProgramsIndex', [
            'programs' => $this->cacheService->getActivePrograms(),
        ]);
    }

    public function programShow(string $slug)
    {
        $program = Program::where('slug', $slug)->with('level')->firstOrFail();

        $educationLevel = strtolower($program->level?->education_level ?? '');

        // Check for specific landing pages based on education level
        if ($educationLevel === 'paud') {
            return Inertia::render('Public/Landing/paud', [
                'programName' => $program->name,
                'programLogo' => $program->logo_path ? asset('storage/'.$program->logo_path) : null,
            ]);
        }

        if ($educationLevel === 'sd') {
            return Inertia::render('Public/Landing/paketa', [
                'programName' => $program->name,
                'programLogo' => $program->logo_path ? asset('storage/'.$program->logo_path) : null,
            ]);
        }

        return Inertia::render('Public/ProgramsShow', [
            'program' => $program,
        ]);
    }

    public function news()
    {
        $news = NewsArticle::published()->latest()->paginate(9);

        return Inertia::render('Public/NewsIndex', [
            'news' => $news,
        ]);
    }

    public function newsShow(string $slug)
    {
        $article = NewsArticle::published()->where('slug', $slug)->firstOrFail();
        $latestNews = NewsArticle::published()
            ->where('id', '!=', $article->id)
            ->latest()
            ->limit(3)
            ->get();

        return Inertia::render('Public/NewsShow', [
            'article' => $article,
            'latestNews' => $latestNews,
        ]);
    }

    public function gallery()
    {
        $photos = GalleryPhoto::published()->ordered()->paginate(12);
        $categories = $this->cacheService->getGalleryCategories();

        return Inertia::render('Public/Gallery', [
            'photos' => $photos,
            'categories' => $categories,
        ]);
    }

    public function contact()
    {
        return Inertia::render('Public/Contact');
    }

    public function registration()
    {
        return Inertia::render('Public/Register', [
            'levels' => Level::orderBy('name')->get(),
            'academicYears' => AcademicYear::orderByDesc('start_date')->get(),
            'provinces' => Province::orderBy('name')->get(),
            'cities' => City::orderBy('name')->limit(50)->get(), // Initial cities limited for performance
        ]);
    }

    public function calendar(Request $request)
    {
        $levels = Level::orderBy('name')->get();

        $activeYear = AcademicYear::where('is_active', true)->first();

        $query = CalendarEvent::query()
            ->with(['level'])
            ->parentsOnly();

        if ($activeYear) {
            $query->forAcademicYear($activeYear->id);
        }

        $events = $query->orderBy('start_date')->orderBy('start_time')->get()
            ->map(function ($event) {
                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'description' => $event->description,
                    'type' => $event->type,
                    'type_label' => $event->type_label,
                    'scope' => $event->scope,
                    'scope_label' => $event->scope_label,
                    'level_id' => $event->level_id,
                    'level' => $event->level ? [
                        'id' => $event->level->id,
                        'name' => $event->level->name,
                    ] : null,
                    'start_date' => $event->start_date->format('Y-m-d'),
                    'end_date' => $event->end_date ? $event->end_date->format('Y-m-d') : null,
                    'start_time' => $event->start_time ? substr($event->start_time, 0, 5) : null,
                    'end_time' => $event->end_time ? substr($event->end_time, 0, 5) : null,
                    'location' => $event->location,
                    'display_color' => $event->display_color,
                    'is_all_day' => $event->is_all_day,
                ];
            });

        return Inertia::render('Public/Calendar', [
            'levels' => $levels,
            'events' => $events,
            'typeLabels' => CalendarEvent::TYPE_LABELS,
            'typeColors' => CalendarEvent::TYPE_COLORS,
        ]);
    }

    public function getRegencies(string $provinceId)
    {
        return response()->json(
            City::where('province_code', $provinceId)->orderBy('name')->get()
        );
    }

    public function getDistricts(string $regencyId)
    {
        return response()->json(
            District::where('city_code', $regencyId)->orderBy('name')->get()
        );
    }

    public function getVillages(string $districtId)
    {
        return response()->json(
            Village::where('district_code', $districtId)->orderBy('name')->get()
        );
    }

    public function storeRegistration(Request $request)
    {
        // 1. Honeypot check
        if ($request->filled('extra_field')) {
            return response()->json(['message' => 'Spam detected.'], 422);
        }

        // 2. Rate limiting
        $key = 'registration-submit:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);

            return response()->json([
                'message' => "Terlalu banyak percobaan. Harap tunggu {$seconds} detik.",
            ], 429);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'nik' => ['nullable', 'string', 'max:16', function ($attribute, $value, $fail) {
                if ($value && Registration::where('nik', $value)->where('status', 'pending')->exists()) {
                    $fail('NIK ini sudah memiliki pendaftaran yang sedang diproses.');
                }
            }],
            'nisn' => ['nullable', 'string', 'max:20', function ($attribute, $value, $fail) {
                if ($value && Registration::where('nisn', $value)->where('status', 'pending')->exists()) {
                    $fail('NISN ini sudah memiliki pendaftaran yang sedang diproses.');
                }
            }],
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'preferred_level_id' => 'nullable|exists:levels,id',
            'academic_year_id' => 'nullable|exists:academic_years,id',
        ]);

        RateLimiter::hit($key, 1800);

        $registrationData = $request->all();
        $registrationData['registration_number'] = Registration::generateRegistrationNumber();
        $registrationData['status'] = 'pending';

        // Store names for province/city/district/village if IDs are provided
        if ($request->filled('province_id')) {
            $registrationData['province_name'] = Province::where('code', $request->province_id)->first()?->name;
        }
        if ($request->filled('regency_id')) {
            $registrationData['regency_name'] = City::where('code', $request->regency_id)->first()?->name;
        }
        if ($request->filled('district_id')) {
            $registrationData['district_name'] = District::where('code', $request->district_id)->first()?->name;
        }
        if ($request->filled('village_id')) {
            $registrationData['village_name'] = Village::where('code', $request->village_id)->first()?->name;
        }

        // Handle POB name resolution if it's a code
        if ($request->filled('pob') && is_numeric($request->pob)) {
            $registrationData['pob'] = City::where('code', $request->pob)->first()?->name ?? $request->pob;
        }

        $registration = Registration::create($registrationData);

        return response()->json([
            'success' => true,
            'registration_number' => $registration->registration_number,
            'message' => 'Pendaftaran berhasil dikirim.',
        ]);
    }
}
