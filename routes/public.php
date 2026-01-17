<?php

use Livewire\Volt\Volt;
use Illuminate\Support\Facades\Route;

// Homepage
Volt::route('/', 'public.homepage')->name('home');

// About pages
Volt::route('/tentang-kami', 'public.about.index')->name('public.about');
Volt::route('/struktur-organisasi', 'public.about.staff')->name('public.organizational-structure');
Volt::route('/fasilitas', 'public.about.facilities')->name('public.facilities');

// Programs
Volt::route('/program-pendidikan', 'public.programs.index')->name('public.programs.index');
Volt::route('/program-pendidikan/{slug}', 'public.programs.show')->name('public.programs.show');

// News
Volt::route('/berita', 'public.news.index')->name('public.news.index');
Volt::route('/berita/{slug}', 'public.news.show')->name('public.news.show');

// Gallery
Volt::route('/galeri', 'public.gallery')->name('public.gallery');

// Contact
Volt::route('/kontak', 'public.contact')->name('public.contact');

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
