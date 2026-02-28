<?php

use App\Models\ContactInquiry;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use Livewire\Component;

new #[Layout('components.admin.layouts.app')] class extends Component {
    use WithPagination;

    public function markAsRead($id): void
    {
        ContactInquiry::findOrFail($id)->update(['is_read' => true]);
        
        session()->flash('message', 'Pesan telah ditandai sebagai sudah dibaca.');
    }

    public function markAsUnread($id): void
    {
        ContactInquiry::findOrFail($id)->update(['is_read' => false]);
        
        session()->flash('message', 'Pesan telah ditandai sebagai belum dibaca.');
    }

    public function delete($id): void
    {
        ContactInquiry::findOrFail($id)->delete();
        
        session()->flash('message', 'Pesan berhasil dihapus.');
    }

    public function with(): array
    {
        return [
            'inquiries' => ContactInquiry::latest()->paginate(10),
            'unreadCount' => ContactInquiry::unread()->count(),
        ];
    }
}; ?>

<div class="p-6 space-y-8 text-slate-900 dark:text-white pb-24 md:pb-6">
    <x-ui.header :title="__('Kotak Masuk Kontak')" :subtitle="__('Kelola pesan, aspirasi, dan pertanyaan dari formulir kontak website.')" separator>
        <x-slot:actions>
            @if($unreadCount > 0)
                <x-ui.badge :label="$unreadCount . ' ' . __('Belum Dibaca')" class="bg-amber-500 text-white border-none font-black italic text-[10px] px-4 shadow-lg shadow-amber-500/20 shadow-sm ring-4 ring-amber-500/10 animate-pulse" />
            @endif
        </x-slot:actions>
    </x-ui.header>

    @if (session('message'))
        <x-ui.alert :title="__('Sukses')" icon="o-check-circle" class="bg-emerald-50 text-emerald-800 border-emerald-100" dismissible>
            {{ session('message') }}
        </x-ui.alert>
    @endif

    @if($inquiries->count() > 0)
        <div class="space-y-6">
            @foreach($inquiries as $inquiry)
                <x-ui.card 
                    wire:key="inquiry-{{ $inquiry->id }}"
                    shadow="false"
                    padding="false"
                    class="border-none ring-1 {{ $inquiry->is_read ? 'ring-slate-100 dark:ring-slate-800 bg-white/50 dark:bg-slate-900/50 grayscale-[0.5] opacity-80' : 'ring-primary/20 bg-white shadow-xl shadow-primary/5' }}"
                >
                    <div class="flex flex-col md:flex-row md:items-stretch min-h-[160px]">
                        {{-- Status Indicator Bar --}}
                        <div class="w-1.5 {{ $inquiry->is_read ? 'bg-slate-200 dark:bg-slate-700' : 'bg-primary shadow-[0_0_15px_rgba(var(--color-primary),0.5)]' }}"></div>

                        <div class="flex-1 p-8">
                            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-6">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-3">
                                        <h3 class="font-black text-xl text-slate-900 dark:text-white uppercase tracking-tighter italic">{{ $inquiry->name }}</h3>
                                        @if(!$inquiry->is_read)
                                            <x-ui.badge :label="__('BARU')" class="bg-indigo-50 text-indigo-600 border-none font-black italic text-[9px] px-3 shadow-sm ring-1 ring-indigo-100" />
                                        @endif
                                    </div>
                                    <div class="flex flex-wrap items-center gap-x-6 gap-y-2">
                                        <div class="flex items-center gap-2 group cursor-pointer">
                                            <div class="size-6 rounded-lg bg-slate-50 dark:bg-slate-800 flex items-center justify-center border border-slate-100 dark:border-slate-700">
                                                <x-ui.icon name="o-envelope" class="size-3 text-slate-400 group-hover:text-primary transition-colors" />
                                            </div>
                                            <span class="text-[10px] font-bold text-slate-500 font-mono tracking-tighter">{{ $inquiry->email }}</span>
                                        </div>
                                        @if($inquiry->phone)
                                            <div class="flex items-center gap-2 group cursor-pointer">
                                                <div class="size-6 rounded-lg bg-slate-50 dark:bg-slate-800 flex items-center justify-center border border-slate-100 dark:border-slate-700">
                                                    <x-ui.icon name="o-phone" class="size-3 text-slate-400 group-hover:text-emerald-500 transition-colors" />
                                                </div>
                                                <span class="text-[10px] font-bold text-slate-500 font-mono tracking-tighter">{{ $inquiry->phone }}</span>
                                            </div>
                                        @endif
                                        <div class="flex items-center gap-2">
                                            <div class="size-6 rounded-lg bg-slate-50 dark:bg-slate-800 flex items-center justify-center border border-slate-100 dark:border-slate-700">
                                                <x-ui.icon name="o-clock" class="size-3 text-slate-400" />
                                            </div>
                                            <span class="text-[10px] font-bold text-slate-400 font-mono tracking-tighter italic uppercase text-slate-400">{{ $inquiry->created_at->format('d/m/Y - H:i') }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 bg-slate-50 dark:bg-slate-900/50 p-2 rounded-2xl border border-slate-100 dark:border-slate-800">
                                    @if($inquiry->is_read)
                                        <x-ui.button 
                                            :label="__('Tandai Belum Dibaca')"
                                            icon="o-eye-slash"
                                            class="btn-ghost btn-xs text-slate-400 hover:text-indigo-500 font-bold italic"
                                            wire:click="markAsUnread({{ $inquiry->id }})"
                                        />
                                    @else
                                        <x-ui.button 
                                            :label="__('Selesaikan Pesan')"
                                            icon="o-check-circle"
                                            class="btn-ghost btn-xs text-primary font-black italic uppercase tracking-tighter"
                                            wire:click="markAsRead({{ $inquiry->id }})"
                                        />
                                    @endif
                                    
                                    <div class="w-px h-4 bg-slate-200 dark:bg-slate-700 mx-1"></div>

                                    <x-ui.button 
                                        icon="o-trash"
                                        class="btn-ghost btn-xs text-slate-300 hover:text-rose-500"
                                        wire:click="delete({{ $inquiry->id }})"
                                        wire:confirm="__('Hapus permanen pesan dari archive?')"
                                    />
                                </div>
                            </div>
                            
                            <div class="relative">
                                @if($inquiry->subject)
                                    <div class="font-black text-xs mb-3 text-indigo-600 uppercase tracking-widest italic flex items-center gap-2">
                                        <div class="h-px w-4 bg-indigo-200"></div>
                                        {{ $inquiry->subject }}
                                    </div>
                                @endif
                                
                                <div class="text-sm font-medium text-slate-700 dark:text-slate-300 bg-slate-50 dark:bg-slate-900/50 p-6 rounded-[2rem] border border-slate-100 dark:border-slate-800 italic leading-relaxed shadow-inner">
                                    {{ $inquiry->message }}
                                </div>

                                <div class="absolute -bottom-2 -right-2 opacity-10 blur-sm pointer-events-none">
                                    <x-ui.icon name="o-chat-bubble-bottom-center-text" class="size-24 text-slate-900" />
                                </div>
                            </div>
                        </div>
                    </div>
                </x-ui.card>
            @endforeach
        </div>

        <div class="mt-12 bg-white dark:bg-slate-900 p-6 rounded-3xl ring-1 ring-slate-100 dark:ring-slate-800">
            {{ $inquiries->links() }}
        </div>
    @else
        <div class="flex flex-col items-center justify-center py-32 text-slate-300 dark:text-slate-700 border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-[32px] bg-slate-50/50 dark:bg-slate-900/50 transition-all text-center px-6">
            <x-ui.icon name="o-inbox" class="size-20 mb-6 opacity-20" />
            <h3 class="font-black text-slate-500 uppercase tracking-widest italic mb-2">{{ __('Arsip Pesan Kosong') }}</h3>
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest italic animate-pulse">{{ __('Semua kotak masuk formulir kontak akan dihimpun di sini.') }}</p>
        </div>
    @endif
</div>

@section('title', __('Manajemen Pesan Masuk'))

@section('title', 'Pesan Kontak')