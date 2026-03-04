<?php

use App\Models\Level;
use App\Models\Program;
use App\Services\CacheService;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Async;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('components.admin.layouts.app')] class extends Component
{
    use WithFileUploads;

    public ?int $level_id = null;
    public string $description = '';
    public string $duration = '';
    public string $requirements = '';
    public $image = null;
    public bool $is_active = true;

    public ?int $editingId = null;

    public function rules(): array
    {
        return [
            'level_id' => 'required|exists:levels,id',
            'description' => 'required|string',
            'duration' => 'required|string|max:100',
            'requirements' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
            'is_active' => 'boolean',
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'level_id' => 'jenjang',
            'description' => 'deskripsi',
            'duration' => 'durasi',
            'requirements' => 'persyaratan',
            'image' => 'gambar',
        ];
    }

    public function save(): void
    {
        $this->validate();

        $level = Level::findOrFail($this->level_id);

        $data = [
            'level_id' => $this->level_id,
            'name' => $level->name,
            'slug' => \Illuminate\Support\Str::slug($level->name),
            'description' => $this->description,
            'duration' => $this->duration,
            'requirements' => $this->requirements,
            'is_active' => $this->is_active,
        ];

        // Handle image upload
        if ($this->image) {
            $filename = uniqid() . '.' . $this->image->extension();
            $originalPath = $this->image->storeAs('programs', $filename, 'public');
            $data['image_path'] = $originalPath;
        }

        if ($this->editingId) {
            $program = Program::findOrFail($this->editingId);
            
            // Delete old images if new image uploaded
            if ($this->image && $program->image_path) {
                Storage::disk('public')->delete($program->image_path);
            }
            
            $program->update($data);
            session()->flash('message', 'Program berhasil diperbarui.');
        } else {
            // Check if this level already has a program
            if (Program::where('level_id', $this->level_id)->exists()) {
                $this->addError('level_id', 'Jenjang ini sudah memiliki program.');

                return;
            }

            // Get the next order value
            $maxOrder = Program::max('order') ?? 0;
            $data['order'] = $maxOrder + 1;
            
            Program::create($data);
            session()->flash('message', 'Program berhasil ditambahkan.');
        }

        // Clear programs cache after save
        $cacheService = app(CacheService::class);
        $cacheService->clearProgramsCache();

        $this->reset();
    }

    public function edit(int $id): void
    {
        $program = Program::findOrFail($id);
        
        $this->editingId = $id;
        $this->level_id = $program->level_id;
        $this->description = $program->description;
        $this->duration = $program->duration;
        $this->requirements = $program->requirements ?? '';
        $this->is_active = $program->is_active;
    }

    public function cancelEdit(): void
    {
        $this->reset();
    }

    public function delete(int $id): void
    {
        $program = Program::findOrFail($id);
        
        // Delete images
        if ($program->image_path) {
            Storage::disk('public')->delete($program->image_path);
        }
        
        $program->delete();
        
        // Clear programs cache after deletion
        $cacheService = app(CacheService::class);
        $cacheService->clearProgramsCache();
        
        session()->flash('message', 'Program berhasil dihapus.');
    }

    #[Async]
    public function moveUp(int $id): void
    {
        $program = Program::findOrFail($id);
        $previousProgram = Program::where('order', '<', $program->order)
            ->orderBy('order', 'desc')
            ->first();

        if ($previousProgram) {
            $tempOrder = $program->order;
            $program->order = $previousProgram->order;
            $previousProgram->order = $tempOrder;

            $program->save();
            $previousProgram->save();
        }
    }

    #[Async]
    public function moveDown(int $id): void
    {
        $program = Program::findOrFail($id);
        $nextProgram = Program::where('order', '>', $program->order)
            ->orderBy('order', 'asc')
            ->first();

        if ($nextProgram) {
            $tempOrder = $program->order;
            $program->order = $nextProgram->order;
            $nextProgram->order = $tempOrder;

            $program->save();
            $nextProgram->save();
        }
    }

    public function with(): array
    {
        return [
            'programs' => Program::with('level')->ordered()->get(),
            'levels' => Level::all(),
            'usedLevelIds' => Program::when($this->editingId, fn ($q) => $q->where('id', '!=', $this->editingId))
                ->pluck('level_id')
                ->toArray(),
        ];
    }
}; ?>

<div class="p-6 space-y-8 text-slate-900 dark:text-white pb-24 md:pb-6">
    @if (session()->has('message'))
        <x-ui.alert :title="__('Sukses')" icon="o-check-circle" class="bg-emerald-50 text-emerald-800 border-emerald-100" dismissible>
            {{ session('message') }}
        </x-ui.alert>
    @endif

    <x-ui.header :title="__('Kurikulum & Program')" :subtitle="__('Kelola profil program pendidikan, kurikulum, dan durasi belajar setiap jenjang.')" separator />

    {{-- Form --}}
    <x-ui.card shadow padding="false" class="border-none ring-1 ring-slate-100 dark:ring-slate-800">
        <div class="p-6 border-b border-slate-50 dark:border-slate-800/50 bg-slate-50/50 dark:bg-slate-900/50">
            <h3 class="font-bold text-slate-800 dark:text-white uppercase tracking-wider text-xs">
                {{ $editingId ? __('Modifikasi Detail Program') : __('Registrasi Program Pendidikan Baru') }}
            </h3>
        </div>
        <div class="p-8">
            <form wire:submit="save" class="space-y-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <x-ui.select 
                        wire:model="level_id" 
                        :label="__('Target Jenjang Pendidikan')" 
                        :placeholder="__('Pilih jenjang akademik...')"
                        :options="$levels"
                        class="tracking-tight"
                    />

                    <x-ui.input 
                        wire:model="duration" 
                        :label="__('Estimasi Durasi Belajar')" 
                        :placeholder="__('Contoh: 6 Bulan / 1 Semester')"
                        icon="o-clock"
                        class="font-medium"
                    />
                </div>

                <div class="space-y-4 max-w-md">
                    <x-ui.file 
                        wire:model="image" 
                        :label="__('Ilustrasi / Foto Program')" 
                        accept="image/jpeg,image/jpg,image/png,image/webp"
                    >
                        @php
                            $previewUrl = $image ? $image->temporaryUrl() : ($editingId && ($program = \App\Models\Program::find($editingId)) && $program->image_path ? Storage::url($program->image_path) : '/placeholder.png');
                        @endphp
                        <div class="mt-4 relative group">
                            <img src="{{ $previewUrl }}" class="h-48 w-80 rounded-[2rem] object-cover border-4 border-white dark:border-slate-700 shadow-2xl group-hover:scale-105 transition-transform duration-500" />
                            <div class="absolute inset-0 rounded-[2rem] bg-gradient-to-t from-black/20 to-transparent"></div>
                        </div>
                    </x-ui.file>
                    <p class="text-xs text-slate-400 px-1 leading-relaxed">
                        * {{ __('Format: JPG, PNG, WebP (Maksimal 2MB). Gunakan gambar dengan resolusi tinggi untuk hasil terbaik.') }}
                    </p>
                </div>

                <x-ui.textarea 
                    wire:model="description" 
                    :label="__('Deskripsi Program & Keunggulan')" 
                    rows="6" 
                    :placeholder="__('Jelaskan visi, misi, dan nilai tambah program ini...')"
                    class="font-medium text-slate-700 dark:text-slate-300 leading-relaxed"
                />

                <x-ui.textarea 
                    wire:model="requirements" 
                    :label="__('Kualifikasi / Persyaratan Pendaftaran (Opsional)')" 
                    rows="3" 
                    :placeholder="__('Sebutkan dokumen atau kriteria yang harus dipenuhi calon peserta...')"
                    class="text-sm leading-relaxed"
                />

                <div class="flex items-center gap-3 p-4 bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-2xl">
                    <x-ui.checkbox wire:model="is_active" :label="__('Status Aktif (Tampilkan di web)')" />
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-widest">{{ __('Aktifkan publikasi program di portal sekolah') }}</span>
                </div>

                <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-50 dark:border-slate-800">
                    @if ($editingId)
                        <x-ui.button wire:click="cancelEdit" :label="__('Batalkan Edit')" class="btn-ghost" />
                    @endif
                    <x-ui.button 
                        :label="$editingId ? __('Simpan Perubahan') : __('Tambahkan Program')"
                        class="btn-primary shadow-xl shadow-primary/20 px-8"
                        type="submit" 
                        spinner="save"
                    />
                </div>
            </form>
        </div>
    </x-ui.card>

    {{-- Programs List --}}
    <div class="space-y-6">
        <div class="flex items-center justify-between px-2">
            <h3 class="font-bold text-slate-800 dark:text-white uppercase tracking-wider text-xs">{{ __('Katalog Program Pendidikan') }}</h3>
            <x-ui.badge :label="$programs->count() . ' ' . __('Program')" class="bg-indigo-50 text-indigo-600 border-none font-bold text-[10px]" />
        </div>

        @if ($programs->isEmpty())
            <div class="flex flex-col items-center justify-center py-32 text-slate-300 dark:text-slate-700 border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-[32px] bg-slate-50/50 dark:bg-slate-900/50 transition-all text-center px-6">
                <x-ui.icon name="o-folder-open" class="size-20 mb-6 opacity-20" />
                <p class="text-sm font-semibold uppercase tracking-widest">{{ __('Belum Ada Program Terdefinisi') }}</p>
                <p class="text-xs text-slate-400 uppercase tracking-widest mt-2">{{ __('Gunakan formulir di atas untuk mendaftarkan program pendidikan pertama.') }}</p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach ($programs as $program)
                    <x-ui.card wire:key="program-{{ $program->id }}" shadow padding="false" class="group relative overflow-hidden border-none ring-1 ring-slate-100 dark:ring-slate-800 hover:ring-primary/20 transition-all duration-500 bg-white">
                        {{-- Visual Header --}}
                        <div class="h-48 relative overflow-hidden bg-slate-100 dark:bg-slate-900 border-b border-slate-50 dark:border-slate-800">
                            @if ($program->image_path)
                                <img 
                                    src="{{ Storage::url($program->image_path) }}" 
                                    alt="{{ $program->name }}"
                                    class="absolute inset-0 size-full object-cover transition-transform duration-700 group-hover:scale-110"
                                >
                            @else
                                <div class="absolute inset-0 flex items-center justify-center bg-indigo-500/10">
                                    <span class="text-6xl font-black text-indigo-500 opacity-20 italic">{{ substr($program->name, 0, 1) }}</span>
                                </div>
                            @endif

                            <div class="absolute top-4 left-4 z-10 flex flex-col gap-2">
                                <x-ui.badge :label="$program->level?->name ?? 'GENERAL'" class="bg-black/40 backdrop-blur-md text-white border-white/20 font-bold text-[9px] px-3 py-1 uppercase tracking-wider" />
                                @if (!$program->is_active)
                                    <x-ui.badge :label="__('NON-AKTIF')" class="bg-rose-500 text-white border-none font-bold text-[8px] px-2 py-0.5" />
                                @endif
                            </div>

                            <div class="absolute bottom-4 right-4 z-10">
                                <x-ui.badge :label="$program->duration" class="bg-white/90 backdrop-blur-sm text-slate-800 border-none font-bold text-[9px] px-3 py-1 shadow-sm ring-1 ring-slate-100 uppercase" icon="o-clock" />
                            </div>

                            <div class="absolute inset-0 bg-gradient-to-t from-slate-900/40 to-transparent"></div>
                        </div>

                        <div class="p-6">
                            <h3 class="font-bold text-lg text-slate-900 dark:text-white leading-tight mb-2 group-hover:text-primary transition-colors uppercase tracking-tight">{{ $program->name }}</h3>
                            <p class="text-xs text-slate-500 leading-relaxed line-clamp-3 mb-6 font-medium">{{ $program->description }}</p>

                            {{-- Advanced Control Panel --}}
                            <div class="flex items-center justify-between pt-4 border-t border-slate-50 dark:border-slate-800/50">
                                <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-all duration-300">
                                    <x-ui.button 
                                        wire:click="moveUp({{ $program->id }})" 
                                        icon="o-chevron-up" 
                                        class="size-8 min-h-0 p-0 btn-ghost text-slate-400 hover:text-indigo-500"
                                    />
                                    <x-ui.button 
                                        wire:click="moveDown({{ $program->id }})" 
                                        icon="o-chevron-down" 
                                        class="size-8 min-h-0 p-0 btn-ghost text-slate-400 hover:text-indigo-500"
                                    />
                                </div>
                                
                                <div class="flex items-center gap-1">
                                    <x-ui.button 
                                        wire:click="edit({{ $program->id }})" 
                                        icon="o-pencil"
                                        class="size-8 min-h-0 p-0 btn-ghost text-slate-300 hover:text-primary"
                                    />
                                    <x-ui.button 
                                        wire:click="delete({{ $program->id }})" 
                                        icon="o-trash"
                                        class="size-8 min-h-0 p-0 btn-ghost text-slate-300 hover:text-rose-500"
                                        wire:confirm="__('Hapus deskripsi program pendidikan ini secara permanen?') "
                                    />
                                </div>
                            </div>
                        </div>
                    </x-ui.card>
                @endforeach
            </div>
        @endif
    </div>
</div>