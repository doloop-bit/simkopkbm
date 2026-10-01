<?php

declare(strict_types=1);

use App\Models\OnlineMaterial;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('components.layouts.student')] class extends Component {
    public OnlineMaterial $material;

    public function mount(int $materialId): void
    {
        $this->material = OnlineMaterial::where('is_published', true)
            ->with(['subject', 'classroom', 'academicYear', 'creator'])
            ->findOrFail($materialId);
    }
}; ?>

<div class="p-6 space-y-6">
    <x-ui.header
        :title="$material->title"
        :subtitle="$material->subject?->name . ' — ' . $material->classroom?->name"
        separator
    >
        <x-slot:actions>
            <x-ui.button :label="__('Kembali')" icon="o-arrow-left" :link="route('student.materials')" ghost />
        </x-slot:actions>
    </x-ui.header>

    {{-- Material Info --}}
    <div class="flex flex-wrap gap-3 text-sm text-slate-500">
        <span class="flex items-center gap-1">
            <x-ui.icon name="o-calendar" class="w-4 h-4" />
            {{ $material->published_at?->format('d M Y') }}
        </span>
        <span class="flex items-center gap-1">
            <x-ui.icon name="o-academic-cap" class="w-4 h-4" />
            {{ $material->subject?->name }}
        </span>
        <span class="flex items-center gap-1">
            <x-ui.icon name="o-building-office" class="w-4 h-4" />
            {{ $material->classroom?->name }}
        </span>
    </div>

    {{-- Content --}}
    <x-ui.card shadow>
        <div class="prose dark:prose-invert max-w-none whitespace-pre-line">
            {!! nl2br(e($material->content)) !!}
        </div>
    </x-ui.card>

    {{-- Attachments --}}
    @if($material->attachments && count($material->attachments) > 0)
        <x-ui.card shadow>
            <h3 class="font-semibold text-slate-900 dark:text-white mb-3">
                <x-ui.icon name="o-paper-clip" class="w-5 h-5 inline" />
                {{ __('File Lampiran') }}
            </h3>
            <div class="space-y-2">
                @foreach($material->attachments as $attachment)
                    <a href="{{ Storage::url($attachment['path']) }}" target="_blank" class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-800 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                        <div class="flex items-center gap-3">
                            <x-ui.icon name="o-document" class="w-5 h-5 text-emerald-600" />
                            <div>
                                <span class="font-medium text-slate-900 dark:text-white">{{ $attachment['name'] }}</span>
                                <div class="text-xs text-slate-500">{{ number_format(($attachment['size'] ?? 0) / 1024, 1) }} KB</div>
                            </div>
                        </div>
                        <x-ui.icon name="o-arrow-down-tray" class="w-5 h-5 text-slate-400" />
                    </a>
                @endforeach
            </div>
        </x-ui.card>
    @endif
</div>
