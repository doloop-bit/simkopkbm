<?php

declare(strict_types=1);

namespace App\Livewire\Teacher\ModulAjar;

use App\Models\ModulAjar;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public int $id;

    public function mount(int $id): void
    {
        $this->id = $id;
    }

    public function with(): array
    {
        $modul = ModulAjar::where('id', $this->id)
            ->when(!auth()->user()->isAdmin(), fn($q) => $q->where('user_id', auth()->id()))
            ->firstOrFail();

        return [
            'module' => $modul,
        ];
    }
}; ?>

<div class="p-6 max-w-5xl mx-auto space-y-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <x-ui.button label="Kembali" icon="o-arrow-left" class="btn-sm btn-ghost mb-2" 
                link="{{ route('teacher.modul-ajar.index') }}" wire:navigate />
            <x-ui.header :title="$module->title" :subtitle="$module->subject . ' - ' . $module->class_level" />
        </div>
        
        <div class="flex items-center gap-2">
            <x-ui.button label="Cetak / PDF" icon="o-printer" class="btn-primary" 
                onclick="window.print()" />
        </div>
    </div>

    <x-ui.card shadow class="bg-white dark:bg-slate-900 border-none print:shadow-none print:p-0">
        <div class="prose prose-blue lg:prose-lg dark:prose-invert max-w-none prose-table:border prose-table:border-collapse prose-th:border prose-td:border prose-th:bg-zinc-50 prose-th:p-2 prose-td:p-2 dark:prose-th:bg-zinc-800">
            {!! \Illuminate\Support\Str::markdown($module->generated_content ?? 'Konten belum di-generate.') !!}
        </div>
    </x-ui.card>

    @if(!$module->generated_content)
        <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-800/30 p-6 rounded-3xl text-center">
            <x-ui.icon name="o-exclamation-triangle" class="size-12 mx-auto text-amber-500 mb-4" />
            <h3 class="font-bold text-amber-800 dark:text-amber-200">Modul Belum Selesai</h3>
            <p class="text-sm text-amber-700 dark:text-amber-300 mb-4">Penyusunan modul ini belum selesai atau terjadi kegagalan saat proses generate.</p>
            <x-ui.button label="Lanjutkan Diskusi di Chat" icon="o-chat-bubble-left-right" class="btn-primary"
                link="{{ route('teacher.modul-ajar.create', ['id' => $module->id]) }}" wire:navigate />
        </div>
    @endif
</div>

<style>
    @media print {
        body {
            background-color: white !important;
        }
        .print\:hidden {
            display: none !important;
        }
        aside, nav, header, button, .flux-sidebar, .flux-navbar {
            display: none !important;
        }
        .max-w-5xl {
            max-width: 100% !important;
            padding: 0 !important;
            margin: 0 !important;
        }
    }
</style>
