<?php

use Illuminate\Support\Facades\Route;

// Homepage
Route::livewire('/', 'public.homepage')->name('home');

// About pages
Route::livewire('/tentang-kami', 'public.about.index')->name('public.about');
Route::livewire('/struktur-organisasi', 'public.about.staff')->name('public.organizational-structure');
Route::livewire('/fasilitas', 'public.about.facilities')->name('public.facilities');

// Programs
Route::livewire('/program-pendidikan', 'public.programs.index')->name('public.programs.index');
Route::livewire('/program-pendidikan/{slug}', 'public.programs.show')->name('public.programs.show');

// News
Route::livewire('/berita', 'public.news.index')->name('public.news.index');
Route::livewire('/berita/{slug}', 'public.news.show')->name('public.news.show');

// Gallery
Route::livewire('/galeri', 'public.gallery')->name('public.gallery');

// Contact
Route::livewire('/kontak', 'public.contact')->name('public.contact');

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
