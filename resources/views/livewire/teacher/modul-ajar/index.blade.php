<?php

declare(strict_types=1);

namespace App\Livewire\Teacher\ModulAjar;

use App\Models\ModulAjar;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public function delete(int $id): void
    {
        $modul = ModulAjar::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $modul->delete();
        $this->js("toast('Berhasil menghapus modul ajar', { type: 'success' })");
    }

    public function with(): array
    {
        $query = ModulAjar::query();

        // If not admin, only show own modules
        if (!auth()->user()->isAdmin()) {
            $query->where('user_id', auth()->id());
        }

        return [
            'modules' => $query->latest()->paginate(10),
            'headers' => [
                ['key' => 'title', 'label' => 'Judul / Tema'],
                ['key' => 'subject', 'label' => 'Mata Pelajaran'],
                ['key' => 'class_level', 'label' => 'Kelas'],
                ['key' => 'status', 'label' => 'Status'],
                ['key' => 'created_at', 'label' => 'Dibuat Pada'],
            ],
        ];
    }
}; ?>

<div class="p-6 space-y-6">
    <x-ui.header title="Modul Ajar AI" subtitle="Generate modul ajar Kurikulum Merdeka secara otomatis dengan bantuan AI.">
        <x-slot:actions>
            <x-ui.button label="Buat Modul Baru" icon="o-sparkles" class="btn-primary" 
                link="{{ route('teacher.modul-ajar.create') }}" wire:navigate />
        </x-slot:actions>
    </x-ui.header>

    <x-ui.card shadow padding="false">
        <x-ui.table :headers="$headers" :rows="$modules">
            @scope('cell_title', $module)
                <div class="flex flex-col">
                    <span class="font-bold text-slate-800 dark:text-slate-200">{{ $module->title ?? 'Tanpa Judul' }}</span>
                    @if($module->description)
                        <span class="text-xs text-slate-500 line-clamp-1">{{ $module->description }}</span>
                    @endif
                </div>
            @endscope

            @scope('cell_status', $module)
                <x-ui.badge 
                    :label="match($module->status) {
                        'completed' => 'Selesai',
                        'generating' => 'Proses',
                        default => 'Draft'
                    }" 
                    :class="match($module->status) {
                        'completed' => 'badge-success',
                        'generating' => 'badge-info',
                        default => 'badge-ghost'
                    }" 
                />
            @endscope

            @scope('cell_created_at', $module)
                <span class="text-sm text-slate-600">{{ $module->created_at->format('d M Y, H:i') }}</span>
            @endscope

            @scope('actions', $module)
                <div class="flex items-center gap-2">
                    <x-ui.button icon="o-eye" link="{{ route('teacher.modul-ajar.show', $module->id) }}" wire:navigate ghost sm />
                    <x-ui.button icon="o-trash" wire:click="delete({{ $module->id }})" 
                        wire:confirm="Apakah Anda yakin ingin menghapus modul ini?"
                        class="text-error" ghost sm />
                </div>
            @endscope
        </x-ui.table>

        @if($modules->isEmpty())
            <div class="py-12 text-center text-slate-400">
                <x-ui.icon name="o-inbox" class="size-12 mx-auto mb-2 opacity-20" />
                <p>Belum ada modul ajar yang dibuat.</p>
                <x-ui.button label="Mulai Buat Sekarang" class="btn-sm btn-ghost mt-4" 
                    link="{{ route('teacher.modul-ajar.create') }}" wire:navigate />
            </div>
        @endif
    </x-ui.card>
</div>
