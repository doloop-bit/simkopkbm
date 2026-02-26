<?php

use App\Models\Program;
use App\Services\CacheService;
use Livewire\Component;
use Livewire\Attributes\Layout;

new #[Layout('components.public.layouts.public')] class extends Component
{
    public function with(): array
    {
        $cacheService = app(CacheService::class);
        
        return [
            'programs' => $cacheService->getActivePrograms(),
            'title' => 'Program Pendidikan - ' . config('app.name'),
            'description' => 'Temukan berbagai program pendidikan di ' . config('app.name') . ' mulai dari PAUD, Paket A, Paket B, hingga Paket C yang disesuaikan dengan kebutuhan Anda.',
            'keywords' => 'Program Pendidikan, PAUD, Paket A, Paket B, Paket C, Kurikulum, Pendidikan Non Formal',
            'ogTitle' => 'Program Pendidikan - ' . config('app.name'),
            'ogDescription' => 'Temukan berbagai program pendidikan di ' . config('app.name') . ' mulai dari PAUD, Paket A, Paket B, hingga Paket C yang disesuaikan dengan kebutuhan Anda.',
        ];
    }
}; ?>

<div>
    {{-- Hero Section --}}
    {{-- Hero Section --}}
    <div class="relative bg-slate-900 text-white overflow-hidden py-16">
        <!-- Background Pattern -->
        <div class="absolute inset-0 opacity-10">
            <svg class="w-full h-full" viewBox="0 0 100 100" fill="none">
                <defs>
                    <pattern id="programs-grid" width="20" height="20" patternUnits="userSpaceOnUse">
                        <path d="M 20 0 L 0 0 0 20" fill="none" stroke="currentColor" stroke-width="0.5"/>
                    </pattern>
                </defs>
                <rect width="100" height="100" fill="url(#programs-grid)" />
            </svg>
        </div>
        
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="text-center">
                <h1 class="mb-4 text-4xl font-bold md:text-5xl font-heading">Program Pendidikan</h1>
                <p class="text-xl text-slate-300">
                    Berbagai program pendidikan kesetaraan untuk semua kalangan
                </p>
                <div class="w-24 h-1 bg-amber-500 mx-auto mt-6 rounded-full"></div>
            </div>
        </div>
    </div>

    {{-- Programs --}}
    <div class="py-24 bg-white">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if ($programs->isNotEmpty())
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8 justify-center">
                    @foreach ($programs as $program)
                        <div class="group relative flex flex-col items-center text-center bg-white rounded-[2rem] p-8 ring-1 ring-zinc-200/60 shadow-sm hover:shadow-2xl hover:ring-zinc-300 transition-all duration-500 hover:-translate-y-2 overflow-hidden">
                            {{-- Decorative Background --}}
                            <div class="absolute -top-12 -right-12 w-24 h-24 bg-amber-500/5 rounded-full blur-2xl group-hover:bg-amber-500/10 transition-colors"></div>
                            
                            {{-- Icon/Image --}}
                            <div class="relative mb-8 transform group-hover:scale-110 transition-transform duration-500">
                                @if ($program->image_path)
                                    <div class="h-24 w-24 rounded-3xl overflow-hidden shadow-lg ring-4 ring-white">
                                        <img 
                                            src="{{ Storage::url($program->image_path) }}" 
                                            alt="{{ $program->name }}"
                                            class="h-full w-full object-cover"
                                        >
                                    </div>
                                @else
                                    <div class="flex h-20 w-20 items-center justify-center rounded-3xl bg-zinc-900 text-3xl font-bold text-white shadow-xl group-hover:bg-amber-500 transition-colors">
                                        {{ Str::upper(Str::substr($program->name, 0, 1)) }}
                                    </div>
                                @endif
                                {{-- Badge --}}
                                <div class="absolute -bottom-2 -right-2 bg-amber-500 text-white p-2 rounded-xl shadow-lg border-2 border-white transform rotate-12 group-hover:rotate-0 transition-transform">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </div>
                            </div>

                            <div class="flex-1 flex flex-col items-center">
                                <span class="inline-flex items-center rounded-full bg-zinc-100 px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-zinc-600 mb-4 group-hover:bg-amber-100 group-hover:text-amber-800 transition-colors">
                                    {{ $program->level?->name ?? 'Program' }}
                                </span>
                                
                                <h3 class="text-2xl font-bold font-heading text-zinc-900 mb-4 tracking-tight">{{ $program->name }}</h3>
                                
                                <div class="flex items-center gap-2 mb-6 text-zinc-500 text-xs font-medium">
                                    <svg class="h-4 w-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span>{{ $program->duration }}</span>
                                </div>

                                <p class="text-zinc-500 text-sm leading-relaxed mb-8 line-clamp-3">
                                    {{ $program->description }}
                                </p>

                                <a 
                                    href="{{ route('public.programs.show', $program->slug) }}" 
                                    class="mt-auto inline-flex items-center gap-2 rounded-2xl bg-zinc-900 px-6 py-3 text-sm font-semibold text-white transition-all hover:bg-amber-500 hover:shadow-xl hover:shadow-amber-500/20 group/btn"
                                >
                                    Lihat Detail
                                    <svg class="h-4 w-4 transform group-hover/btn:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                {{-- Empty State --}}
                <div class="py-16 text-center">
                    <svg class="mx-auto h-24 w-24 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                    <h3 class="mt-4 text-xl font-medium font-heading text-slate-900">Belum Ada Program</h3>
                    <p class="mt-2 text-slate-600">Program pendidikan sedang dalam proses persiapan.</p>
                </div>
            @endif
        </div>
    </div>
</div>