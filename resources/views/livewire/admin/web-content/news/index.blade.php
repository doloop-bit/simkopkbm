<?php

use App\Models\NewsArticle;
use App\Services\CacheService;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $statusFilter = 'all';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function deleteArticle(int $id): void
    {
        $article = NewsArticle::findOrFail($id);
        
        // Delete associated images
        if ($article->featured_image_path) {
            Storage::disk('public')->delete($article->featured_image_path);
        }
        
        $article->delete();
        
        // Clear news cache after deletion
        $cacheService = app(CacheService::class);
        $cacheService->clearNewsCache();
        
        session()->flash('message', 'Artikel berhasil dihapus.');
    }

    public function with(): array
    {
        $query = NewsArticle::with('author')->latestPublished();

        if ($this->search) {
            $query->where('title', 'like', "%{$this->search}%");
        }

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        return [
            'articles' => $query->paginate(15),
        ];
    }
}; ?>

<div class="p-6">
    <x-header title="Berita" subtitle="Kelola artikel berita dan pengumuman" separator>
        <x-slot:actions>
            <x-button label="Tambah Berita" icon="o-plus" link="{{ route('admin.news.create') }}" wire:navigate class="btn-primary" />
        </x-slot:actions>
    </x-header>

    @if (session()->has('message'))
        <x-alert title="Sukses" icon="o-check-circle" class="alert-success mb-6">
            {{ session('message') }}
        </x-alert>
    @endif

    {{-- Search and Filter --}}
    <x-card class="mb-6 bg-base-100 border border-base-200" shadow>
        <div class="flex flex-col gap-4 sm:flex-row items-end">
            <div class="flex-1">
                <x-input 
                    wire:model.live.debounce.300ms="search" 
                    label="Cari Artikel"
                    placeholder="Judul atau isi..."
                    icon="o-magnifying-glass"
                    inline
                    clearable
                />
            </div>
            <div class="w-full sm:w-48">
                <x-select 
                    wire:model.live="statusFilter"
                    label="Status"
                    :options="[
                        ['id' => 'all', 'name' => 'Semua Status'],
                        ['id' => 'draft', 'name' => 'Draft'],
                        ['id' => 'published', 'name' => 'Dipublikasikan'],
                    ]"
                    inline
                />
            </div>
        </div>
    </x-card>

    {{-- Articles Table --}}
    @if ($articles->count() > 0)
        <x-card shadow class="border border-base-200 overflow-hidden" no-separator padding="p-0">
            <x-table :rows="$articles" :headers="[['key' => 'title', 'label' => 'Artikel'], ['key' => 'author.name', 'label' => 'Penulis'], ['key' => 'status', 'label' => 'Status'], ['key' => 'published_at', 'label' => 'Tanggal', 'class' => 'text-center'], ['key' => 'actions', 'label' => '', 'sortable' => false]]" with-pagination>
                @scope('cell_title', $article)
                    <div class="flex items-center gap-3 py-1">
                        @if ($article->featured_image_path)
                            <img 
                                src="{{ Storage::url($article->featured_image_path) }}" 
                                alt="{{ $article->title }}" 
                                class="h-12 w-16 rounded object-cover shadow-sm"
                            >
                        @else
                            <div class="flex h-12 w-16 items-center justify-center rounded bg-base-200">
                                <x-icon name="o-newspaper" class="size-6 opacity-30" />
                            </div>
                        @endif
                        <div class="flex flex-col">
                            <span class="font-bold text-base leading-tight">{{ $article->title }}</span>
                            <span class="text-xs opacity-50">{{ Str::limit($article->excerpt, 60) }}</span>
                        </div>
                    </div>
                @endscope

                @scope('cell_status', $article)
                    @if ($article->status === 'published')
                        <x-badge label="Live" class="badge-success badge-sm shadow-sm" />
                    @else
                        <x-badge label="Draft" class="badge-ghost badge-sm border-base-300" />
                    @endif
                @endscope

                @scope('cell_published_at', $article)
                    <span class="text-sm opacity-60">
                        {{ $article->published_at ? $article->published_at->format('d M Y') : '-' }}
                    </span>
                @endscope

                @scope('cell_actions', $article)
                    <div class="flex justify-end gap-1">
                        <x-button icon="o-pencil" link="{{ route('admin.news.edit', $article->id) }}" wire:navigate class="btn-sm btn-ghost" tooltip="Edit" />
                        <x-button 
                            icon="o-trash" 
                            wire:click="deleteArticle({{ $article->id }})"
                            wire:confirm="Hapus artikel ini?"
                            class="btn-sm btn-ghost text-error"
                            tooltip="Hapus"
                        />
                    </div>
                @endscope
            </x-table>
        </x-card>
    @else
        <x-card class="p-16 text-center" shadow>
            <x-icon name="o-newspaper" class="size-16 mb-4 opacity-10 mx-auto" />
            <h3 class="font-bold text-xl opacity-50 mb-2">
                {{ ($search || $statusFilter !== 'all') ? 'Tidak ada hasil' : 'Belum ada berita' }}
            </h3>
            <p class="opacity-40">
                {{ ($search || $statusFilter !== 'all') ? 'Coba ubah kata kunci atau filter Anda.' : 'Klik tombol "Tambah Berita" untuk membuat artikel pertama.' }}
            </p>
        </x-card>
    @endif
</div>
