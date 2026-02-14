<?php

declare(strict_types=1);

use App\Models\Level;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.admin.layouts.app')] class extends Component {
    public string $name = '';
    public string $type = 'class_teacher';
    public string $education_level = 'sd';
    public array $phase_map = [];

    public ?Level $editing = null;

    public function createNew(): void
    {
        $this->reset(['name', 'type', 'education_level', 'phase_map', 'editing']);
        $this->updatedEducationLevel();
        $this->resetValidation();
    }

    public function updatedEducationLevel(): void
    {
        $this->phase_map = match($this->education_level) {
            'paud' => ['1' => 'A', '2' => 'B'],
            'sd' => ['1' => 'A', '2' => 'A', '3' => 'B', '4' => 'B', '5' => 'C', '6' => 'C'],
            'smp' => ['7' => 'D', '8' => 'D', '9' => 'D'],
            'sma' => ['10' => 'E', '11' => 'F', '12' => 'F'],
            default => [],
        };
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:class_teacher,subject_teacher'],
            'education_level' => ['required', 'in:paud,sd,smp,sma'],
            'phase_map' => ['nullable', 'array'],
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->editing) {
            $this->editing->update($validated);
        } else {
            Level::create($validated);
        }

        $this->reset(['name', 'type', 'education_level', 'phase_map', 'editing']);
        $this->dispatch('level-saved');
    }

    public function edit(Level $level): void
    {
        $this->editing = $level;
        $this->name = $level->name;
        $this->type = $level->type;
        $this->education_level = $level->education_level ?? 'sd';
        $this->phase_map = $level->phase_map ?? [];
    }

    public function delete(Level $level): void
    {
        $level->delete();
    }

    public function with(): array
    {
        return [
            'levels' => Level::all(),
        ];
    }
}; ?>

<div class="p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl" level="1">Jenjang Pendidikan</flux:heading>
            <flux:subheading>Atur jenjang SPP dan skema pengajaran (Guru Kelas vs Mata Pelajaran).</flux:subheading>
        </div>

        <flux:modal.trigger name="level-modal">
            <flux:button variant="primary" icon="plus" wire:click="createNew">Tambah Jenjang</flux:button>
        </flux:modal.trigger>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        @foreach ($levels as $level)
            <div wire:key="{{ $level->id }}" class="p-6 border rounded-xl border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex items-start justify-between">
                        <div>
                            <flux:heading size="lg">{{ $level->name }}</flux:heading>
                            <flux:text class="text-xs font-bold text-blue-600 uppercase">{{ $level->education_level === 'paud' ? 'PAUD' : 'Paket ' . strtoupper(substr($level->education_level, 0, 1)) }}</flux:text>
                        </div>
                        <flux:badge variant="{{ $level->type === 'class_teacher' ? 'success' : 'info' }}" size="sm">
                            {{ $level->type === 'class_teacher' ? 'Guru Kelas' : 'Guru Mapel' }}
                        </flux:badge>
                    </div>
                    <flux:text class="mt-2 text-sm">
                        {{ $level->type === 'class_teacher' ? 'Satu guru mengampu semua mata pelajaran.' : 'Satu mata pelajaran diampu oleh satu guru spesialis.' }}
                    </flux:text>
                    
                    @if($level->phase_map)
                        <div class="mt-4 flex flex-wrap gap-1">
                            @foreach($level->phase_map as $grade => $phase)
                                <flux:badge size="sm" inset color="zinc">Kelas {{ $grade }} ({{ $phase }})</flux:badge>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="edit({{ $level->id }})" x-on:click="$flux.modal('level-modal').show()" />
                    <flux:button size="sm" variant="ghost" icon="trash" class="text-red-500" wire:confirm="Yakin ingin menghapus jenjang ini?" wire:click="delete({{ $level->id }})" />
                </div>
            </div>
        @endforeach
    </div>

    <flux:modal name="level-modal" class="max-w-md" x-on:level-saved.window="$flux.modal('level-modal').close()">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $editing ? 'Edit Jenjang' : 'Tambah Jenjang Baru' }}</flux:heading>
                <flux:subheading>Lengkapi detail jenjang pendidikan di bawah ini.</flux:subheading>
            </div>

            <flux:input wire:model="name" label="Nama Jenjang (Contoh: PAUD Al-Ishlah, Paket B Utama)" required />

            <flux:select wire:model.live="education_level" label="Tingkat Pendidikan" required>
                <option value="paud">PAUD / TK</option>
                <option value="sd">SD / Paket A</option>
                <option value="smp">SMP / Paket B</option>
                <option value="sma">SMA / Paket C</option>
            </flux:select>

            <flux:radio.group wire:model="type" label="Skema Pengajaran" class="flex flex-col gap-2">
                <flux:radio value="class_teacher" label="Sistem Guru Kelas" />
                <flux:radio value="subject_teacher" label="Sistem Guru Mata Pelajaran" />
            </flux:radio.group>

            @if(!empty($phase_map))
                <div class="p-4 rounded-lg bg-zinc-50 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700">
                    <flux:heading size="sm" class="mb-2">Preview Tingkat Kelas (Kurikulum Merdeka)</flux:heading>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach($phase_map as $grade => $phase)
                            <div class="text-xs flex justify-between">
                                <span class="text-zinc-500">Tingkat {{ $grade }}</span>
                                <span class="font-bold">Fase {{ $phase }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Batal</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Simpan</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
