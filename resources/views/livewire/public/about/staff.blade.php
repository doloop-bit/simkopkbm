<?php

use App\Models\StaffMember;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('components.public.layouts.public')] class extends Component
{
    public function with(): array
    {
        return [
            'staffMembers' => StaffMember::ordered()->get(),
            'title' => 'Struktur Organisasi - ' . config('app.name'),
            'description' => 'Kenali tim pengajar dan staf ' . config('app.name') . ' yang berpengalaman dan berkomitmen memberikan pendidikan terbaik untuk siswa.',
            'keywords' => 'Struktur Organisasi, Tim Pengajar, Staf, Guru, PKBM, Tenaga Pendidik',
            'ogTitle' => 'Struktur Organisasi - ' . config('app.name'),
            'ogDescription' => 'Kenali tim pengajar dan staf ' . config('app.name') . ' yang berpengalaman dan berkomitmen memberikan pendidikan terbaik untuk siswa.',
        ];
    }
}; ?>

<div>
    {{-- Hero Section --}}
    <div class="relative bg-gradient-to-br from-green-600 via-green-700 to-emerald-800 text-white overflow-hidden">
        <!-- Background Pattern -->
        <div class="absolute inset-0 opacity-20">
            <svg class="w-full h-full" viewBox="0 0 100 100" fill="none">
                <defs>
                    <pattern id="staff-grid" width="20" height="20" patternUnits="userSpaceOnUse">
                        <path d="M 20 0 L 0 0 0 20" fill="none" stroke="currentColor" stroke-width="0.5"/>
                    </pattern>
                </defs>
                <rect width="100" height="100" fill="url(#staff-grid)" />
            </svg>
        </div>
        
        <!-- Floating Elements -->
        <div class="absolute top-20 left-10 w-16 h-16 bg-green-400 rounded-full opacity-20 animate-pulse"></div>
        <div class="absolute top-40 right-20 w-12 h-12 bg-emerald-400 rounded-full opacity-30 animate-bounce"></div>
        <div class="absolute bottom-20 left-1/4 w-10 h-10 bg-green-300 rounded-full opacity-25 animate-pulse"></div>
        
        <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12 sm:py-16 lg:py-20">
            <div class="text-center">
                <h1 class="mb-4 sm:mb-6 text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-bold leading-tight">
                    <span class="bg-gradient-to-r from-white to-green-100 bg-clip-text text-transparent">
                        Struktur Organisasi
                    </span>
                </h1>
                <p class="text-lg sm:text-xl md:text-2xl text-green-100 font-light max-w-3xl mx-auto px-4">
                    Tim pengajar dan tenaga kependidikan yang berpengalaman dan berkomitmen memberikan pendidikan terbaik
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

    {{-- Staff Members --}}
    <div class="py-12 sm:py-16 lg:py-20 bg-gradient-to-br from-gray-50 via-white to-green-50">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if ($staffMembers->isNotEmpty())
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 sm:gap-8">
                    @foreach ($staffMembers as $staff)
                        <div class="group overflow-hidden rounded-xl bg-white shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:scale-105 border border-green-100">
                            {{-- Photo --}}
                            <div class="aspect-square overflow-hidden bg-gradient-to-br from-green-100 to-green-200">
                                @if ($staff->photo_path)
                                    <img 
                                        src="{{ Storage::url($staff->photo_path) }}" 
                                        alt="{{ $staff->name }}"
                                        class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110"
                                        loading="lazy"
                                    >
                                @else
                                    <div class="flex h-full items-center justify-center bg-gradient-to-br from-green-100 to-green-200">
                                        <div class="text-center">
                                            <svg class="h-16 w-16 mx-auto text-green-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                            </svg>
                                            <p class="text-sm text-green-600 font-medium">{{ $staff->name }}</p>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            {{-- Info --}}
                            <div class="p-4 sm:p-6">
                                <h3 class="mb-2 text-lg sm:text-xl font-bold text-gray-900 group-hover:text-green-700 transition-colors duration-200">
                                    {{ $staff->name }}
                                </h3>
                                <p class="mb-3 text-sm sm:text-base font-medium text-green-600">{{ $staff->position }}</p>
                                
                                @if ($staff->bio)
                                    <p class="mb-4 text-sm sm:text-base text-gray-600 leading-relaxed">
                                        {{ Str::limit($staff->bio, 120) }}
                                    </p>
                                @endif

                                {{-- Contact Info --}}
                                @if ($staff->email || $staff->phone)
                                    <div class="rounded-lg bg-gradient-to-r from-green-50 to-green-100 p-3 sm:p-4 border border-green-200">
                                        <div class="space-y-2">
                                            @if ($staff->email)
                                                <div class="flex items-center gap-2 text-xs sm:text-sm text-green-800">
                                                    <svg class="h-4 w-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                                    </svg>
                                                    <a href="mailto:{{ $staff->email }}" class="hover:text-green-700 transition-colors duration-200 break-all">
                                                        {{ $staff->email }}
                                                    </a>
                                                </div>
                                            @endif

                                            @if ($staff->phone)
                                                <div class="flex items-center gap-2 text-xs sm:text-sm text-green-800">
                                                    <svg class="h-4 w-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                                    </svg>
                                                    <a href="tel:{{ $staff->phone }}" class="hover:text-green-700 transition-colors duration-200">
                                                        {{ $staff->phone }}
                                                    </a>
                                                </div>
                                            @endif
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
                        <div class="w-24 h-24 mx-auto mb-6 bg-gradient-to-br from-green-100 to-green-200 rounded-full flex items-center justify-center">
                            <svg class="h-12 w-12 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl sm:text-2xl font-bold text-gray-900 mb-2">Belum Ada Data Staff</h3>
                        <p class="text-sm sm:text-base text-gray-600 leading-relaxed">
                            Informasi struktur organisasi sedang dalam proses pembaruan. Silakan kembali lagi nanti.
                        </p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>