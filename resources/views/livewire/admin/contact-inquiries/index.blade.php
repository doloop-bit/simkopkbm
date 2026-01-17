<?php

use App\Models\ContactInquiry;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use Livewire\Volt\Component;

new class extends Component {
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

<div>
    @if (session('message'))
        <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
            <div class="text-green-800">{{ session('message') }}</div>
        </div>
    @endif

    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl">Pesan Kontak</flux:heading>
            <flux:subheading>Kelola pesan dari formulir kontak website</flux:subheading>
        </div>
        
        @if($unreadCount > 0)
            <flux:badge size="sm" class="bg-yellow-100 text-yellow-800">
                {{ $unreadCount }} pesan belum dibaca
            </flux:badge>
        @endif
    </div>

    @if($inquiries->count() > 0)
        <div class="space-y-4">
            @foreach($inquiries as $inquiry)
                <div class="bg-white rounded-lg border {{ $inquiry->is_read ? 'border-gray-200' : 'border-blue-200 bg-blue-50' }} p-6">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <flux:heading size="sm">{{ $inquiry->name }}</flux:heading>
                                @if(!$inquiry->is_read)
                                    <flux:badge size="sm" class="bg-blue-100 text-blue-800">Baru</flux:badge>
                                @endif
                            </div>
                            
                            <div class="text-sm text-gray-600 mb-3">
                                <div class="flex items-center gap-4">
                                    <span>📧 {{ $inquiry->email }}</span>
                                    @if($inquiry->phone)
                                        <span>📱 {{ $inquiry->phone }}</span>
                                    @endif
                                    <span>🕒 {{ $inquiry->created_at->format('d M Y, H:i') }}</span>
                                </div>
                            </div>
                            
                            @if($inquiry->subject)
                                <flux:text class="font-medium mb-2">{{ $inquiry->subject }}</flux:text>
                            @endif
                            
                            <flux:text class="text-gray-700">{{ $inquiry->message }}</flux:text>
                        </div>
                        
                        <div class="flex items-center gap-2 ml-4">
                            @if($inquiry->is_read)
                                <flux:button 
                                    variant="ghost" 
                                    size="sm"
                                    wire:click="markAsUnread({{ $inquiry->id }})"
                                    wire:confirm="Tandai sebagai belum dibaca?"
                                >
                                    Tandai Belum Dibaca
                                </flux:button>
                            @else
                                <flux:button 
                                    class="bg-blue-600 text-white hover:bg-blue-700" 
                                    size="sm"
                                    wire:click="markAsRead({{ $inquiry->id }})"
                                >
                                    Tandai Sudah Dibaca
                                </flux:button>
                            @endif
                            
                            <flux:button 
                                class="bg-red-600 text-white hover:bg-red-700" 
                                size="sm"
                                wire:click="delete({{ $inquiry->id }})"
                                wire:confirm="Yakin ingin menghapus pesan ini?"
                            >
                                Hapus
                            </flux:button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $inquiries->links() }}
        </div>
    @else
        <div class="text-center py-12">
            <div class="text-gray-400 text-6xl mb-4">📬</div>
            <flux:heading size="lg" class="text-gray-600 mb-2">Belum ada pesan</flux:heading>
            <flux:text class="text-gray-500">Pesan dari formulir kontak akan muncul di sini</flux:text>
        </div>
    @endif
</div>

@section('title', 'Pesan Kontak')