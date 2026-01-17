<?php

use App\Models\SchoolProfile;
use App\Models\Facility;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public ?SchoolProfile $profile = null;
    public array $facilities = [];
    
    // Form fields for adding/editing facility
    public ?int $editingId = null;
    public string $name = '';
    public string $description = '';
    public $image;
    public ?string $currentImagePath = null;
    public bool $showForm = false;

    public function mount(): void
    {
        $this->profile = SchoolProfile::active();
        
        if (!$this->profile) {
            session()->flash('error', 'Profil sekolah belum dibuat. Silakan buat profil sekolah terlebih dahulu.');
            return;
        }

        $this->loadFacilities();
    }

    public function loadFacilities(): void
    {
        if ($this->profile) {
            $this->facilities = $this->profile->facilities()
                ->orderBy('order')
                ->get()
                ->toArray();
        }
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'image' => $this->editingId ? 'nullable|image|mimes:jpeg,jpg,png,webp|max:5120' : 'nullable|image|mimes:jpeg,jpg,png,webp|max:5120',
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'name' => 'nama fasilitas',
            'description' => 'deskripsi',
            'image' => 'gambar',
        ];
    }

    public function showAddForm(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $facility = Facility::findOrFail($id);
        
        $this->editingId = $facility->id;
        $this->name = $facility->name;
        $this->description = $facility->description ?? '';
        $this->currentImagePath = $facility->image_path;
        $this->showForm = true;
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->editingId) {
            // Update existing facility
            $facility = Facility::findOrFail($this->editingId);
            
            // Handle image upload
            if ($this->image) {
                // Delete old image if exists
                if ($facility->image_path) {
                    Storage::disk('public')->delete($facility->image_path);
                }
                
                $path = $this->image->store('facilities', 'public');
                $facility->image_path = $path;
            }
            
            $facility->name = $this->name;
            $facility->description = $this->description;
            $facility->save();
            
            session()->flash('message', 'Fasilitas berhasil diperbarui.');
        } else {
            // Create new facility
            $maxOrder = Facility::where('school_profile_id', $this->profile->id)->max('order') ?? 0;
            
            $data = [
                'school_profile_id' => $this->profile->id,
                'name' => $this->name,
                'description' => $this->description,
                'order' => $maxOrder + 1,
            ];
            
            // Handle image upload
            if ($this->image) {
                $path = $this->image->store('facilities', 'public');
                $data['image_path'] = $path;
            }
            
            Facility::create($data);
            
            session()->flash('message', 'Fasilitas berhasil ditambahkan.');
        }

        $this->resetForm();
        $this->loadFacilities();
    }

    public function delete(int $id): void
    {
        $facility = Facility::findOrFail($id);
        
        // Delete image if exists
        if ($facility->image_path) {
            Storage::disk('public')->delete($facility->image_path);
        }
        
        $facility->delete();
        
        // Reorder remaining facilities
        $this->reorderAfterDelete($facility->order);
        
        $this->loadFacilities();
        
        session()->flash('message', 'Fasilitas berhasil dihapus.');
    }

    public function removeImage(): void
    {
        if ($this->editingId) {
            $facility = Facility::findOrFail($this->editingId);
            
            if ($facility->image_path) {
                Storage::disk('public')->delete($facility->image_path);
                $facility->image_path = null;
                $facility->save();
                $this->currentImagePath = null;
                
                session()->flash('message', 'Gambar berhasil dihapus.');
            }
        }
    }

    public function moveUp(int $id): void
    {
        $facility = Facility::findOrFail($id);
        
        if ($facility->order > 1) {
            $previousFacility = Facility::where('school_profile_id', $this->profile->id)
                ->where('order', $facility->order - 1)
                ->first();
            
            if ($previousFacility) {
                $previousFacility->order = $facility->order;
                $facility->order = $facility->order - 1;
                
                $previousFacility->save();
                $facility->save();
                
                $this->loadFacilities();
            }
        }
    }

    public function moveDown(int $id): void
    {
        $facility = Facility::findOrFail($id);
        $maxOrder = Facility::where('school_profile_id', $this->profile->id)->max('order');
        
        if ($facility->order < $maxOrder) {
            $nextFacility = Facility::where('school_profile_id', $this->profile->id)
                ->where('order', $facility->order + 1)
                ->first();
            
            if ($nextFacility) {
                $nextFacility->order = $facility->order;
                $facility->order = $facility->order + 1;
                
                $nextFacility->save();
                $facility->save();
                
                $this->loadFacilities();
            }
        }
    }

    private function reorderAfterDelete(int $deletedOrder): void
    {
        Facility::where('school_profile_id', $this->profile->id)
            ->where('order', '>', $deletedOrder)
            ->decrement('order');
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->description = '';
        $this->image = null;
        $this->currentImagePath = null;
        $this->showForm = false;
        $this->resetValidation();
    }
}; ?>

<div>
    <flux:heading size="xl">Fasilitas Sekolah</flux:heading>
    <flux:subheading>Kelola fasilitas sekolah</flux:subheading>

    @if (session()->has('message'))
        <flux:callout color="green" icon="check-circle" class="mt-6">
            {{ session('message') }}
        </flux:callout>
    @endif

    @if (session()->has('error'))
        <flux:callout color="red" icon="exclamation-triangle" class="mt-6">
            {{ session('error') }}
        </flux:callout>
    @endif

    @if (!$profile)
        <flux:callout color="yellow" icon="exclamation-triangle" class="mt-6">
            Profil sekolah belum dibuat. Silakan buat profil sekolah terlebih dahulu di halaman Profil Sekolah.
        </flux:callout>
    @else
        {{-- Add Button --}}
        @if (!$showForm)
            <div class="mt-6">
                <flux:button wire:click="showAddForm" variant="primary">
                    Tambah Fasilitas
                </flux:button>
            </div>
        @endif

        {{-- Add/Edit Form --}}
        @if ($showForm)
            <form wire:submit="save" class="mt-6 space-y-6 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="lg">
                    {{ $editingId ? 'Edit Fasilitas' : 'Tambah Fasilitas Baru' }}
                </flux:heading>

                <flux:input 
                    wire:model="name" 
                    label="Nama Fasilitas" 
                    type="text" 
                    required 
                    placeholder="Contoh: Perpustakaan"
                />

                <flux:textarea 
                    wire:model="description" 
                    label="Deskripsi" 
                    rows="4" 
                    placeholder="Deskripsi fasilitas (opsional)"
                />

                {{-- Image Upload --}}
                <div class="space-y-4">
                    @if ($currentImagePath && !$image)
                        <div class="flex items-start gap-4">
                            <img 
                                src="{{ Storage::url($currentImagePath) }}" 
                                alt="Gambar {{ $name }}" 
                                class="h-32 w-32 rounded-lg border object-cover"
                            >
                            <div class="flex flex-col gap-2">
                                <flux:text>Gambar saat ini</flux:text>
                                <flux:button 
                                    wire:click="removeImage" 
                                    variant="danger" 
                                    size="sm"
                                    type="button"
                                    wire:confirm="Apakah Anda yakin ingin menghapus gambar?"
                                >
                                    Hapus Gambar
                                </flux:button>
                            </div>
                        </div>
                    @endif

                    <div>
                        <flux:input 
                            wire:model="image" 
                            label="Gambar" 
                            type="file" 
                            accept="image/jpeg,image/jpg,image/png,image/webp"
                        />
                        <flux:text class="mt-1 text-sm">
                            Format: JPEG, PNG, WebP. Maksimal 5MB. Opsional.
                        </flux:text>
                        @if ($image)
                            <flux:text class="mt-2 text-sm">
                                File dipilih: {{ $image->getClientOriginalName() }}
                            </flux:text>
                        @endif
                    </div>

                    <div wire:loading wire:target="image" class="text-sm text-zinc-600 dark:text-zinc-400">
                        Mengunggah file...
                    </div>
                </div>

                {{-- Form Actions --}}
                <div class="flex items-center justify-end gap-4">
                    <flux:button 
                        wire:click="cancelEdit" 
                        variant="ghost" 
                        type="button"
                    >
                        Batal
                    </flux:button>
                    <flux:button 
                        variant="primary" 
                        type="submit" 
                        wire:loading.attr="disabled"
                    >
                        <span wire:loading.remove wire:target="save">
                            {{ $editingId ? 'Perbarui' : 'Simpan' }}
                        </span>
                        <span wire:loading wire:target="save">Menyimpan...</span>
                    </flux:button>
                </div>
            </form>
        @endif

        {{-- Facilities List --}}
        @if (count($facilities) > 0)
            <div class="mt-6 space-y-4">
                <flux:heading size="lg">Daftar Fasilitas</flux:heading>
                
                <div class="space-y-3">
                    @foreach ($facilities as $index => $facility)
                        <div class="flex items-center gap-4 rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
                            {{-- Image --}}
                            <div class="flex-shrink-0">
                                @if ($facility['image_path'])
                                    <img 
                                        src="{{ Storage::url($facility['image_path']) }}" 
                                        alt="Gambar {{ $facility['name'] }}" 
                                        class="h-20 w-20 rounded-lg border object-cover"
                                    >
                                @else
                                    <div class="flex h-20 w-20 items-center justify-center rounded-lg border bg-zinc-100 dark:bg-zinc-700">
                                        <flux:icon.building-office class="h-10 w-10 text-zinc-400" />
                                    </div>
                                @endif
                            </div>

                            {{-- Info --}}
                            <div class="flex-1">
                                <flux:heading size="base">{{ $facility['name'] }}</flux:heading>
                                @if ($facility['description'])
                                    <flux:text class="mt-1 text-sm">{{ $facility['description'] }}</flux:text>
                                @endif
                                <flux:text class="mt-1 text-xs text-zinc-500">
                                    Urutan: {{ $facility['order'] }}
                                </flux:text>
                            </div>

                            {{-- Actions --}}
                            <div class="flex flex-col gap-2 sm:flex-row">
                                {{-- Ordering Buttons --}}
                                <div class="flex gap-1">
                                    <flux:button 
                                        wire:click="moveUp({{ $facility['id'] }})" 
                                        variant="ghost" 
                                        size="sm"
                                        :disabled="$index === 0"
                                        title="Pindah ke atas"
                                    >
                                        <flux:icon.chevron-up class="h-4 w-4" />
                                    </flux:button>
                                    <flux:button 
                                        wire:click="moveDown({{ $facility['id'] }})" 
                                        variant="ghost" 
                                        size="sm"
                                        :disabled="$index === count($facilities) - 1"
                                        title="Pindah ke bawah"
                                    >
                                        <flux:icon.chevron-down class="h-4 w-4" />
                                    </flux:button>
                                </div>

                                {{-- Edit & Delete Buttons --}}
                                <div class="flex gap-2">
                                    <flux:button 
                                        wire:click="edit({{ $facility['id'] }})" 
                                        variant="ghost" 
                                        size="sm"
                                    >
                                        Edit
                                    </flux:button>
                                    <flux:button 
                                        wire:click="delete({{ $facility['id'] }})" 
                                        variant="danger" 
                                        size="sm"
                                        wire:confirm="Apakah Anda yakin ingin menghapus fasilitas ini?"
                                    >
                                        Hapus
                                    </flux:button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <flux:callout color="zinc" icon="information-circle" class="mt-6">
                Belum ada fasilitas. Klik tombol "Tambah Fasilitas" untuk menambahkan fasilitas baru.
            </flux:callout>
        @endif
    @endif
</div>
