<?php

use App\Models\Facility;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.public')] class extends Component
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
    <div class="bg-gradient-to-r from-purple-600 to-purple-800 py-16 text-white">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h1 class="mb-4 text-4xl font-bold md:text-5xl">Fasilitas</h1>
                <p class="text-xl text-purple-100">
                    Fasilitas lengkap untuk mendukung proses pembelajaran
                </p>
            </div>
        </div>
    </div>

    {{-- Facilities --}}
    <div class="py-16">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if ($facilities->isNotEmpty())
                <div class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($facilities as $facility)
                        <div class="group overflow-hidden rounded-lg bg-white shadow-lg transition-transform duration-300 hover:scale-105">
                            {{-- Image --}}
                            <div class="aspect-video overflow-hidden bg-gray-200">
                                @if ($facility->image_path)
                                    <img 
                                        src="{{ Storage::url($facility->image_path) }}" 
                                        alt="{{ $facility->name }}"
                                        class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-110"
                                    >
                                @else
                                    <div class="flex h-full items-center justify-center bg-gray-100">
                                        <svg class="h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                        </svg>
                                    </div>
                                @endif
                            </div>

                            {{-- Content --}}
                            <div class="p-6">
                                <h3 class="mb-3 text-xl font-bold text-gray-900">{{ $facility->name }}</h3>
                                
                                @if ($facility->description)
                                    <p class="mb-4 text-gray-600 leading-relaxed">
                                        {{ $facility->description }}
                                    </p>
                                @endif

                                {{-- Specifications --}}
                                @if ($facility->specifications)
                                    <div class="rounded-lg bg-gray-50 p-4">
                                        <h4 class="mb-2 text-sm font-semibold text-gray-900">Spesifikasi:</h4>
                                        <div class="text-sm text-gray-700">
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
                <div class="py-16 text-center">
                    <svg class="mx-auto h-24 w-24 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    <h3 class="mt-4 text-xl font-medium text-gray-900">Belum Ada Data Fasilitas</h3>
                    <p class="mt-2 text-gray-600">Informasi fasilitas sedang dalam proses pembaruan.</p>
                </div>
            @endif
        </div>
    </div>
</div>