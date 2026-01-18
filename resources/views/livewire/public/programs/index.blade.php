<?php

use App\Models\Program;
use App\Services\CacheService;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.public')] class extends Component
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
    <div class="bg-gradient-to-r from-indigo-600 to-indigo-800 py-16 text-white">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h1 class="mb-4 text-4xl font-bold md:text-5xl">Program Pendidikan</h1>
                <p class="text-xl text-indigo-100">
                    Berbagai program pendidikan kesetaraan untuk semua kalangan
                </p>
            </div>
        </div>
    </div>

    {{-- Programs --}}
    <div class="py-16">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if ($programs->isNotEmpty())
                <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
                    @foreach ($programs as $program)
                        <div class="group overflow-hidden rounded-lg bg-white shadow-lg transition-transform duration-300 hover:scale-105">
                            <div class="md:flex">
                                {{-- Image --}}
                                @if ($program->image_path)
                                    <div class="md:w-1/3">
                                        <div class="aspect-video overflow-hidden bg-gray-200 md:aspect-square">
                                            <img 
                                                src="{{ Storage::url($program->image_path) }}" 
                                                alt="{{ $program->name }}"
                                                class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-110"
                                            >
                                        </div>
                                    </div>
                                @endif

                                {{-- Content --}}
                                <div class="p-6 {{ $program->image_path ? 'md:w-2/3' : 'w-full' }}">
                                    <div class="mb-3 flex items-center gap-3">
                                        <h3 class="text-2xl font-bold text-gray-900">{{ $program->name }}</h3>
                                        <span class="rounded-full bg-indigo-100 px-3 py-1 text-sm font-medium text-indigo-800">
                                            {{ $program->level }}
                                        </span>
                                    </div>

                                    <div class="mb-4 flex items-center gap-4 text-sm text-gray-600">
                                        <div class="flex items-center gap-1">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            <span>{{ $program->duration }}</span>
                                        </div>
                                    </div>

                                    <p class="mb-6 text-gray-600 leading-relaxed">
                                        {{ Str::limit($program->description, 200) }}
                                    </p>

                                    <div class="flex items-center justify-between">
                                        <a 
                                            href="{{ route('public.programs.show', $program->slug) }}" 
                                            class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-indigo-700"
                                        >
                                            Selengkapnya
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                            </svg>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                {{-- Empty State --}}
                <div class="py-16 text-center">
                    <svg class="mx-auto h-24 w-24 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                    <h3 class="mt-4 text-xl font-medium text-gray-900">Belum Ada Program</h3>
                    <p class="mt-2 text-gray-600">Program pendidikan sedang dalam proses persiapan.</p>
                </div>
            @endif
        </div>
    </div>
</div>