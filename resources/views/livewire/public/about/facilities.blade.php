<?php

use App\Models\Facility;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('components.public.layouts.public')] class extends Component
{
    public function with(): array
    {
        return [
            'facilities' => Facility::ordered()->get(),
            'title' => 'Fasilitas - ' . config('app.name'),
            'description' => 'Jelajahi fasilitas lengkap dan modern di ' . config('app.name') . ' yang mendukung proses pembelajaran yang nyaman dan efektif.',
            'keywords' => 'Fasilitas, Sarana Prasarana, Ruang Kelas, Perpustakaan, PKBM, Fasilitas Sekolah',
            'ogTitle' => 'Fasilitas - ' . config('app.name'),
            'ogDescription' => 'Jelajahi fasilitas lengkap dan modern di ' . config('app.name') . ' yang mendukung proses pembelajaran yang nyaman dan efektif.',
        ];
    }
}; ?>

<div>
    {{-- Hero Section --}}
    <div class="relative bg-slate-900 text-white overflow-hidden">
        <!-- Background Pattern -->
        <div class="absolute inset-0 opacity-10">
            <svg class="w-full h-full" viewBox="0 0 100 100" fill="none">
                <defs>
                    <pattern id="facilities-grid" width="20" height="20" patternUnits="userSpaceOnUse">
                        <path d="M 20 0 L 0 0 0 20" fill="none" stroke="currentColor" stroke-width="0.5"/>
                    </pattern>
                </defs>
                <rect width="100" height="100" fill="url(#facilities-grid)" />
            </svg>
        </div>
        
        <!-- Floating Elements -->
        <div class="absolute top-20 left-10 w-16 h-16 bg-amber-400 rounded-full opacity-10 animate-pulse"></div>
        <div class="absolute top-40 right-20 w-12 h-12 bg-blue-500 rounded-full opacity-10 animate-bounce"></div>
        <div class="absolute bottom-20 left-1/4 w-10 h-10 bg-slate-600 rounded-full opacity-20 animate-pulse"></div>
        
        <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12 sm:py-16 lg:py-20">
            <div class="text-center">
                <h1 class="mb-4 sm:mb-6 text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-bold leading-tight font-heading">
                    <span class="bg-gradient-to-r from-white to-slate-300 bg-clip-text text-transparent">
                        Fasilitas
                    </span>
                </h1>
                <p class="text-lg sm:text-xl md:text-2xl text-slate-300 font-light max-w-3xl mx-auto px-4">
                    Fasilitas lengkap dan modern untuk mendukung proses pembelajaran yang nyaman dan efektif
                </p>
            </div>
        </div>
        
        <!-- Wave Bottom -->
        <div class="absolute bottom-0 left-0 right-0">
            <svg viewBox="0 0 1440 120" fill="none" class="w-full h-auto">
                <path d="M0,64L48,69.3C96,75,192,85,288,80C384,75,480,53,576,48C672,43,768,53,864,64C960,75,1056,85,1152,80C1248,75,1344,53,1392,42.7L1440,32L1440,120L1392,120C1344,120,1248,120,1152,120C1056,120,960,120,864,120C768,120,672,120,576,120C480,120,384,120,288,120C192,120,96,120,48,120L0,120Z" fill="rgb(249 250 251)"/>
            </svg>
        </div>
    </div>

    {{-- Facilities --}}
    <div class="py-12 sm:py-16 lg:py-20 bg-white">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if ($facilities->isNotEmpty())
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
                    @foreach ($facilities as $facility)
                        <div class="group overflow-hidden rounded-xl bg-white shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:scale-105 border border-slate-100">
                            {{-- Image --}}
                            <div class="aspect-video overflow-hidden bg-slate-100">
                                @if ($facility->image_path)
                                    <img 
                                        src="{{ Storage::url($facility->image_path) }}" 
                                        alt="{{ $facility->name }}"
                                        class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110"
                                        loading="lazy"
                                    >
                                @else
                                    <div class="flex h-full items-center justify-center bg-slate-100">
                                        <div class="text-center">
                                            <svg class="h-16 w-16 mx-auto text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                            </svg>
                                            <p class="text-sm text-slate-500 font-medium">{{ $facility->name }}</p>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            {{-- Content --}}
                            <div class="p-4 sm:p-6">
                                <h3 class="mb-3 text-lg sm:text-xl font-bold font-heading text-slate-900 group-hover:text-amber-600 transition-colors duration-200">
                                    {{ $facility->name }}
                                </h3>
                                
                                @if ($facility->description)
                                    <p class="mb-4 text-sm sm:text-base text-slate-600 leading-relaxed">
                                        {{ $facility->description }}
                                    </p>
                                @endif

                                {{-- Specifications --}}
                                @if ($facility->specifications)
                                    <div class="rounded-lg bg-slate-50 p-3 sm:p-4 border border-slate-200">
                                        <h4 class="mb-2 text-xs sm:text-sm font-semibold text-slate-900 flex items-center">
                                            <svg class="w-4 h-4 mr-2 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                            Spesifikasi:
                                        </h4>
                                        <div class="text-xs sm:text-sm text-slate-700 leading-relaxed">
                                            {!! nl2br(e($facility->specifications)) !!}
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                {{-- Empty State --}}
                <div class="py-12 sm:py-16 text-center">
                    <div class="max-w-md mx-auto">
                        <div class="w-24 h-24 mx-auto mb-6 bg-slate-100 rounded-full flex items-center justify-center">
                            <svg class="h-12 w-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                        <h3 class="text-xl sm:text-2xl font-bold text-slate-900 mb-2 font-heading">Belum Ada Data Fasilitas</h3>
                        <p class="text-sm sm:text-base text-gray-600 leading-relaxed">
                            Informasi fasilitas sedang dalam proses pembaruan. Silakan kembali lagi nanti.
                        </p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>