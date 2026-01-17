<?php

use App\Models\StaffMember;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.public')] class extends Component
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
    <div class="bg-gradient-to-r from-green-600 to-green-800 py-16 text-white">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h1 class="mb-4 text-4xl font-bold md:text-5xl">Struktur Organisasi</h1>
                <p class="text-xl text-green-100">
                    Tim pengajar dan tenaga kependidikan yang berpengalaman
                </p>
            </div>
        </div>
    </div>

    {{-- Staff Members --}}
    <div class="py-16">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if ($staffMembers->isNotEmpty())
                <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    @foreach ($staffMembers as $staff)
                        <div class="group overflow-hidden rounded-lg bg-white shadow-lg transition-transform duration-300 hover:scale-105">
                            {{-- Photo --}}
                            <div class="aspect-square overflow-hidden bg-gray-200">
                                @if ($staff->photo_path)
                                    <img 
                                        src="{{ Storage::url($staff->photo_path) }}" 
                                        alt="{{ $staff->name }}"
                                        class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-110"
                                    >
                                @else
                                    <div class="flex h-full items-center justify-center bg-gray-100">
                                        <svg class="h-20 w-20 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                    </div>
                                @endif
                            </div>

                            {{-- Info --}}
                            <div class="p-6">
                                <h3 class="mb-2 text-xl font-bold text-gray-900">{{ $staff->name }}</h3>
                                <p class="mb-3 text-sm font-medium text-blue-600">{{ $staff->position }}</p>
                                
                                @if ($staff->bio)
                                    <p class="text-sm leading-relaxed text-gray-600">
                                        {{ Str::limit($staff->bio, 120) }}
                                    </p>
                                @endif

                                {{-- Contact Info --}}
                                <div class="mt-4 space-y-2">
                                    @if ($staff->email)
                                        <div class="flex items-center gap-2 text-sm text-gray-600">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                            </svg>
                                            <a href="mailto:{{ $staff->email }}" class="hover:text-blue-600">
                                                {{ $staff->email }}
                                            </a>
                                        </div>
                                    @endif

                                    @if ($staff->phone)
                                        <div class="flex items-center gap-2 text-sm text-gray-600">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                            </svg>
                                            <a href="tel:{{ $staff->phone }}" class="hover:text-blue-600">
                                                {{ $staff->phone }}
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                {{-- Empty State --}}
                <div class="py-16 text-center">
                    <svg class="mx-auto h-24 w-24 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <h3 class="mt-4 text-xl font-medium text-gray-900">Belum Ada Data Staff</h3>
                    <p class="mt-2 text-gray-600">Informasi struktur organisasi sedang dalam proses pembaruan.</p>
                </div>
            @endif
        </div>
    </div>
</div>