<?php

use App\Http\Controllers\PublicSvelteController;
use Illuminate\Support\Facades\Route;

// Svelte V2 Public routes
Route::controller(PublicSvelteController::class)->group(function () {
    Route::get('/', 'home')->name('home');
    Route::get('/tentang-kami', 'about')->name('public.about');
    Route::get('/struktur-organisasi', 'staff')->name('public.organizational-structure');
    Route::get('/fasilitas', 'facilities')->name('public.facilities');
    Route::get('/program-pendidikan', 'programs')->name('public.programs.index');
    Route::get('/program-pendidikan/{slug}', 'programShow')->name('public.programs.show');
    Route::get('/berita', 'news')->name('public.news.index');
    Route::get('/berita/{slug}', 'newsShow')->name('public.news.show');
    Route::get('/galeri', 'gallery')->name('public.gallery');
    Route::get('/kontak', 'contact')->name('public.contact');
    Route::get('/pendaftaran', 'registration')->name('public.register');

    // Region APIs
    Route::get('/api/regions/regencies/{provinceId}', 'getRegencies')->name('api.regions.regencies');
    Route::get('/api/regions/districts/{regencyId}', 'getDistricts')->name('api.regions.districts');
    Route::get('/api/regions/villages/{districtId}', 'getVillages')->name('api.regions.villages');

    Route::post('/pendaftaran', 'storeRegistration')->name('public.register.store');
});

// Classic V1 (Livewire/Blade) routes
Route::prefix('v1')->name('v1.')->group(function () {
    Route::livewire('/', 'public.homepage')->name('home');

    Route::livewire('/tentang-kami', 'public.about.index')->name('public.about');
    Route::livewire('/struktur-organisasi', 'public.about.staff')->name('public.organizational-structure');
    Route::livewire('/fasilitas', 'public.about.facilities')->name('public.facilities');

    Route::livewire('/program-pendidikan', 'public.programs.index')->name('public.programs.index');
    Route::livewire('/program-pendidikan/{slug}', 'public.programs.show')->name('public.programs.show');

    Route::livewire('/berita', 'public.news.index')->name('public.news.index');
    Route::livewire('/berita/{slug}', 'public.news.show')->name('public.news.show');

    Route::livewire('/galeri', 'public.gallery')->name('public.gallery');
    Route::livewire('/kontak', 'public.contact')->name('public.contact');
    Route::livewire('/pendaftaran', 'public.register')->name('register');
});

// PAUD Landing Page
Route::get('/paud', function () {
    $program = \App\Models\Program::where('slug', 'like', '%paud%')->first()
        ?? new \App\Models\Program(['name' => 'PAUD Ceria']);

    return view('paud', compact('program'));
})->name('public.paud');

// Paket A Landing Page
Route::get('/paket-a', function () {
    $program = \App\Models\Program::where('slug', 'like', '%paket-a%')->first()
        ?? new \App\Models\Program(['name' => 'Paket A (Setara SD)']);

    return view('paketa', compact('program'));
})->name('public.paketa');

// SEO Routes
Route::get('/sitemap.xml', function () {
    $schoolProfile = \App\Models\SchoolProfile::active();
    $news = \App\Models\NewsArticle::published()->latest()->get();
    $programs = \App\Models\Program::active()->get();

    return response()->view('sitemap', [
        'schoolProfile' => $schoolProfile,
        'news' => $news,
        'programs' => $programs,
    ])->header('Content-Type', 'text/xml');
})->name('sitemap');
