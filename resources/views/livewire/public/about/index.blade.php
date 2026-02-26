<?php

use App\Models\SchoolProfile;
use Livewire\Component;
use Livewire\Attributes\Layout;

new #[Layout('components.public.layouts.public')] class extends Component
{
    public function with(): array
    {
        return [
            'schoolProfile' => SchoolProfile::active(),
            'title' => 'Tentang Kami - ' . config('app.name'),
            'description' => 'Pelajari lebih lanjut tentang visi, misi, dan sejarah ' . config('app.name') . ' - Pusat Kegiatan Belajar Masyarakat yang berkomitmen memberikan pendidikan berkualitas.',
            'keywords' => 'Tentang Kami, Visi Misi, Sejarah, PKBM, Pusat Kegiatan Belajar Masyarakat, Profil Sekolah',
            'ogTitle' => 'Tentang Kami - ' . config('app.name'),
            'ogDescription' => 'Pelajari lebih lanjut tentang visi, misi, dan sejarah ' . config('app.name') . ' - Pusat Kegiatan Belajar Masyarakat yang berkomitmen memberikan pendidikan berkualitas.',
        ];
    }
}; ?>

<div>
    @if ($schoolProfile)
        {{-- Hero Section --}}
        <div class="bg-slate-900 py-16 text-white relative overflow-hidden">
             <!-- Background Pattern -->
            <div class="absolute inset-0 opacity-10">
                <svg class="w-full h-full" viewBox="0 0 100 100" fill="none">
                    <defs>
                        <pattern id="about-grid" width="20" height="20" patternUnits="userSpaceOnUse">
                            <path d="M 20 0 L 0 0 0 20" fill="none" stroke="currentColor" stroke-width="0.5"/>
                        </pattern>
                    </defs>
                    <rect width="100" height="100" fill="url(#about-grid)" />
                </svg>
            </div>
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 relative z-10">
                <div class="text-center">
                    <h1 class="mb-4 text-4xl font-bold md:text-5xl font-heading">Tentang Kami</h1>
                    <p class="text-xl text-slate-300">
                        Mengenal lebih dekat {{ $schoolProfile->name }}
                    </p>
                    <div class="w-24 h-1 bg-amber-500 mx-auto mt-6 rounded-full"></div>
                </div>
            </div>
        </div>

        {{-- Main Content --}}
        <div class="py-16">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 gap-12 lg:grid-cols-2">
                    {{-- School Logo --}}
                    @if ($schoolProfile->logo_path)
                        <div class="flex justify-center lg:justify-start">
                            <img 
                                src="{{ Storage::url($schoolProfile->logo_path) }}" 
                                alt="Logo {{ $schoolProfile->name }}"
                                class="h-64 w-64 rounded-lg object-contain shadow-lg"
                            >
                        </div>
                    @endif

                    {{-- School Info --}}
                    <div class="space-y-8">
                        <div>
                            <h2 class="mb-4 text-3xl font-bold text-slate-900 font-heading">{{ $schoolProfile->name }}</h2>
                            <p class="text-lg leading-relaxed text-slate-600">
                                {{ $schoolProfile->description }}
                            </p>
                        </div>

                        {{-- Contact Info --}}
                        <div class="space-y-4">
                            <h3 class="text-xl font-semibold text-gray-900">Informasi Kontak</h3>
                            
                            @if ($schoolProfile->address)
                                <div class="flex items-start gap-3">
                                    <svg class="mt-1 h-5 w-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    <div>
                                        <p class="font-medium text-slate-900">Alamat</p>
                                        <p class="text-slate-600">{{ $schoolProfile->address }}</p>
                                    </div>
                                </div>
                            @endif

                            @if ($schoolProfile->phone)
                                <div class="flex items-start gap-3">
                                    <svg class="mt-1 h-5 w-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                    </svg>
                                    <div>
                                        <p class="font-medium text-slate-900">Telepon</p>
                                        <p class="text-slate-600">{{ $schoolProfile->phone }}</p>
                                    </div>
                                </div>
                            @endif

                            @if ($schoolProfile->email)
                                <div class="flex items-start gap-3">
                                    <svg class="mt-1 h-5 w-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                    <div>
                                        <p class="font-medium text-slate-900">Email</p>
                                        <p class="text-slate-600">{{ $schoolProfile->email }}</p>
                                    </div>
                                </div>
                            @endif

                            @if ($schoolProfile->website)
                                <div class="flex items-start gap-3">
                                    <svg class="mt-1 h-5 w-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9v-9m0-9v9m0 9c-5 0-9-4-9-9s4-9 9-9"/>
                                    </svg>
                                    <div>
                                        <p class="font-medium text-slate-900">Website</p>
                                        <a href="{{ $schoolProfile->website }}" target="_blank" class="text-amber-600 hover:text-amber-700">
                                            {{ $schoolProfile->website }}
                                        </a>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Vision & Mission --}}
                @if ($schoolProfile->vision || $schoolProfile->mission)
                    <div class="mt-16">
                        <div class="grid grid-cols-1 gap-12 lg:grid-cols-2">
                            @if ($schoolProfile->vision)
                                <div class="rounded-lg bg-slate-50 border border-slate-200 p-8 shadow-sm hover:shadow-md transition-all duration-300">
                                    <h3 class="mb-4 text-2xl font-bold font-heading text-slate-900">Visi</h3>
                                    <p class="text-lg leading-relaxed text-slate-700">
                                        {{ $schoolProfile->vision }}
                                    </p>
                                </div>
                            @endif

                            @if ($schoolProfile->mission)
                                <div class="rounded-lg bg-white border border-slate-200 p-8 shadow-sm hover:shadow-md transition-all duration-300">
                                    <h3 class="mb-4 text-2xl font-bold font-heading text-slate-900">Misi</h3>
                                    <div class="text-lg leading-relaxed text-slate-700">
                                        {!! nl2br(e($schoolProfile->mission)) !!}
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- History --}}
                @if ($schoolProfile->history)
                    <div class="mt-16">
                        <div class="mx-auto max-w-4xl">
                            <h3 class="mb-8 text-center text-3xl font-bold font-heading text-slate-900">Sejarah</h3>
                            <div class="rounded-lg bg-slate-50 p-8 border border-slate-200">
                                <div class="prose prose-lg max-w-none text-gray-700">
                                    {!! nl2br(e($schoolProfile->history)) !!}
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @else
        {{-- No School Profile --}}
        <div class="flex min-h-screen items-center justify-center">
            <div class="text-center">
                <h1 class="mb-4 text-4xl font-bold text-gray-900">Tentang Kami</h1>
                <p class="text-lg text-gray-600">Informasi sekolah sedang dalam proses pembaruan.</p>
            </div>
        </div>
    @endif
</div>