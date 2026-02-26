<?php

use App\Models\Program;
use App\Models\SchoolProfile;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Storage;

new #[Layout('components.public.layouts.public')] class extends Component
{
    public Program $program;

    public function mount(string $slug): void
    {
        $this->program = Program::with('level')
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();
    }

    public function with(): array
    {
        $schoolProfile = SchoolProfile::active();
        
        // Set SEO data dynamically
        $title = $this->program->name . ' - Program Pendidikan ' . config('app.name');
        $description = $this->program->description ? 
            substr(strip_tags($this->program->description), 0, 160) . '...' : 
            'Program ' . $this->program->name . ' di ' . config('app.name') . ' - Pusat Kegiatan Belajar Masyarakat.';
        
        return [
            'schoolProfile' => $schoolProfile,
            'title' => $title,
            'description' => $description,
            'keywords' => 'Program ' . $this->program->name . ', ' . ($this->program->level?->name ?? '') . ', Pendidikan, PKBM, ' . config('app.name'),
            'ogTitle' => $title,
            'ogDescription' => $description,
            'ogImage' => $this->program->image_path ? Storage::url($this->program->image_path) : null,
            'ogImageAlt' => 'Program ' . $this->program->name,
        ];
    }
}; ?>

<div>
    {{-- Hero Section --}}
    <div class="bg-slate-900 py-16 text-white relative">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="text-center">
                <nav class="mb-4 flex justify-center" aria-label="Breadcrumb">
                    <ol class="flex items-center space-x-2 text-slate-400">
                        <li>
                            <a href="{{ route('public.programs.index') }}" class="hover:text-amber-400 transition-colors">
                                Program Pendidikan
                            </a>
                        </li>
                        <li>
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                            </svg>
                        </li>
                        <li class="text-white">{{ $program->name }}</li>
                    </ol>
                </nav>
                <h1 class="mb-4 text-4xl font-bold md:text-5xl font-heading">{{ $program->name }}</h1>
                <p class="text-xl text-slate-300">{{ $program->level?->name ?? '-' }}</p>
            </div>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="py-16">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-12 lg:grid-cols-3">
                {{-- Main Content --}}
                <div class="lg:col-span-2">
                    {{-- Program Image --}}
                    @if ($program->image_path)
                        <div class="mb-8 overflow-hidden rounded-lg">
                            <img 
                                src="{{ Storage::url($program->image_path) }}" 
                                alt="{{ $program->name }}"
                                class="h-64 w-full object-cover md:h-80"
                            >
                        </div>
                    @endif

                    {{-- Description --}}
                    <div class="mb-8">
                        <h2 class="mb-4 text-2xl font-bold text-slate-900 font-heading">Tentang Program</h2>
                        <div class="prose prose-lg max-w-none text-slate-700">
                            {!! nl2br(e($program->description)) !!}
                        </div>
                    </div>

                    {{-- Curriculum Overview --}}
                    @if ($program->curriculum_overview)
                        <div class="mb-8">
                            <h2 class="mb-4 text-2xl font-bold text-slate-900 font-heading">Kurikulum</h2>
                            <div class="rounded-lg bg-slate-50 p-6 border border-slate-200">
                                <div class="prose max-w-none text-slate-700">
                                    {!! nl2br(e($program->curriculum_overview)) !!}
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Requirements --}}
                    @if ($program->requirements)
                        <div class="mb-8">
                            <h2 class="mb-4 text-2xl font-bold text-slate-900 font-heading">Persyaratan</h2>
                            <div class="rounded-lg bg-white border border-slate-200 p-6 shadow-sm">
                                <div class="prose max-w-none text-slate-700">
                                    {!! nl2br(e($program->requirements)) !!}
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Sidebar --}}
                <div class="lg:col-span-1">
                    <div class="sticky top-8 space-y-6">
                        {{-- Program Info --}}
                        <div class="rounded-lg bg-white p-6 shadow-lg border border-slate-100">
                            <h3 class="mb-4 text-lg font-bold text-slate-900 font-heading">Informasi Program</h3>
                            <div class="space-y-3">
                                <div class="flex items-center gap-3">
                                    <svg class="h-5 w-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <div>
                                        <p class="text-sm font-medium text-slate-900">Durasi</p>
                                        <p class="text-sm text-slate-600">{{ $program->duration }}</p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-3">
                                    <svg class="h-5 w-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                                    </svg>
                                    <div>
                                        <p class="text-sm font-medium text-slate-900">Tingkat</p>
                                        <p class="text-sm text-slate-600">{{ $program->level?->name ?? '-' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Contact Info --}}
                        @if ($schoolProfile)
                            <div class="rounded-lg bg-slate-50 p-6 border border-slate-200">
                                <h3 class="mb-4 text-lg font-bold text-slate-900 font-heading">Informasi Pendaftaran</h3>
                                <div class="space-y-3">
                                    @if ($schoolProfile->phone)
                                        <div class="flex items-center gap-3">
                                            <svg class="h-5 w-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                            </svg>
                                            <div>
                                                <p class="text-sm font-medium text-slate-900">Telepon</p>
                                                <a href="tel:{{ $schoolProfile->phone }}" class="text-sm text-slate-600 hover:text-amber-600">
                                                    {{ $schoolProfile->phone }}
                                                </a>
                                            </div>
                                        </div>
                                    @endif

                                    @if ($schoolProfile->email)
                                        <div class="flex items-center gap-3">
                                            <svg class="h-5 w-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                            </svg>
                                            <div>
                                                <p class="text-sm font-medium text-slate-900">Email</p>
                                                <a href="mailto:{{ $schoolProfile->email }}" class="text-sm text-slate-600 hover:text-amber-600">
                                                    {{ $schoolProfile->email }}
                                                </a>
                                            </div>
                                        </div>
                                    @endif

                                    @if ($schoolProfile->address)
                                        <div class="flex items-start gap-3">
                                            <svg class="mt-1 h-5 w-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            </svg>
                                            <div>
                                                <p class="text-sm font-medium text-slate-900">Alamat</p>
                                                <p class="text-sm text-slate-600">{{ $schoolProfile->address }}</p>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <div class="mt-6">
                                    <a 
                                        href="{{ route('public.contact') }}" 
                                        class="block w-full rounded-lg bg-amber-500 px-4 py-2 text-center text-sm font-medium text-white transition-colors hover:bg-amber-600"
                                    >
                                        Hubungi Kami
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>