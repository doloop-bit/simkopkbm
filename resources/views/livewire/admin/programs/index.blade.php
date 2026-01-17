<?php

use App\Models\Program;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public string $name = '';
    public string $description = '';
    public string $level = '';
    public string $duration = '';
    public string $requirements = '';
    public $image = null;
    public bool $is_active = true;

    public ?int $editingId = null;

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'level' => 'required|string|max:100',
            'duration' => 'required|string|max:100',
            'requirements' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
            'is_active' => 'boolean',
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'name' => 'nama program',
            'description' => 'deskripsi',
            'level' => 'tingkat',
            'duration' => 'durasi',
            'requirements' => 'persyaratan',
            'image' => 'gambar',
        ];
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'slug' => \Illuminate\Support\Str::slug($this->name),
            'description' => $this->description,
            'level' => $this->level,
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
            // Get the next order value
            $maxOrder = Program::max('order') ?? 0;
            $data['order'] = $maxOrder + 1;
            
            Program::create($data);
            session()->flash('message', 'Program berhasil ditambahkan.');
        }

        $this->reset();
    }

    public function edit(int $id): void
    {
        $program = Program::findOrFail($id);
        
        $this->editingId = $id;
        $this->name = $program->name;
        $this->description = $program->description;
        $this->level = $program->level;
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
            'programs' => Program::ordered()->get(),
        ];
    }
}; ?>

<div>
    <div class="mb-6">
        <flux:heading size="xl">Program Pendidikan</flux:heading>
        <flux:subheading>Kelola program-program pendidikan sekolah</flux:subheading>
    </div>

    @if (session()->has('message'))
        <flux:callout color="green" icon="check-circle" class="mb-6">
            {{ session('message') }}
        </flux:callout>
    @endif

    {{-- Form --}}
    <div class="mb-8 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
        <flux:heading size="lg" class="mb-4">
            {{ $editingId ? 'Edit Program' : 'Tambah Program Baru' }}
        </flux:heading>

        <form wire:submit="save" class="space-y-4">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <flux:input 
                    wire:model="name" 
                    label="Nama Program" 
                    type="text" 
                    required 
                    placeholder="Contoh: Paket A, PAUD"
                />

                <flux:input 
                    wire:model="level" 
                    label="Tingkat" 
                    type="text" 
                    required 
                    placeholder="Contoh: Setara SD, Setara SMP"
                />
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <flux:input 
                    wire:model="duration" 
                    label="Durasi" 
                    type="text" 
                    required 
                    placeholder="Contoh: 6 bulan, 1 tahun"
                />

                <flux:input 
                    wire:model="image" 
                    label="Gambar Program" 
                    type="file" 
                    accept="image/jpeg,image/jpg,image/png,image/webp"
                />
            </div>

            <flux:textarea 
                wire:model="description" 
                label="Deskripsi" 
                rows="4" 
                required 
                placeholder="Jelaskan tentang program ini..."
            />

            <flux:textarea 
                wire:model="requirements" 
                label="Persyaratan (Opsional)" 
                rows="3" 
                placeholder="Persyaratan untuk mengikuti program ini..."
            />

            <div class="flex items-center gap-2">
                <flux:checkbox wire:model="is_active" />
                <flux:text>Program aktif (ditampilkan di website)</flux:text>
            </div>

            <div class="flex justify-end gap-3">
                @if ($editingId)
                    <flux:button wire:click="cancelEdit" variant="ghost">
                        Batal
                    </flux:button>
                @endif
                <flux:button 
                    variant="primary" 
                    type="submit" 
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove>{{ $editingId ? 'Perbarui' : 'Simpan' }}</span>
                    <span wire:loading>Menyimpan...</span>
                </flux:button>
            </div>
        </form>
    </div>

    {{-- Programs List --}}
    @if ($programs->isEmpty())
        <div class="rounded-lg border border-zinc-200 bg-white p-12 text-center dark:border-zinc-700 dark:bg-zinc-800">
            <flux:text class="text-zinc-500 dark:text-zinc-400">
                Belum ada program pendidikan. Tambahkan program pertama Anda!
            </flux:text>
        </div>
    @else
        <div class="space-y-4">
            @foreach ($programs as $program)
                <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="flex items-start gap-6">
                        {{-- Image --}}
                        @if ($program->image_path)
                            <div class="flex-shrink-0">
                                <img 
                                    src="{{ Storage::url($program->image_path) }}" 
                                    alt="{{ $program->name }}"
                                    class="h-24 w-32 rounded-lg object-cover"
                                >
                            </div>
                        @endif

                        {{-- Content --}}
                        <div class="flex-1">
                            <div class="mb-2 flex items-start justify-between">
                                <div>
                                    <flux:heading size="lg" class="mb-1">{{ $program->name }}</flux:heading>
                                    <div class="flex items-center gap-4 text-sm text-zinc-600 dark:text-zinc-400">
                                        <span>{{ $program->level }}</span>
                                        <span>•</span>
                                        <span>{{ $program->duration }}</span>
                                        <span>•</span>
                                        @if ($program->is_active)
                                            <flux:badge color="green" size="sm">Aktif</flux:badge>
                                        @else
                                            <flux:badge color="zinc" size="sm">Tidak Aktif</flux:badge>
                                        @endif
                                    </div>
                                </div>

                                {{-- Action Buttons --}}
                                <div class="flex gap-2">
                                    <flux:button 
                                        wire:click="moveUp({{ $program->id }})" 
                                        variant="ghost" 
                                        size="sm"
                                        title="Pindah ke atas"
                                    >
                                        ↑
                                    </flux:button>
                                    <flux:button 
                                        wire:click="moveDown({{ $program->id }})" 
                                        variant="ghost" 
                                        size="sm"
                                        title="Pindah ke bawah"
                                    >
                                        ↓
                                    </flux:button>
                                    <flux:button 
                                        wire:click="edit({{ $program->id }})" 
                                        variant="ghost" 
                                        size="sm"
                                    >
                                        Edit
                                    </flux:button>
                                    <flux:button 
                                        wire:click="delete({{ $program->id }})" 
                                        variant="danger" 
                                        size="sm"
                                        wire:confirm="Apakah Anda yakin ingin menghapus program ini?"
                                    >
                                        Hapus
                                    </flux:button>
                                </div>
                            </div>

                            <flux:text class="mb-3">{{ $program->description }}</flux:text>

                            @if ($program->requirements)
                                <div class="rounded-lg bg-zinc-50 p-3 dark:bg-zinc-900">
                                    <flux:text class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                        Persyaratan:
                                    </flux:text>
                                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                                        {{ $program->requirements }}
                                    </flux:text>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>