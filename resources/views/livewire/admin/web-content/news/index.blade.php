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

<div class="p-6 space-y-8 text-slate-900 dark:text-white pb-24 md:pb-6">
    @if (session()->has('message'))
        <x-ui.alert :title="__('Sukses')" icon="o-check-circle" class="bg-emerald-50 text-emerald-800 border-emerald-100" dismissible>
            {{ session('message') }}
        </x-ui.alert>
    @endif

    <x-ui.header :title="__('Portal Berita')" :subtitle="__('Kelola publikasi artikel berita, informasi terkini, dan pengumuman sekolah.')" separator>
        <x-slot:actions>
            <x-ui.button :label="__('Tulis Berita Baru')" icon="o-plus" link="{{ route('admin.news.create') }}" wire:navigate class="btn-primary shadow-lg shadow-primary/20" />
        </x-slot:actions>
    </x-ui.header>

    {{-- Search and Filter --}}
    <x-ui.card shadow padding="false" class="border-none ring-1 ring-slate-100 dark:ring-slate-800">
        <div class="p-6 flex flex-col gap-6 md:flex-row md:items-end">
            <div class="flex-1">
                <x-ui.input 
                    wire:model.live.debounce.300ms="search" 
                    :label="__('Cari Artikel')"
                    :placeholder="__('Masukkan judul atau cuplikan isi...')"
                    icon="o-magnifying-glass"
                    clearable
                />
            </div>
            <div class="w-full md:w-64">
                <x-ui.select 
                    wire:model.live="statusFilter"
                    :label="__('Filter Status')"
                    :options="[
                        ['id' => 'all', 'name' => __('Semua Status')],
                        ['id' => 'draft', 'name' => __('Draft (Arsip)')],
                        ['id' => 'published', 'name' => __('Dipublikasikan (Live)')],
                    ]"
                />
            </div>
        </div>
    </x-ui.card>

    {{-- Articles Table --}}
    @if ($articles->count() > 0)
        <x-ui.card shadow padding="false" class="border-none ring-1 ring-slate-100 dark:ring-slate-800">
            <x-ui.table :rows="$articles" :headers="[
                ['key' => 'news_content', 'label' => __('Artikel')],
                ['key' => 'author.name', 'label' => __('Redaksi / Penulis')],
                ['key' => 'news_status', 'label' => __('Status'), 'class' => 'text-center'],
                ['key' => 'published_at', 'label' => __('Tgl Terbit'), 'class' => 'text-right'],
                ['key' => 'actions', 'label' => '']
            ]">
                @scope('cell_news_content', $article)
                    <div class="flex items-center gap-4 py-2">
                        @if ($article->featured_image_path)
                            <img 
                                src="{{ Storage::url($article->featured_image_path) }}" 
                                alt="{{ $article->title }}" 
                                class="h-16 w-16 rounded-2xl object-cover shadow-sm ring-1 ring-slate-100"
                            >
                        @else
                            <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-800 text-slate-300">
                                <x-ui.icon name="o-newspaper" class="size-6 opacity-30" />
                            </div>
                        @endif
                        <div class="flex flex-col min-w-0">
                            <span class="font-black text-slate-900 dark:text-white leading-tight mb-1 truncate group-hover:text-primary transition-colors italic uppercase tracking-tighter">{{ $article->title }}</span>
                            <span class="text-[10px] text-slate-400 italic line-clamp-1 leading-relaxed">{{ Str::limit($article->excerpt, 80) }}</span>
                        </div>
                    </div>
                @endscope

                @scope('cell_news_status', $article)
                    <div class="flex justify-center">
                        @if ($article->status === 'published')
                            <x-ui.badge :label="__('LIVE')" class="bg-emerald-50 text-emerald-600 border-none font-black italic text-[9px] px-3 shadow-sm ring-1 ring-emerald-100" />
                        @else
                            <x-ui.badge :label="__('DRAFT')" class="bg-slate-50 text-slate-400 border-none font-black italic text-[9px] px-3 ring-1 ring-slate-100" />
                        @endif
                    </div>
                @endscope

                @scope('cell_published_at', $article)
                    <div class="text-right">
                        <span class="text-[10px] font-bold text-slate-500 font-mono tracking-tighter">
                            {{ $article->published_at ? $article->published_at->format('d/m/Y') : '-' }}
                        </span>
                    </div>
                @endscope

                @scope('cell_actions', $article)
                    <div class="flex justify-end gap-1">
                        <x-ui.button icon="o-pencil" link="{{ route('admin.news.edit', $article->id) }}" wire:navigate class="btn-ghost btn-sm text-slate-400 hover:text-primary" />
                        <x-ui.button 
                            icon="o-trash" 
                            wire:click="deleteArticle({{ $article->id }})"
                            wire:confirm="{{ __('Hapus berita ini secara permanen?') }}"
                            class="btn-ghost btn-sm text-slate-400 hover:text-rose-500"
                        />
                    </div>
                @endscope
            </x-ui.table>
            
            <div class="p-6 border-t border-slate-50 dark:border-slate-800">
                {{ $articles->links() }}
            </div>
        </x-ui.card>
    @else
        <div class="flex flex-col items-center justify-center py-32 text-slate-300 dark:text-slate-700 border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-[32px] bg-slate-50/50 dark:bg-slate-900/50 transition-all text-center px-6">
            <x-ui.icon name="o-newspaper" class="size-20 mb-6 opacity-20" />
            <h3 class="font-black text-slate-500 uppercase tracking-widest italic mb-2">
                {{ ($search || $statusFilter !== 'all') ? __('Tidak Ditemukan Hasil') : __('Belum Ada Berita') }}
            </h3>
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest italic max-w-xs animate-pulse">
                {{ ($search || $statusFilter !== 'all') ? __('Coba ubah kata kunci atau filter pencarian Anda.') : __('Silakan tulis kabar berita atau pengumuman pertama Anda.') }}
            </p>
            @if (!$search && $statusFilter === 'all')
                <x-ui.button :label="__('Mulai Menulis')" link="{{ route('admin.news.create') }}" wire:navigate class="mt-8 btn-ghost text-primary btn-sm font-bold" />
            @endif
        </div>
    @endif
</div>
