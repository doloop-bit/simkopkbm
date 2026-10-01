<?php

declare(strict_types=1);

use App\Models\OnlineMaterial;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.student')] class extends Component {
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        $student = auth()->user();
        $classroomId = $student->latestProfile?->profileable?->classroom_id;

        $materials = OnlineMaterial::query()
            ->where('is_published', true)
            ->when($classroomId, fn ($q) => $q->where('classroom_id', $classroomId))
            ->when($this->search, fn ($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->with(['subject', 'classroom'])
            ->orderBy('order')
            ->latest('published_at')
            ->paginate(12);

        return [
            'materials' => $materials,
        ];
    }
}; ?>

<div class="p-6 space-y-6">
    <x-ui.header :title="__('Materi Pembelajaran')" :subtitle="__('Daftar materi yang tersedia untuk Anda.')" separator />

    <x-ui.input wire:model.live.debounce.300ms="search" :placeholder="__('Cari materi...')" icon="o-magnifying-glass" />

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($materials as $material)
            <a href="{{ route('student.material-detail', $material->id) }}" wire:navigate class="block">
                <x-ui.card shadow class="hover:shadow-lg transition-shadow h-full">
                    <div class="space-y-2">
                        <div class="flex items-center gap-2">
                            <x-ui.icon name="o-book-open" class="w-5 h-5 text-emerald-600" />
                            <x-ui.badge :label="$material->subject?->name" flat size="xs" />
                        </div>
                        <h3 class="font-semibold text-slate-900 dark:text-white">{{ $material->title }}</h3>
                        <p class="text-sm text-slate-500 line-clamp-2">{{ Str::limit(strip_tags($material->content), 100) }}</p>
                        <div class="flex items-center justify-between text-xs text-slate-400 pt-2">
                            <span>{{ $material->classroom?->name }}</span>
                            @if($material->attachments && count($material->attachments) > 0)
                                <span class="flex items-center gap-1">
                                    <x-ui.icon name="o-paper-clip" class="w-3 h-3" />
                                    {{ count($material->attachments) }} file
                                </span>
                            @endif
                        </div>
                    </div>
                </x-ui.card>
            </a>
        @empty
            <div class="col-span-full text-center py-12 text-slate-500">
                <x-ui.icon name="o-book-open" class="w-12 h-12 mx-auto mb-3 text-slate-300" />
                <p>{{ __('Belum ada materi tersedia.') }}</p>
            </div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $materials->links() }}
    </div>
</div>
