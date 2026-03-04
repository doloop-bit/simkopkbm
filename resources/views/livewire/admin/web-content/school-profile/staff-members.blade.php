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

<div class="p-6 space-y-8 text-slate-900 dark:text-white pb-24 md:pb-6">
    @if (session()->has('message'))
        <x-ui.alert :title="__('Sukses')" icon="o-check-circle" class="bg-emerald-50 text-emerald-800 border-emerald-100" dismissible>
            {{ session('message') }}
        </x-ui.alert>
    @endif

    @if (session()->has('error'))
        <x-ui.alert :title="__('Kesalahan')" icon="o-exclamation-triangle" class="bg-rose-50 text-rose-800 border-rose-100" dismissible>
            {{ session('error') }}
        </x-ui.alert>
    @endif

    <x-ui.header :title="__('Struktur Organisasi')" :subtitle="__('Kelola daftar pengurus, guru, dan staf pendukung yang bertugas di sekolah.')" separator>
        <x-slot:actions>
            <x-ui.button :label="__('Profil')" icon="o-building-office-2" class="btn-ghost" :href="route('admin.school-profile.edit')" wire:navigate />
            <x-ui.button :label="__('Fasilitas')" icon="o-building-office" class="btn-ghost" :href="route('admin.school-profile.facilities')" wire:navigate />
            @if (!$showForm && $profile)
                <x-ui.button :label="__('Tambah Anggota Baru')" icon="o-plus" class="btn-primary shadow-lg shadow-primary/20" wire:click="showAddForm" />
            @endif
        </x-slot:actions>
    </x-ui.header>

    @if (!$profile)
        <x-ui.alert :title="__('Profil Belum Dikonfigurasi')" icon="o-exclamation-triangle" class="bg-amber-50 text-amber-800 border-amber-100 shadow-sm rounded-3xl">
            {{ __('Profil sekolah belum dibuat. Silakan buat profil sekolah terlebih dahulu di halaman Profil Sekolah untuk dapat mengelola struktur organisasi.') }}
        </x-ui.alert>
    @else
        {{-- Add/Edit Form --}}
        @if ($showForm)
            <x-ui.card shadow padding="false" class="mb-12 ring-2 ring-primary/5">
                <div class="p-6 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
                    <h3 class="font-bold text-slate-800 dark:text-white uppercase tracking-wider text-xs">
                        {{ $editingId ? __('Edit Data Personel') : __('Registrasi Personel Baru') }}
                    </h3>
                </div>
                <div class="p-8 space-y-8">
                    <form wire:submit="save" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <x-ui.input 
                                wire:model="name" 
                                :label="__('Nama Lengkap & Gelar')" 
                                type="text" 
                                required 
                                :placeholder="__('Contoh: Dr. Ahmad Suryadi, M.Pd')"
                                class="font-semibold text-lg"
                            />

                            <x-ui.input 
                                wire:model="position" 
                                :label="__('Jabatan / Peran')" 
                                type="text" 
                                required 
                                :placeholder="__('Contoh: Kepala Sekolah')"
                                class="font-semibold text-slate-600"
                            />
                        </div>

                        {{-- Photo Upload --}}
                        <div class="space-y-4">
                            @if ($currentPhotoPath && !$photo)
                                <div class="flex items-center gap-6 p-6 bg-slate-50 dark:bg-slate-900/50 rounded-[2rem] border border-slate-100 dark:border-slate-800 group">
                                    <div class="relative">
                                        <img 
                                            src="{{ Storage::url($currentPhotoPath) }}" 
                                            alt="Foto {{ $name }}" 
                                            class="h-24 w-24 rounded-full border-4 border-white dark:border-slate-700 object-cover shadow-xl group-hover:scale-105 transition-transform duration-500"
                                        >
                                    </div>
                                    <div class="flex-1">
                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2 block">{{ __('Foto Profil Saat Ini') }}</span>
                                        <x-ui.button 
                                            wire:click="removePhoto" 
                                            :label="__('Hapus Foto')"
                                            icon="o-trash"
                                            class="btn-ghost btn-xs text-rose-500 hover:bg-rose-50 font-semibold"
                                            wire:confirm="{{ __('Hapus foto personel ini?') }}"
                                        />
                                    </div>
                                </div>
                            @endif

                        {{-- Photo Upload --}}
                        <div class="space-y-4 max-w-lg">
                            <x-ui.file 
                                wire:model="photo" 
                                :label="__('Unggah Foto Formal')" 
                                accept="image/jpeg,image/jpg,image/png,image/webp"
                            >
                                @if ($photo)
                                    <div class="text-xs font-semibold text-primary mt-2 px-1">
                                        {{ __('File dipilih') }}: <span class="underline">{{ $photo->getClientOriginalName() }}</span>
                                    </div>
                                @endif
                            </x-ui.file>
                            <p class="text-xs text-slate-400 px-1 leading-relaxed">
                                * {{ __('Format file: JPEG, PNG, WebP (Maksimal 5MB). Gunakan latar belakang polos atau formal untuk konsistensi visual.') }}
                            </p>
                        </div>
                        </div>

                        {{-- Form Actions --}}
                        <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-100 dark:border-slate-800">
                            <x-ui.button :label="__('Batalkan')" wire:click="cancelEdit" class="btn-ghost" />
                            <x-ui.button 
                                :label="$editingId ? __('Perbarui Data') : __('Simpan Personel')"
                                class="btn-primary shadow-xl shadow-primary/20 px-8" 
                                type="submit" 
                                spinner="save"
                            />
                        </div>
                    </form>
                </div>
            </x-ui.card>
        @endif

        {{-- Staff Members List --}}
        <div class="space-y-6">
            <div class="flex items-center justify-between px-2">
                <h3 class="font-bold text-slate-800 dark:text-white uppercase tracking-wider text-xs">{{ __('Daftar Pengurus & Staf') }}</h3>
                <x-ui.badge :label="count($staffMembers) . ' ' . __('Personel')" class="bg-emerald-50 text-emerald-600 border-none font-bold" />
            </div>
            
            @if (count($staffMembers) > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    @foreach ($staffMembers as $index => $staff)
                        <x-ui.card wire:key="staff-{{ $staff['id'] }}" shadow padding="false" class="group relative overflow-hidden border-none ring-1 ring-slate-100 dark:ring-slate-800 hover:ring-primary/20 transition-all duration-500">
                            {{-- Photo Frame --}}
                            <div class="pt-8 pb-6 flex justify-center bg-slate-50 dark:bg-slate-900/50 border-b border-slate-100 dark:border-slate-800/50">
                                <div class="relative">
                                    <div class="size-24 rounded-full p-1 bg-white dark:bg-slate-800 shadow-xl ring-1 ring-slate-100 dark:ring-slate-700">
                                        @if ($staff['photo_path'])
                                            <img 
                                                src="{{ Storage::url($staff['photo_path']) }}" 
                                                alt="Foto {{ $staff['name'] }}" 
                                                class="size-full rounded-full object-cover group-hover:scale-110 transition-transform duration-700"
                                            >
                                        @else
                                            <div class="size-full rounded-full bg-slate-50 dark:bg-slate-900 flex items-center justify-center">
                                                <x-ui.icon name="o-user" class="size-10 text-slate-200" />
                                            </div>
                                        @endif
                                    </div>
                                    <div class="absolute -bottom-1 -right-1 size-7 bg-white dark:bg-slate-800 rounded-full shadow-md flex items-center justify-center border-2 border-slate-50 dark:border-slate-900">
                                        <span class="text-[9px] font-bold text-slate-400">#{{ $index + 1 }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="p-6 text-center">
                                <h4 class="font-bold text-slate-900 dark:text-white leading-tight mb-1 group-hover:text-primary transition-colors uppercase tracking-tight text-base">{{ $staff['name'] }}</h4>
                                <p class="text-[10px] font-bold text-indigo-500 uppercase tracking-widest mb-6 leading-relaxed">{{ $staff['position'] }}</p>
                                
                                <div class="flex items-center justify-between pt-4 border-t border-slate-50 dark:border-slate-800/50">
                                    <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-all duration-300">
                                        <x-ui.button 
                                            wire:click="moveUp({{ $staff['id'] }})" 
                                            icon="o-chevron-up"
                                            class="size-7 min-h-0 p-0 btn-ghost text-slate-400 hover:text-indigo-500"
                                            :disabled="$index === 0"
                                        />
                                        <x-ui.button 
                                            wire:click="moveDown({{ $staff['id'] }})" 
                                            icon="o-chevron-down"
                                            class="size-7 min-h-0 p-0 btn-ghost text-slate-400 hover:text-indigo-500"
                                            :disabled="$index === count($staffMembers) - 1"
                                        />
                                    </div>
                                    <div class="flex gap-1">
                                        <x-ui.button 
                                            wire:click="edit({{ $staff['id'] }})" 
                                            icon="o-pencil"
                                            class="size-7 min-h-0 p-0 btn-ghost text-slate-300 hover:text-primary"
                                        />
                                        <x-ui.button 
                                            wire:click="delete({{ $staff['id'] }})" 
                                            icon="o-trash"
                                            class="size-7 min-h-0 p-0 btn-ghost text-slate-300 hover:text-rose-500"
                                            wire:confirm="{{ __('Hapus personel ini dari struktur organisasi?') }}"
                                        />
                                    </div>
                                </div>
                            </div>
                        </x-ui.card>
                    @endforeach
                </div>
            @else
                <div class="flex flex-col items-center justify-center py-32 text-slate-300 dark:text-slate-700 border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-[32px] bg-slate-50/50 dark:bg-slate-900/50 transition-all text-center px-6">
                    <x-ui.icon name="o-user-group" class="size-20 mb-6 opacity-20" />
                    <p class="text-sm font-bold uppercase tracking-widest">{{ __('Data Struktur Organisasi Masih Kosong') }}</p>
                    <x-ui.button :label="__('Inisialisasi Data Personel')" wire:click="showAddForm" class="mt-8 btn-ghost text-primary btn-sm font-bold" />
                </div>
            @endif
        </div>
    @endif
</div>
