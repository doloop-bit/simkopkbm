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

<div class="p-6">
    <x-header title="Pesan Kontak" subtitle="Kelola pesan dari formulir kontak website" separator>
        <x-slot:actions>
            @if($unreadCount > 0)
                <x-badge value="{{ $unreadCount }} belum dibaca" class="badge-warning" />
            @endif
        </x-slot:actions>
    </x-header>

    @if (session('message'))
        <x-alert title="Sukses" icon="o-check-circle" class="alert-success mb-6">
            {{ session('message') }}
        </x-alert>
    @endif

    @if($inquiries->count() > 0)
        <div class="space-y-4">
            @foreach($inquiries as $inquiry)
                <x-card 
                    wire:key="inquiry-{{ $inquiry->id }}"
                    class="border {{ $inquiry->is_read ? 'border-base-200 opacity-80' : 'border-primary/30 bg-primary/5 shadow-md' }}"
                    shadow
                >
                    <div class="flex flex-col md:flex-row md:items-start justify-between gap-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <h3 class="font-bold text-lg leading-none">{{ $inquiry->name }}</h3>
                                @if(!$inquiry->is_read)
                                    <x-badge label="Baru" class="badge-primary badge-sm" />
                                @endif
                            </div>
                            
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs opacity-60 mb-3 font-medium">
                                <div class="flex items-center gap-1">
                                    <x-icon name="o-envelope" class="size-3" />
                                    {{ $inquiry->email }}
                                </div>
                                @if($inquiry->phone)
                                    <div class="flex items-center gap-1">
                                        <x-icon name="o-phone" class="size-3" />
                                        {{ $inquiry->phone }}
                                    </div>
                                @endif
                                <div class="flex items-center gap-1">
                                    <x-icon name="o-clock" class="size-3" />
                                    {{ $inquiry->created_at->format('d M Y, H:i') }}
                                </div>
                            </div>
                            
                            @if($inquiry->subject)
                                <div class="font-bold text-sm mb-1 text-primary">{{ $inquiry->subject }}</div>
                            @endif
                            
                            <div class="text-sm bg-base-200/50 p-3 rounded-lg border border-base-200">
                                {{ $inquiry->message }}
                            </div>
                        </div>
                        
                        <div class="flex items-center md:flex-col gap-2 md:w-48">
                            @if($inquiry->is_read)
                                <x-button 
                                    label="Belum Dibaca"
                                    icon="o-eye-slash"
                                    class="btn-sm btn-ghost flex-1 md:w-full"
                                    wire:click="markAsUnread({{ $inquiry->id }})"
                                    wire:confirm="Tandai sebagai belum dibaca?"
                                />
                            @else
                                <x-button 
                                    label="Sudah Dibaca"
                                    icon="o-check-circle"
                                    class="btn-sm btn-primary flex-1 md:w-full"
                                    wire:click="markAsRead({{ $inquiry->id }})"
                                />
                            @endif
                            
                            <x-button 
                                label="Hapus"
                                icon="o-trash"
                                class="btn-sm btn-ghost text-error flex-1 md:w-full"
                                wire:click="delete({{ $inquiry->id }})"
                                wire:confirm="Yakin ingin menghapus pesan ini?"
                            />
                        </div>
                    </div>
                </x-card>
            @endforeach
        </div>

        <div class="mt-8">
            {{ $inquiries->links() }}
        </div>
    @else
        <x-card class="p-16 text-center" shadow>
            <x-icon name="o-inbox" class="size-16 mb-4 opacity-10 mx-auto" />
            <h3 class="font-bold text-xl opacity-50 mb-2">Belum ada pesan</h3>
            <p class="opacity-40">Pesan dari formulir kontak akan muncul di sini</p>
        </x-card>
    @endif
</div>

@section('title', 'Pesan Kontak')