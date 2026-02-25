<?php

use App\Models\Level;
use App\Models\Program;
use App\Services\CacheService;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
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

<div>
    <div class="mb-6">
        <x-ts-text size="xl" color="neutral" weight="bold">Program Pendidikan</x-ts-text>
        <x-ts-text size="sm" color="neutral">Kelola profil program pendidikan untuk setiap jenjang</x-ts-text>
    </div>

    @if (session()->has('message'))
        <x-ts-alert title="Sukses" text="{{ session('message') }}" color="green" icon="check-circle" class="mb-6" close />
    @endif

    {{-- Form --}}
    <x-ts-card class="mb-8">
        <x-slot:header>
            <x-ts-text size="lg" weight="bold">
                {{ $editingId ? 'Edit Program' : 'Tambah Program Baru' }}
            </x-ts-text>
        </x-slot:header>

        <form wire:submit="save" class="space-y-4">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-ts-select.native 
                    wire:model="level_id" 
                    label="Jenjang Pendidikan" 
                    required 
                    placeholder="Pilih Jenjang..."
                >
                    <option value="">Pilih Jenjang...</option>
                    @foreach ($levels as $level)
                        @php $isUsed = in_array($level->id, $usedLevelIds); @endphp
                        <option 
                            wire:key="level-{{ $level->id }}"
                            value="{{ $level->id }}" 
                            {{ $isUsed ? 'disabled' : '' }}
                        >
                            {{ $level->name }} {{ $isUsed ? '(sudah ada program)' : '' }}
                        </option>
                    @endforeach
                </x-ts-select.native>

                <x-ts-input 
                    wire:model="duration" 
                    label="Durasi" 
                    required 
                    placeholder="Contoh: 6 bulan, 1 tahun"
                />
            </div>

            <x-ts-input 
                wire:model="image" 
                label="Gambar Program" 
                type="file" 
                accept="image/jpeg,image/jpg,image/png,image/webp"
            />

            <x-ts-textarea 
                wire:model="description" 
                label="Deskripsi" 
                rows="4" 
                required 
                placeholder="Jelaskan tentang program ini..."
            />

            <x-ts-textarea 
                wire:model="requirements" 
                label="Persyaratan (Opsional)" 
                rows="3" 
                placeholder="Persyaratan untuk mengikuti program ini..."
            />

            <div class="flex items-center gap-2">
                <x-ts-checkbox wire:model="is_active" label="Program aktif (ditampilkan di website)" />
            </div>

            <div class="flex justify-end gap-3">
                @if ($editingId)
                    <x-ts-button wire:click="cancelEdit" color="neutral" variant="ghost">
                        Batal
                    </x-ts-button>
                @endif
                <x-ts-button 
                    color="primary" 
                    type="submit" 
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove>{{ $editingId ? 'Perbarui' : 'Simpan' }}</span>
                    <span wire:loading>Menyimpan...</span>
                </x-ts-button>
            </div>
        </form>
    </x-ts-card>

    {{-- Programs List --}}
    @if ($programs->isEmpty())
        <x-ts-card class="p-12 text-center">
            <x-ts-text color="neutral">
                Belum ada program pendidikan. Tambahkan program pertama Anda!
            </x-ts-text>
        </x-ts-card>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 justify-center">
            @foreach ($programs as $program)
                <x-ts-card wire:key="program-{{ $program->id }}" class="flex flex-col items-center">
                    <div class="flex flex-col items-center w-full text-center">
                        {{-- Icon/Avatar --}}
                        <div class="mb-4">
                            @if ($program->image_path)
                                <img 
                                    src="{{ Storage::url($program->image_path) }}" 
                                    alt="{{ $program->name }}"
                                    class="h-24 w-24 rounded-2xl object-cover shadow-sm border border-zinc-200 dark:border-zinc-700"
                                >
                            @else
                                <div class="flex h-20 w-20 items-center justify-center rounded-2xl bg-zinc-900 text-2xl font-bold text-white shadow-md">
                                    {{ substr($program->name, 0, 1) }}
                                </div>
                            @endif
                        </div>

                        {{-- Content --}}
                        <div class="space-y-2 w-full">
                            <x-ts-text size="lg" weight="bold" class="block truncate">{{ $program->name }}</x-ts-text>
                            
                            <div class="flex flex-col items-center gap-2">
                                <x-ts-text size="sm" color="neutral" weight="medium">{{ $program->level?->name ?? '-' }}</x-ts-text>
                                <div class="flex items-center gap-2">
                                    <x-ts-badge color="neutral" light size="sm">{{ $program->duration }}</x-ts-badge>
                                    @if ($program->is_active)
                                        <x-ts-badge color="green" size="sm">Aktif</x-ts-badge>
                                    @else
                                        <x-ts-badge color="neutral" size="sm">Tidak Aktif</x-ts-badge>
                                    @endif
                                </div>
                            </div>

                            <x-ts-text size="sm" class="mt-4 line-clamp-2 text-zinc-600 dark:text-zinc-400">
                                {{ $program->description }}
                            </x-ts-text>

                            {{-- Action Buttons --}}
                            <div class="flex justify-center gap-2 mt-6">
                                <div class="flex gap-1 mr-2 border-r pr-2 border-zinc-200 dark:border-zinc-700">
                                    <x-ts-button.circle 
                                        wire:click="moveUp({{ $program->id }})" 
                                        icon="chevron-up" 
                                        size="xs"
                                        color="neutral"
                                        variant="ghost"
                                        title="Pindah ke atas"
                                    />
                                    <x-ts-button.circle 
                                        wire:click="moveDown({{ $program->id }})" 
                                        icon="chevron-down" 
                                        size="xs"
                                        color="neutral"
                                        variant="ghost"
                                        title="Pindah ke bawah"
                                    />
                                </div>
                                
                                <x-ts-button 
                                    wire:click="edit({{ $program->id }})" 
                                    size="sm"
                                    color="neutral"
                                    variant="outline"
                                >
                                    Edit
                                </x-ts-button>
                                <x-ts-button 
                                    wire:click="delete({{ $program->id }})" 
                                    size="sm"
                                    color="red"
                                    variant="ghost"
                                    wire:confirm="Apakah Anda yakin ingin menghapus program ini?"
                                >
                                    Hapus
                                </x-ts-button>
                            </div>
                        </div>
                    </div>
                </x-ts-card>
            @endforeach
        </div>
    @endif
</div>