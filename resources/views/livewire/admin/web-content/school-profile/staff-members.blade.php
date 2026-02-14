<?php

use App\Models\SchoolProfile;
use App\Models\StaffMember;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public ?SchoolProfile $profile = null;
    public array $staffMembers = [];
    
    // Form fields for adding/editing staff
    public ?int $editingId = null;
    public string $name = '';
    public string $position = '';
    public $photo;
    public ?string $currentPhotoPath = null;
    public bool $showForm = false;

    public function mount(): void
    {
        $this->profile = SchoolProfile::active();
        
        if (!$this->profile) {
            session()->flash('error', 'Profil sekolah belum dibuat. Silakan buat profil sekolah terlebih dahulu.');
            return;
        }

        $this->loadStaffMembers();
    }

    public function loadStaffMembers(): void
    {
        if ($this->profile) {
            $this->staffMembers = $this->profile->staffMembers()
                ->orderBy('order')
                ->get()
                ->toArray();
        }
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'position' => 'required|string|max:255',
            'photo' => $this->editingId ? 'nullable|image|mimes:jpeg,jpg,png,webp|max:5120' : 'nullable|image|mimes:jpeg,jpg,png,webp|max:5120',
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'name' => 'nama',
            'position' => 'jabatan',
            'photo' => 'foto',
        ];
    }

    public function showAddForm(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $staff = StaffMember::findOrFail($id);
        
        $this->editingId = $staff->id;
        $this->name = $staff->name;
        $this->position = $staff->position;
        $this->currentPhotoPath = $staff->photo_path;
        $this->showForm = true;
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->editingId) {
            // Update existing staff member
            $staff = StaffMember::findOrFail($this->editingId);
            
            // Handle photo upload
            if ($this->photo) {
                // Delete old photo if exists
                if ($staff->photo_path) {
                    Storage::disk('public')->delete($staff->photo_path);
                }
                
                $path = $this->photo->store('staff', 'public');
                $staff->photo_path = $path;
            }
            
            $staff->name = $this->name;
            $staff->position = $this->position;
            $staff->save();
            
            session()->flash('message', 'Anggota struktur organisasi berhasil diperbarui.');
        } else {
            // Create new staff member
            $maxOrder = StaffMember::where('school_profile_id', $this->profile->id)->max('order') ?? 0;
            
            $data = [
                'school_profile_id' => $this->profile->id,
                'name' => $this->name,
                'position' => $this->position,
                'order' => $maxOrder + 1,
            ];
            
            // Handle photo upload
            if ($this->photo) {
                $path = $this->photo->store('staff', 'public');
                $data['photo_path'] = $path;
            }
            
            StaffMember::create($data);
            
            session()->flash('message', 'Anggota struktur organisasi berhasil ditambahkan.');
        }

        $this->resetForm();
        $this->loadStaffMembers();
    }

    public function delete(int $id): void
    {
        $staff = StaffMember::findOrFail($id);
        
        // Delete photo if exists
        if ($staff->photo_path) {
            Storage::disk('public')->delete($staff->photo_path);
        }
        
        $staff->delete();
        
        // Reorder remaining staff members
        $this->reorderAfterDelete($staff->order);
        
        $this->loadStaffMembers();
        
        session()->flash('message', 'Anggota struktur organisasi berhasil dihapus.');
    }

    public function removePhoto(): void
    {
        if ($this->editingId) {
            $staff = StaffMember::findOrFail($this->editingId);
            
            if ($staff->photo_path) {
                Storage::disk('public')->delete($staff->photo_path);
                $staff->photo_path = null;
                $staff->save();
                $this->currentPhotoPath = null;
                
                session()->flash('message', 'Foto berhasil dihapus.');
            }
        }
    }

    public function moveUp(int $id): void
    {
        $staff = StaffMember::findOrFail($id);
        
        if ($staff->order > 1) {
            $previousStaff = StaffMember::where('school_profile_id', $this->profile->id)
                ->where('order', $staff->order - 1)
                ->first();
            
            if ($previousStaff) {
                $previousStaff->order = $staff->order;
                $staff->order = $staff->order - 1;
                
                $previousStaff->save();
                $staff->save();
                
                $this->loadStaffMembers();
            }
        }
    }

    public function moveDown(int $id): void
    {
        $staff = StaffMember::findOrFail($id);
        $maxOrder = StaffMember::where('school_profile_id', $this->profile->id)->max('order');
        
        if ($staff->order < $maxOrder) {
            $nextStaff = StaffMember::where('school_profile_id', $this->profile->id)
                ->where('order', $staff->order + 1)
                ->first();
            
            if ($nextStaff) {
                $nextStaff->order = $staff->order;
                $staff->order = $staff->order + 1;
                
                $nextStaff->save();
                $staff->save();
                
                $this->loadStaffMembers();
            }
        }
    }

    private function reorderAfterDelete(int $deletedOrder): void
    {
        StaffMember::where('school_profile_id', $this->profile->id)
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
        $this->position = '';
        $this->photo = null;
        $this->currentPhotoPath = null;
        $this->showForm = false;
        $this->resetValidation();
    }
}; ?>

<div>
    <flux:heading size="xl">Struktur Organisasi</flux:heading>
    <flux:subheading>Kelola anggota struktur organisasi sekolah</flux:subheading>

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
                    Tambah Anggota
                </flux:button>
            </div>
        @endif

        {{-- Add/Edit Form --}}
        @if ($showForm)
            <form wire:submit="save" class="mt-6 space-y-6 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="lg">
                    {{ $editingId ? 'Edit Anggota' : 'Tambah Anggota Baru' }}
                </flux:heading>

                <flux:input 
                    wire:model="name" 
                    label="Nama Lengkap" 
                    type="text" 
                    required 
                    placeholder="Contoh: Dr. Ahmad Suryadi, M.Pd"
                />

                <flux:input 
                    wire:model="position" 
                    label="Jabatan" 
                    type="text" 
                    required 
                    placeholder="Contoh: Kepala Sekolah"
                />

                {{-- Photo Upload --}}
                <div class="space-y-4">
                    @if ($currentPhotoPath && !$photo)
                        <div class="flex items-start gap-4">
                            <img 
                                src="{{ Storage::url($currentPhotoPath) }}" 
                                alt="Foto {{ $name }}" 
                                class="h-32 w-32 rounded-lg border object-cover"
                            >
                            <div class="flex flex-col gap-2">
                                <flux:text>Foto saat ini</flux:text>
                                <flux:button 
                                    wire:click="removePhoto" 
                                    variant="danger" 
                                    size="sm"
                                    type="button"
                                    wire:confirm="Apakah Anda yakin ingin menghapus foto?"
                                >
                                    Hapus Foto
                                </flux:button>
                            </div>
                        </div>
                    @endif

                    <div>
                        <flux:input 
                            wire:model="photo" 
                            label="Foto" 
                            type="file" 
                            accept="image/jpeg,image/jpg,image/png,image/webp"
                        />
                        <flux:text class="mt-1 text-sm">
                            Format: JPEG, PNG, WebP. Maksimal 5MB. Opsional.
                        </flux:text>
                        @if ($photo)
                            <flux:text class="mt-2 text-sm">
                                File dipilih: {{ $photo->getClientOriginalName() }}
                            </flux:text>
                        @endif
                    </div>

                    <div wire:loading wire:target="photo" class="text-sm text-zinc-600 dark:text-zinc-400">
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

        {{-- Staff Members List --}}
        @if (count($staffMembers) > 0)
            <div class="mt-6 space-y-4">
                <flux:heading size="lg">Daftar Anggota</flux:heading>
                
                <div class="space-y-3">
                    @foreach ($staffMembers as $index => $staff)
                        <div class="flex items-center gap-4 rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
                            {{-- Photo --}}
                            <div class="flex-shrink-0">
                                @if ($staff['photo_path'])
                                    <img 
                                        src="{{ Storage::url($staff['photo_path']) }}" 
                                        alt="Foto {{ $staff['name'] }}" 
                                        class="h-20 w-20 rounded-lg border object-cover"
                                    >
                                @else
                                    <div class="flex h-20 w-20 items-center justify-center rounded-lg border bg-zinc-100 dark:bg-zinc-700">
                                        <flux:icon.user class="h-10 w-10 text-zinc-400" />
                                    </div>
                                @endif
                            </div>

                            {{-- Info --}}
                            <div class="flex-1">
                                <flux:heading size="base">{{ $staff['name'] }}</flux:heading>
                                <flux:text class="text-sm">{{ $staff['position'] }}</flux:text>
                                <flux:text class="mt-1 text-xs text-zinc-500">
                                    Urutan: {{ $staff['order'] }}
                                </flux:text>
                            </div>

                            {{-- Actions --}}
                            <div class="flex flex-col gap-2 sm:flex-row">
                                {{-- Ordering Buttons --}}
                                <div class="flex gap-1">
                                    <flux:button 
                                        wire:click="moveUp({{ $staff['id'] }})" 
                                        variant="ghost" 
                                        size="sm"
                                        :disabled="$index === 0"
                                        title="Pindah ke atas"
                                    >
                                        <flux:icon.chevron-up class="h-4 w-4" />
                                    </flux:button>
                                    <flux:button 
                                        wire:click="moveDown({{ $staff['id'] }})" 
                                        variant="ghost" 
                                        size="sm"
                                        :disabled="$index === count($staffMembers) - 1"
                                        title="Pindah ke bawah"
                                    >
                                        <flux:icon.chevron-down class="h-4 w-4" />
                                    </flux:button>
                                </div>

                                {{-- Edit & Delete Buttons --}}
                                <div class="flex gap-2">
                                    <flux:button 
                                        wire:click="edit({{ $staff['id'] }})" 
                                        variant="ghost" 
                                        size="sm"
                                    >
                                        Edit
                                    </flux:button>
                                    <flux:button 
                                        wire:click="delete({{ $staff['id'] }})" 
                                        variant="danger" 
                                        size="sm"
                                        wire:confirm="Apakah Anda yakin ingin menghapus anggota ini?"
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
                Belum ada anggota struktur organisasi. Klik tombol "Tambah Anggota" untuk menambahkan anggota baru.
            </flux:callout>
        @endif
    @endif
</div>
