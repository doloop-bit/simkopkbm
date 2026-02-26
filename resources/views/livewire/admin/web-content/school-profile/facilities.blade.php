<?php

use App\Models\SchoolProfile;
use App\Models\Facility;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
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

<div class="p-6">
    <x-header title="Fasilitas Sekolah" subtitle="Kelola fasilitas sekolah" separator>
        <x-slot:actions>
            @if (!$showForm && $profile)
                <x-button label="Tambah Fasilitas" icon="o-plus" class="btn-primary" wire:click="showAddForm" />
            @endif
        </x-slot:actions>
    </x-header>

    @if (session()->has('message'))
        <x-alert title="Sukses" icon="o-check-circle" class="alert-success mb-6">
            {{ session('message') }}
        </x-alert>
    @endif

    @if (session()->has('error'))
        <x-alert title="Error" icon="o-exclamation-triangle" class="alert-error mb-6">
            {{ session('error') }}
        </x-alert>
    @endif

    @if (!$profile)
        <x-alert title="Profil Belum Ada" icon="o-exclamation-triangle" class="alert-warning mb-6">
            Profil sekolah belum dibuat. Silakan buat profil sekolah terlebih dahulu di halaman Profil Sekolah.
        </x-alert>
    @else
        {{-- Add/Edit Form --}}
        @if ($showForm)
            <x-card title="{{ $editingId ? 'Edit Fasilitas' : 'Tambah Fasilitas Baru' }}" separator shadow class="mb-8 border border-base-200">
                <form wire:submit="save" class="space-y-6">
                    <x-input 
                        wire:model="name" 
                        label="Nama Fasilitas" 
                        type="text" 
                        required 
                        placeholder="Contoh: Perpustakaan"
                    />

                    <x-textarea 
                        wire:model="description" 
                        label="Deskripsi" 
                        rows="4" 
                        placeholder="Deskripsi fasilitas (opsional)"
                    />

                    {{-- Image Upload --}}
                    <div class="space-y-4">
                        @if ($currentImagePath && !$image)
                            <div class="flex items-start gap-4 p-4 bg-base-200 rounded-lg">
                                <img 
                                    src="{{ Storage::url($currentImagePath) }}" 
                                    alt="Gambar {{ $name }}" 
                                    class="h-32 w-32 rounded-lg border border-base-300 object-cover"
                                >
                                <div class="flex flex-col gap-2">
                                    <span class="text-sm font-medium">Gambar saat ini</span>
                                    <x-button 
                                        wire:click="removeImage" 
                                        label="Hapus Gambar"
                                        icon="o-trash"
                                        class="btn-error btn-sm"
                                        wire:confirm="Apakah Anda yakin ingin menghapus gambar?"
                                    />
                                </div>
                            </div>
                        @endif

                        <x-file 
                            wire:model="image" 
                            label="Gambar" 
                            accept="image/jpeg,image/jpg,image/png,image/webp"
                            crop-after-change
                        >
                            <span class="text-xs opacity-70">Format: JPEG, PNG, WebP. Maksimal 5MB. Opsional.</span>
                            @if ($image)
                                <div class="text-sm mt-2">
                                    File dipilih: <span class="font-medium">{{ $image->getClientOriginalName() }}</span>
                                </div>
                            @endif
                        </x-file>
                    </div>

                    {{-- Form Actions --}}
                    <x-slot:actions>
                        <x-button label="Batal" wire:click="cancelEdit" ghost />
                        <x-button 
                            label="{{ $editingId ? 'Perbarui' : 'Simpan' }}"
                            class="btn-primary" 
                            type="submit" 
                            spinner="save"
                        />
                    </x-slot:actions>
                </form>
            </x-card>
        @endif

        {{-- Facilities List --}}
        @if (count($facilities) > 0)
            <div class="space-y-4">
                <h3 class="text-lg font-bold">Daftar Fasilitas</h3>
                
                <div class="grid grid-cols-1 gap-4">
                    @foreach ($facilities as $index => $facility)
                        <x-card wire:key="facility-{{ $facility['id'] }}" shadow class="border border-base-200 hover:shadow-md transition-all">
                            <div class="flex flex-col sm:flex-row items-center sm:items-start gap-4">
                                {{-- Image --}}
                                <div class="flex-shrink-0">
                                    @if ($facility['image_path'])
                                        <img 
                                            src="{{ Storage::url($facility['image_path']) }}" 
                                            alt="Gambar {{ $facility['name'] }}" 
                                            class="h-24 w-24 sm:h-20 sm:w-20 rounded-lg border border-base-300 object-cover"
                                        >
                                    @else
                                        <div class="flex h-24 w-24 sm:h-20 sm:w-20 items-center justify-center rounded-lg border border-base-200 bg-base-200">
                                            <x-icon name="o-building-office" class="h-10 w-10 opacity-30" />
                                        </div>
                                    @endif
                                </div>

                                {{-- Info --}}
                                <div class="flex-1 text-center sm:text-left">
                                    <h4 class="font-bold text-lg">{{ $facility['name'] }}</h4>
                                    @if ($facility['description'])
                                        <p class="mt-1 text-sm opacity-70 line-clamp-2">{{ $facility['description'] }}</p>
                                    @endif
                                    <div class="mt-2 text-xs flex items-center justify-center sm:justify-start gap-2">
                                        <x-badge label="Urutan: {{ $facility['order'] }}" class="badge-ghost badge-xs" />
                                    </div>
                                </div>

                                {{-- Actions --}}
                                <div class="flex flex-row sm:flex-col justify-end gap-1 w-full sm:w-auto mt-4 sm:mt-0 pt-4 sm:pt-0 border-t sm:border-t-0 border-base-200">
                                    {{-- Ordering Buttons --}}
                                    <div class="flex gap-1 justify-center">
                                        <x-button 
                                            wire:click="moveUp({{ $facility['id'] }})" 
                                            icon="o-chevron-up"
                                            class="btn-xs btn-ghost btn-circle"
                                            :disabled="$index === 0"
                                            tooltip="Pindah ke atas"
                                        />
                                        <x-button 
                                            wire:click="moveDown({{ $facility['id'] }})" 
                                            icon="o-chevron-down"
                                            class="btn-xs btn-ghost btn-circle"
                                            :disabled="$index === count($facilities) - 1"
                                            tooltip="Pindah ke bawah"
                                        />
                                    </div>

                                    {{-- Edit & Delete Buttons --}}
                                    <div class="flex gap-1 justify-center">
                                        <x-button 
                                            wire:click="edit({{ $facility['id'] }})" 
                                            icon="o-pencil"
                                            class="btn-sm btn-ghost"
                                            tooltip="Edit"
                                        />
                                        <x-button 
                                            wire:click="delete({{ $facility['id'] }})" 
                                            icon="o-trash"
                                            class="btn-sm btn-ghost text-error"
                                            wire:confirm="Apakah Anda yakin ingin menghapus fasilitas ini?"
                                            tooltip="Hapus"
                                        />
                                    </div>
                                </div>
                            </div>
                        </x-card>
                    @endforeach
                </div>
            </div>
        @else
            <x-card class="mt-8 p-12 text-center" shadow>
                <x-icon name="o-information-circle" class="size-12 mb-3 opacity-20" />
                <p class="text-base-content/50">
                    Belum ada fasilitas. Klik tombol "Tambah Fasilitas" untuk menambahkan fasilitas baru.
                </p>
            </x-card>
        @endif
    @endif
</div>
