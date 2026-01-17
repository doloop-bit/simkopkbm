<?php

use App\Models\NewsArticle;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Component;
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

<div>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl">Berita</flux:heading>
            <flux:subheading>Kelola artikel berita dan pengumuman</flux:subheading>
        </div>
        <flux:button variant="primary" href="{{ route('admin.news.create') }}" wire:navigate>
            Tambah Berita
        </flux:button>
    </div>

    @if (session()->has('message'))
        <flux:callout color="green" icon="check-circle" class="mb-6">
            {{ session('message') }}
        </flux:callout>
    @endif

    {{-- Search and Filter --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row">
        <div class="flex-1">
            <flux:input 
                wire:model.live.debounce.300ms="search" 
                type="text" 
                placeholder="Cari artikel..."
                icon="magnifying-glass"
            />
        </div>
        <div class="w-full sm:w-48">
            <flux:select wire:model.live="statusFilter">
                <option value="all">Semua Status</option>
                <option value="draft">Draft</option>
                <option value="published">Dipublikasikan</option>
            </flux:select>
        </div>
    </div>

    {{-- Articles Table --}}
    @if ($articles->count() > 0)
        <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-700 dark:text-zinc-300">
                                Judul
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-700 dark:text-zinc-300">
                                Penulis
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-700 dark:text-zinc-300">
                                Status
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-700 dark:text-zinc-300">
                                Tanggal Publikasi
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-700 dark:text-zinc-300">
                                Aksi
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach ($articles as $article)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        @if ($article->featured_image_path)
                                            <img 
                                                src="{{ Storage::url($article->featured_image_path) }}" 
                                                alt="{{ $article->title }}" 
                                                class="h-12 w-12 rounded object-cover"
                                            >
                                        @else
                                            <div class="flex h-12 w-12 items-center justify-center rounded bg-zinc-100 dark:bg-zinc-700">
                                                <flux:icon.newspaper class="h-6 w-6 text-zinc-400" />
                                            </div>
                                        @endif
                                        <div class="flex-1">
                                            <flux:text class="font-medium">{{ $article->title }}</flux:text>
                                            @if ($article->excerpt)
                                                <flux:text class="mt-1 text-xs text-zinc-500">
                                                    {{ Str::limit($article->excerpt, 60) }}
                                                </flux:text>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <flux:text class="text-sm">{{ $article->author->name }}</flux:text>
                                </td>
                                <td class="px-6 py-4">
                                    @if ($article->status === 'published')
                                        <flux:badge color="green" size="sm">Dipublikasikan</flux:badge>
                                    @else
                                        <flux:badge color="zinc" size="sm">Draft</flux:badge>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <flux:text class="text-sm">
                                        {{ $article->published_at ? $article->published_at->format('d M Y') : '-' }}
                                    </flux:text>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <flux:button 
                                            variant="ghost" 
                                            size="sm"
                                            href="{{ route('admin.news.edit', $article->id) }}"
                                            wire:navigate
                                        >
                                            Edit
                                        </flux:button>
                                        <flux:button 
                                            variant="danger" 
                                            size="sm"
                                            wire:click="deleteArticle({{ $article->id }})"
                                            wire:confirm="Apakah Anda yakin ingin menghapus artikel ini?"
                                        >
                                            Hapus
                                        </flux:button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Pagination --}}
        <div class="mt-6">
            {{ $articles->links() }}
        </div>
    @else
        <flux:callout color="zinc" icon="information-circle">
            @if ($search || $statusFilter !== 'all')
                Tidak ada artikel yang sesuai dengan pencarian atau filter Anda.
            @else
                Belum ada artikel berita. Klik tombol "Tambah Berita" untuk membuat artikel baru.
            @endif
        </flux:callout>
    @endif
</div>
