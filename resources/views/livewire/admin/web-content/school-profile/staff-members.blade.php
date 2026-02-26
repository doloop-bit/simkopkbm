<?php

use App\Models\SchoolProfile;
use App\Models\StaffMember;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
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

<div class="p-6">
    <x-header title="Struktur Organisasi" subtitle="Kelola anggota struktur organisasi sekolah" separator>
        <x-slot:actions>
            @if (!$showForm && $profile)
                <x-button label="Tambah Anggota" icon="o-plus" class="btn-primary" wire:click="showAddForm" />
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
            <x-card title="{{ $editingId ? 'Edit Anggota' : 'Tambah Anggota Baru' }}" separator shadow class="mb-8 border border-base-200">
                <form wire:submit="save" class="space-y-6">
                    <x-input 
                        wire:model="name" 
                        label="Nama Lengkap" 
                        type="text" 
                        required 
                        placeholder="Contoh: Dr. Ahmad Suryadi, M.Pd"
                    />

                    <x-input 
                        wire:model="position" 
                        label="Jabatan" 
                        type="text" 
                        required 
                        placeholder="Contoh: Kepala Sekolah"
                    />

                    {{-- Photo Upload --}}
                    <div class="space-y-4">
                        @if ($currentPhotoPath && !$photo)
                            <div class="flex items-start gap-4 p-4 bg-base-200 rounded-lg">
                                <img 
                                    src="{{ Storage::url($currentPhotoPath) }}" 
                                    alt="Foto {{ $name }}" 
                                    class="h-32 w-32 rounded-lg border border-base-300 object-cover"
                                >
                                <div class="flex flex-col gap-2">
                                    <span class="text-sm font-medium">Foto saat ini</span>
                                    <x-button 
                                        wire:click="removePhoto" 
                                        label="Hapus Foto"
                                        icon="o-trash"
                                        class="btn-error btn-sm"
                                        wire:confirm="Apakah Anda yakin ingin menghapus foto?"
                                    />
                                </div>
                            </div>
                        @endif

                        <x-file 
                            wire:model="photo" 
                            label="Foto" 
                            accept="image/jpeg,image/jpg,image/png,image/webp"
                            crop-after-change
                        >
                            <span class="text-xs opacity-70">Format: JPEG, PNG, WebP. Maksimal 5MB. Opsional.</span>
                            @if ($photo)
                                <div class="text-sm mt-2">
                                    File dipilih: <span class="font-medium">{{ $photo->getClientOriginalName() }}</span>
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

        {{-- Staff Members List --}}
        @if (count($staffMembers) > 0)
            <div class="space-y-4">
                <h3 class="text-lg font-bold">Daftar Anggota</h3>
                
                <div class="grid grid-cols-1 gap-4">
                    @foreach ($staffMembers as $index => $staff)
                        <x-card wire:key="staff-{{ $staff['id'] }}" shadow class="border border-base-200 hover:shadow-md transition-all">
                            <div class="flex flex-col sm:flex-row items-center sm:items-start gap-4">
                                {{-- Photo --}}
                                <div class="flex-shrink-0">
                                    @if ($staff['photo_path'])
                                        <img 
                                            src="{{ Storage::url($staff['photo_path']) }}" 
                                            alt="Foto {{ $staff['name'] }}" 
                                            class="h-24 w-24 sm:h-20 sm:w-20 rounded-full border border-base-300 object-cover"
                                        >
                                    @else
                                        <div class="flex h-24 w-24 sm:h-20 sm:w-20 items-center justify-center rounded-full border border-base-200 bg-base-200">
                                            <x-icon name="o-user" class="h-10 w-10 opacity-30" />
                                        </div>
                                    @endif
                                </div>

                                {{-- Info --}}
                                <div class="flex-1 text-center sm:text-left">
                                    <h4 class="font-bold text-lg">{{ $staff['name'] }}</h4>
                                    <p class="text-sm opacity-70">{{ $staff['position'] }}</p>
                                    <div class="mt-2 text-xs flex items-center justify-center sm:justify-start gap-2">
                                        <x-badge label="Urutan: {{ $staff['order'] }}" class="badge-ghost badge-xs" />
                                    </div>
                                </div>

                                {{-- Actions --}}
                                <div class="flex flex-row sm:flex-col justify-end gap-1 w-full sm:w-auto mt-4 sm:mt-0 pt-4 sm:pt-0 border-t sm:border-t-0 border-base-200">
                                    {{-- Ordering Buttons --}}
                                    <div class="flex gap-1 justify-center">
                                        <x-button 
                                            wire:click="moveUp({{ $staff['id'] }})" 
                                            icon="o-chevron-up"
                                            class="btn-xs btn-ghost btn-circle"
                                            :disabled="$index === 0"
                                            tooltip="Pindah ke atas"
                                        />
                                        <x-button 
                                            wire:click="moveDown({{ $staff['id'] }})" 
                                            icon="o-chevron-down"
                                            class="btn-xs btn-ghost btn-circle"
                                            :disabled="$index === count($staffMembers) - 1"
                                            tooltip="Pindah ke bawah"
                                        />
                                    </div>

                                    {{-- Edit & Delete Buttons --}}
                                    <div class="flex gap-1 justify-center">
                                        <x-button 
                                            wire:click="edit({{ $staff['id'] }})" 
                                            icon="o-pencil"
                                            class="btn-sm btn-ghost"
                                            tooltip="Edit"
                                        />
                                        <x-button 
                                            wire:click="delete({{ $staff['id'] }})" 
                                            icon="o-trash"
                                            class="btn-sm btn-ghost text-error"
                                            wire:confirm="Apakah Anda yakin ingin menghapus anggota ini?"
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
                <x-icon name="o-user-group" class="size-12 mb-3 opacity-20" />
                <p class="text-base-content/50">
                    Belum ada anggota struktur organisasi. Klik tombol "Tambah Anggota" untuk menambahkan anggota baru.
                </p>
            </x-card>
        @endif
    @endif
</div>
