<?php

declare(strict_types=1);

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.admin.layouts.app')] class extends Component {
    public string $currentTab = 'grading';

    public function mount(): void
    {
        // Check if there's a tab parameter in the URL
        $tab = request()->query('tab');
        if ($tab && in_array($tab, ['grading', 'create'])) {
            $this->currentTab = $tab;
        }
    }

    public function switchTab(string $tab): void
    {
        $this->currentTab = $tab;
    }

    public function with(): array
    {
        return [
            'tabs' => [
                'grading' => [
                    'label' => 'Input Nilai & TP',
                    'icon' => 'clipboard-document-list',
                    'route' => 'admin.report-card.grading',
                    'description' => 'Input nilai akhir dan deskripsi TP untuk rapor',
                ],
                'create' => [
                    'label' => 'Buat Rapor',
                    'icon' => 'document-text',
                    'route' => 'admin.report-card.create',
                    'description' => 'Buat dan kelola rapor siswa berdasarkan data penilaian',
                ],
            ],
        ];
    }
}; ?>

<div class="min-h-screen pb-20 md:pb-0">
    {{-- Navigation Component --}}
    <x-admin.report-card-nav />

    {{-- Header Section (Desktop Only) --}}
    <div class="hidden md:block bg-white dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700 -mt-6 mb-6">
        <div class="p-6">
            <div class="flex items-center justify-between">
                <div>
                    <flux:heading size="xl" level="1">{{ __('Raport & Penilaian') }}</flux:heading>
                    <flux:subheading>{{ __('Kelola nilai dan rapor siswa') }}</flux:subheading>
                </div>
            </div>
        </div>
    </div>

    {{-- Content Area --}}
    <div class="p-6">
        <div class="max-w-7xl mx-auto">
            {{-- Mobile Header --}}
            <div class="md:hidden mb-6">
                <flux:heading size="xl" level="1">{{ __('Raport & Penilaian') }}</flux:heading>
                <flux:subheading>{{ __('Kelola nilai dan rapor siswa') }}</flux:subheading>
            </div>

            {{-- Info Card --}}
            <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-900 rounded-xl">
                <div class="flex items-start gap-3">
                    <flux:icon icon="information-circle" class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5" />
                    <div class="flex-1">
                        <p class="text-sm font-medium text-blue-900 dark:text-blue-200 mb-1">
                            Navigasi Raport & Penilaian
                        </p>
                        <p class="text-sm text-blue-800 dark:text-blue-300">
                            <span class="hidden md:inline">Gunakan menu navigasi di atas untuk beralih antara</span>
                            <span class="md:hidden">Gunakan menu navigasi di bawah layar untuk beralih antara</span>
                            <strong>Input Nilai & TP</strong> dan <strong>Buat Rapor</strong>.
                            Pastikan semua nilai sudah diinput sebelum membuat rapor.
                        </p>
                    </div>
                </div>
            </div>

            {{-- Quick Links --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach($tabs as $key => $tab)
                    <a href="{{ route($tab['route']) }}" wire:navigate.hover
                        class="group p-6 bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 hover:border-blue-500 dark:hover:border-blue-500 transition-all hover:shadow-lg">
                        <div class="flex items-start gap-4">
                            <div
                                class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg group-hover:bg-blue-100 dark:group-hover:bg-blue-900/40 transition-colors">
                                <flux:icon :icon="$tab['icon']" class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                            </div>
                            <div class="flex-1">
                                <h3
                                    class="text-lg font-semibold text-zinc-900 dark:text-white mb-2 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                                    {{ $tab['label'] }}
                                </h3>
                                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                                    {{ $tab['description'] }}
                                </p>
                                <div class="mt-4 flex items-center text-sm text-blue-600 dark:text-blue-400 font-medium">
                                    Buka halaman
                                    <flux:icon icon="arrow-right"
                                        class="w-4 h-4 ml-1 group-hover:translate-x-1 transition-transform" />
                                </div>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            {{-- Additional Info Section --}}
            <div class="mt-8 p-6 bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700">
                <flux:heading size="lg" class="mb-4">Panduan Penggunaan</flux:heading>
                <div class="space-y-4">
                    <div class="flex items-start gap-3">
                        <div
                            class="flex-shrink-0 w-8 h-8 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center">
                            <span class="text-sm font-bold text-blue-600 dark:text-blue-400">1</span>
                        </div>
                        <div>
                            <p class="font-medium text-zinc-900 dark:text-white">Input Nilai & TP</p>
                            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                                Masukkan nilai akhir (0-100) dan pilih TP terbaik serta TP yang perlu peningkatan untuk
                                setiap siswa per mata pelajaran.
                            </p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <div
                            class="flex-shrink-0 w-8 h-8 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center">
                            <span class="text-sm font-bold text-blue-600 dark:text-blue-400">2</span>
                        </div>
                        <div>
                            <p class="font-medium text-zinc-900 dark:text-white">Buat Rapor</p>
                            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                                Setelah semua nilai diinput, buat rapor dengan memilih tahun ajaran, kelas, semester,
                                dan siswa yang akan dibuatkan rapornya.
                            </p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <div
                            class="flex-shrink-0 w-8 h-8 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center">
                            <span class="text-sm font-bold text-blue-600 dark:text-blue-400">3</span>
                        </div>
                        <div>
                            <p class="font-medium text-zinc-900 dark:text-white">Preview & Download</p>
                            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                                Gunakan tombol preview untuk melihat rapor sebelum dicetak, lalu download dalam format
                                PDF.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>