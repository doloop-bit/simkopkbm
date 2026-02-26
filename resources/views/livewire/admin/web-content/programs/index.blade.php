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

<div class="p-6">
    <x-header title="Program Pendidikan" subtitle="Kelola profil program pendidikan untuk setiap jenjang" separator />

    @if (session()->has('message'))
        <x-alert title="Sukses" icon="o-check-circle" class="alert-success mb-6">
            {{ session('message') }}
        </x-alert>
    @endif

    {{-- Form --}}
    <x-card separator progress-indicator shadow title="{{ $editingId ? 'Edit Program' : 'Tambah Program Baru' }}">
        <form wire:submit="save" class="space-y-4">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-select 
                    wire:model="level_id" 
                    label="Jenjang Pendidikan" 
                    placeholder="Pilih Jenjang..."
                    :options="$levels"
                />

                <x-input 
                    wire:model="duration" 
                    label="Durasi" 
                    placeholder="Contoh: 6 bulan, 1 tahun"
                    icon="o-clock"
                />
            </div>

            <x-file 
                wire:model="image" 
                label="Gambar Program" 
                accept="image/jpeg,image/jpg,image/png,image/webp"
                crop-after-change
            >
                @php
                    $previewUrl = $image ? $image->temporaryUrl() : ($editingId && ($program = \App\Models\Program::find($editingId)) && $program->image_path ? Storage::url($program->image_path) : '/placeholder.png');
                @endphp
                <img src="{{ $previewUrl }}" class="h-40 rounded-lg object-cover border border-base-300 shadow-sm" />
            </x-file>

            <x-textarea 
                wire:model="description" 
                label="Deskripsi" 
                rows="4" 
                placeholder="Jelaskan tentang program ini..."
            />

            <x-textarea 
                wire:model="requirements" 
                label="Persyaratan (Opsional)" 
                rows="3" 
                placeholder="Persyaratan untuk mengikuti program ini..."
            />

            <x-checkbox wire:model="is_active" label="Program aktif (ditampilkan di website)" />

            <x-slot:actions>
                @if ($editingId)
                    <x-button wire:click="cancelEdit" label="Batal" ghost />
                @endif
                <x-button 
                    label="{{ $editingId ? 'Perbarui' : 'Simpan' }}"
                    class="btn-primary"
                    type="submit" 
                    spinner="save"
                />
            </x-slot:actions>
        </form>
    </x-card>

    {{-- Programs List --}}
    @if ($programs->isEmpty())
        <x-card class="mt-8 p-12 text-center" shadow>
            <x-icon name="o-folder-open" class="size-12 mb-3 opacity-20" />
            <p class="text-base-content/50">
                Belum ada program pendidikan. Tambahkan program pertama Anda!
            </p>
        </x-card>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-8">
            @foreach ($programs as $program)
                <x-card wire:key="program-{{ $program->id }}" shadow class="hover:shadow-lg transition-all border border-base-200">
                    <div class="flex flex-col items-center w-full text-center p-2">
                        {{-- Icon/Avatar --}}
                        <div class="mb-4 relative">
                            @if ($program->image_path)
                                <img 
                                    src="{{ Storage::url($program->image_path) }}" 
                                    alt="{{ $program->name }}"
                                    class="h-24 w-24 rounded-2xl object-cover shadow-sm border border-base-300"
                                >
                            @else
                                <div class="flex h-24 w-24 items-center justify-center rounded-2xl bg-primary text-primary-content text-3xl font-bold shadow-md">
                                    {{ substr($program->name, 0, 1) }}
                                </div>
                            @endif
                            
                            @if (!$program->is_active)
                                <div class="absolute -top-2 -right-2">
                                    <x-badge label="Nonaktif" class="badge-ghost badge-sm" />
                                </div>
                            @endif
                        </div>

                        {{-- Content --}}
                        <div class="space-y-1 w-full">
                            <h3 class="text-lg font-bold truncate">{{ $program->name }}</h3>
                            <p class="text-sm font-medium opacity-70">{{ $program->level?->name ?? '-' }}</p>
                            
                            <div class="flex items-center justify-center gap-2 mt-2">
                                <x-badge :label="$program->duration" class="badge-outline badge-sm" icon="o-clock" />
                            </div>

                            <p class="mt-4 text-sm line-clamp-3 text-base-content/70 h-15">
                                {{ $program->description }}
                            </p>

                            {{-- Action Buttons --}}
                            <div class="flex justify-between items-center mt-6 pt-4 border-t border-base-200">
                                <div class="flex gap-1">
                                    <x-button 
                                        wire:click="moveUp({{ $program->id }})" 
                                        icon="o-chevron-up" 
                                        class="btn-xs btn-ghost btn-circle"
                                        tooltip="Naikkan"
                                    />
                                    <x-button 
                                        wire:click="moveDown({{ $program->id }})" 
                                        icon="o-chevron-down" 
                                        class="btn-xs btn-ghost btn-circle"
                                        tooltip="Turunkan"
                                    />
                                </div>
                                
                                <div class="flex gap-1">
                                    <x-button 
                                        wire:click="edit({{ $program->id }})" 
                                        icon="o-pencil"
                                        class="btn-sm btn-ghost"
                                        tooltip="Edit"
                                    />
                                    <x-button 
                                        wire:click="delete({{ $program->id }})" 
                                        icon="o-trash"
                                        class="btn-sm btn-ghost text-error"
                                        wire:confirm="Apakah Anda yakin ingin menghapus program ini?"
                                        tooltip="Hapus"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </x-card>
            @endforeach
        </div>
    @endif
</div>