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

    <x-ui.header :title="__('Fasilitas Sekolah')" :subtitle="__('Kelola daftar fasilitas penunjang kegiatan belajar mengajar di sekolah.')" separator>
        <x-slot:actions>
            <x-ui.button :label="__('Profil')" icon="o-building-office-2" class="btn-ghost" :href="route('admin.school-profile.edit')" wire:navigate />
            <x-ui.button :label="__('Struktur')" icon="o-user-group" class="btn-ghost" :href="route('admin.school-profile.staff-members')" wire:navigate />
            @if (!$showForm && $profile)
                <x-ui.button :label="__('Tambah Fasilitas Baru')" icon="o-plus" class="btn-primary shadow-lg shadow-primary/20" wire:click="showAddForm" />
            @endif
        </x-slot:actions>
    </x-ui.header>

    @if (!$profile)
        <x-ui.alert :title="__('Profil Belum Dikonfigurasi')" icon="o-exclamation-triangle" class="bg-amber-50 text-amber-800 border-amber-100 shadow-sm rounded-3xl">
            {{ __('Profil sekolah belum dibuat. Silakan buat profil sekolah terlebih dahulu di halaman Profil Sekolah untuk dapat mengelola fasilitas.') }}
        </x-ui.alert>
    @else
        {{-- Add/Edit Form --}}
        @if ($showForm)
            <x-ui.card shadow padding="false" class="mb-12 ring-2 ring-primary/5">
                <div class="p-6 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
                    <h3 class="font-bold text-slate-800 dark:text-white uppercase tracking-wider text-xs">
                        {{ $editingId ? __('Edit Detail Fasilitas') : __('Registrasi Fasilitas Baru') }}
                    </h3>
                </div>
                <div class="p-8 space-y-8">
                    <form wire:submit="save" class="space-y-6">
                        <x-ui.input 
                            wire:model="name" 
                            :label="__('Nama Fasilitas')" 
                            type="text" 
                            required 
                            :placeholder="__('Contoh: Laboratorium Digital')"
                            class="font-semibold text-lg"
                        />

                        <x-ui.textarea 
                            wire:model="description" 
                            :label="__('Deskripsi Singkat')" 
                            rows="4" 
                            :placeholder="__('Jelaskan fungsi atau keunggulan fasilitas ini...')"
                            class="text-sm"
                        />

                        {{-- Image Upload --}}
                        <div class="space-y-4 max-w-lg">
                            @if ($currentImagePath && !$image)
                                <div class="flex items-center gap-6 p-6 bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-100 dark:border-slate-800 group">
                                    <div class="relative">
                                        <img 
                                            src="{{ Storage::url($currentImagePath) }}" 
                                            alt="Gambar {{ $name }}" 
                                            class="h-32 w-48 rounded-xl border border-white dark:border-slate-700 object-cover shadow-lg group-hover:scale-105 transition-transform duration-500"
                                        >
                                    </div>
                                    <div class="flex-1">
                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2 block">{{ __('Gambar Saat Ini') }}</span>
                                        <x-ui.button 
                                            wire:click="removeImage" 
                                            :label="__('Hapus Gambar')"
                                            icon="o-trash"
                                            class="btn-ghost btn-xs text-rose-500 hover:bg-rose-50 font-semibold"
                                            wire:confirm="{{ __('Apakah Anda yakin ingin menghapus gambar fasilitas ini?') }}"
                                        />
                                    </div>
                                </div>
                            @endif

                            <x-ui.file 
                                wire:model="image" 
                                :label="__('Unggah Foto Fasilitas')" 
                                accept="image/jpeg,image/jpg,image/png,image/webp"
                            >
                                @if ($image)
                                    <div class="text-xs font-semibold text-primary mt-2 px-1">
                                        {{ __('File dipilih') }}: <span class="underline">{{ $image->getClientOriginalName() }}</span>
                                    </div>
                                @endif
                            </x-ui.file>
                            <p class="text-xs text-slate-400 px-1 leading-relaxed">
                                * {{ __('Format file yang didukung: JPEG, PNG, WebP (Maksimal 5MB). Rasio 16:9 direkomendasikan.') }}
                            </p>
                        </div>

                        {{-- Form Actions --}}
                        <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-100 dark:border-slate-800">
                            <x-ui.button :label="__('Batalkan')" wire:click="cancelEdit" class="btn-ghost" />
                            <x-ui.button 
                                :label="$editingId ? __('Perbarui Fasilitas') : __('Simpan Fasilitas')"
                                class="btn-primary shadow-xl shadow-primary/20 px-8" 
                                type="submit" 
                                spinner="save"
                            />
                        </div>
                    </form>
                </div>
            </x-ui.card>
        @endif

        {{-- Facilities List --}}
        <div class="space-y-6">
            <div class="flex items-center justify-between px-2">
                <h3 class="font-bold text-slate-800 dark:text-white uppercase tracking-wider text-xs">{{ __('Daftar Fasilitas Terdaftar') }}</h3>
                <x-ui.badge :label="count($facilities) . ' ' . __('Unit')" class="bg-indigo-50 text-indigo-600 border-none font-bold" />
            </div>
            
            @if (count($facilities) > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($facilities as $index => $facility)
                        <x-ui.card wire:key="facility-{{ $facility['id'] }}" shadow padding="false" class="group overflow-hidden border-none ring-1 ring-slate-100 dark:ring-slate-800 hover:ring-primary/20 transition-all duration-500">
                            {{-- Image Preview --}}
                            <div class="relative h-48 overflow-hidden bg-slate-100 dark:bg-slate-800">
                                @if ($facility['image_path'])
                                    <img 
                                        src="{{ Storage::url($facility['image_path']) }}" 
                                        alt="Gambar {{ $facility['name'] }}" 
                                        class="absolute inset-0 w-full h-full object-cover group-hover:scale-110 transition-transform duration-700"
                                    >
                                @else
                                    <div class="absolute inset-0 flex items-center justify-center opacity-10">
                                        <x-ui.icon name="o-building-office" class="size-24" />
                                    </div>
                                @endif
                                
                                {{-- Ordering Controls Overlay --}}
                                <div class="absolute top-4 right-4 flex flex-col gap-2 translate-x-12 opacity-0 group-hover:translate-x-0 group-hover:opacity-100 transition-all duration-300">
                                    <x-ui.button 
                                        wire:click="moveUp({{ $facility['id'] }})" 
                                        icon="o-chevron-up"
                                        class="size-8 min-h-0 p-0 bg-white/90 dark:bg-slate-800/90 hover:bg-white text-slate-600 dark:text-white rounded-xl shadow-sm border-none"
                                        :disabled="$index === 0"
                                    />
                                    <x-ui.button 
                                        wire:click="moveDown({{ $facility['id'] }})" 
                                        icon="o-chevron-down"
                                        class="size-8 min-h-0 p-0 bg-white/90 dark:bg-slate-800/90 hover:bg-white text-slate-600 dark:text-white rounded-xl shadow-sm border-none"
                                        :disabled="$index === count($facilities) - 1"
                                    />
                                </div>

                                {{-- Order Badge --}}
                                <div class="absolute left-4 top-4">
                                    <span class="px-3 py-1 bg-black/50 backdrop-blur-md text-[10px] font-bold text-white rounded-full border border-white/20">
                                        #{{ $index + 1 }}
                                    </span>
                                </div>
                            </div>

                             <div class="p-6">
                                <h4 class="font-bold text-lg text-slate-800 dark:text-white leading-tight mb-2 group-hover:text-primary transition-colors uppercase tracking-tight text-base">{{ $facility['name'] }}</h4>
                                @if ($facility['description'])
                                    <p class="text-xs text-slate-500 leading-relaxed line-clamp-2 mb-4">{{ $facility['description'] }}</p>
                                @endif
                                
                                <div class="flex items-center justify-between mt-auto pt-4 border-t border-slate-50 dark:border-slate-800/50">
                                    <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <x-ui.button 
                                            wire:click="edit({{ $facility['id'] }})" 
                                            icon="o-pencil"
                                            class="btn-ghost btn-xs text-slate-400 hover:text-primary"
                                        />
                                        <x-ui.button 
                                            wire:click="delete({{ $facility['id'] }})" 
                                            icon="o-trash"
                                            class="btn-ghost btn-xs text-slate-400 hover:text-rose-500"
                                            wire:confirm="{{ __('Hapus fasilitas ini secara permanen?') }}"
                                        />
                                    </div>
                                     <x-ui.button 
                                        wire:click="edit({{ $facility['id'] }})"
                                        :label="__('Kelola')"
                                        class="btn-ghost btn-xs text-primary font-bold uppercase tracking-widest text-[10px]"
                                    />
                                </div>
                            </div>
                        </x-ui.card>
                    @endforeach
                </div>
            @else
                <div class="flex flex-col items-center justify-center py-32 text-slate-300 dark:text-slate-700 border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-[32px] bg-slate-50/50 dark:bg-slate-900/50 transition-all">
                    <x-ui.icon name="o-building-office-2" class="size-20 mb-6 opacity-20" />
                    <p class="text-sm font-bold uppercase tracking-widest">{{ __('Belum Ada Fasilitas Terdaftar') }}</p>
                    <x-ui.button :label="__('Mulai Tambahkan Data')" wire:click="showAddForm" class="mt-8 btn-ghost text-primary btn-sm font-bold" />
                </div>
            @endif
        </div>
    @endif
</div>
