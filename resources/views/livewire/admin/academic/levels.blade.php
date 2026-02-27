<?php

declare(strict_types=1);

use App\Models\Level;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('components.admin.layouts.app')] class extends Component {
    public string $name = '';
    public string $type = 'class_teacher';
    public string $education_level = 'sd';
    public array $phase_map = [];
    public bool $levelModal = false;

    public ?Level $editing = null;

    public function createNew(): void
    {
        $this->reset(['name', 'type', 'education_level', 'phase_map', 'editing']);
        $this->updatedEducationLevel();
        $this->resetValidation();
        $this->levelModal = true;
    }

    public function updatedEducationLevel(): void
    {
        $this->phase_map = match($this->education_level) {
            'paud' => [],
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
        $this->levelModal = false;
    }

    public function edit(Level $level): void
    {
        $this->editing = $level;
        $this->name = $level->name;
        $this->type = $level->type;
        $this->education_level = $level->education_level ?? 'sd';
        $this->phase_map = $level->phase_map ?? [];
        $this->levelModal = true;
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
    <x-header title="Jenjang Pendidikan" subtitle="Atur jenjang SPP dan skema pengajaran (Guru Kelas vs Mata Pelajaran)." separator>
        <x-slot:actions>
            <x-button label="Tambah Jenjang" icon="o-plus" class="btn-primary" wire:click="createNew" wire:loading.attr="disabled" />
        </x-slot:actions>
    </x-header>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        @foreach ($levels as $level)
            <x-card wire:key="{{ $level->id }}" class="shadow-sm border border-base-200">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="text-lg font-bold">{{ $level->name }}</h3>
                        <div class="text-[10px] font-bold text-primary uppercase tracking-widest mt-1">
                            {{ match($level->education_level) {
                                'paud' => 'PAUD',
                                'sd' => 'Paket A',
                                'smp' => 'Paket B',
                                'sma' => 'Paket C',
                                default => 'Lainnya',
                            } }}
                        </div>
                    </div>
                    <x-badge 
                        :label="$level->type === 'class_teacher' ? 'Guru Kelas' : 'Guru Mapel'" 
                        class="{{ $level->type === 'class_teacher' ? 'badge-success' : 'badge-info' }} badge-sm" 
                    />
                </div>

                <div class="mt-4 text-xs opacity-60">
                    {{ $level->type === 'class_teacher' ? 'Satu guru mengampu semua mata pelajaran.' : 'Satu mata pelajaran diampu oleh satu guru spesialis.' }}
                </div>

                <x-slot:actions>
                    <x-button icon="o-pencil-square" wire:click="edit({{ $level->id }})" ghost sm wire:loading.attr="disabled" />
                    <x-button 
                        icon="o-trash" 
                        class="text-error" 
                        wire:confirm="Yakin ingin menghapus jenjang ini?" 
                        wire:click="delete({{ $level->id }})" 
                        ghost sm 
                    />
                </x-slot:actions>
            </x-card>
        @endforeach
    </div>

    <x-modal wire:model="levelModal" class="backdrop-blur" persistent>
        <x-header :title="$editing ? 'Edit Jenjang' : 'Tambah Jenjang Baru'" subtitle="Lengkapi detail jenjang pendidikan di bawah ini." separator />

        <form wire:submit="save">
            <div class="space-y-4">
                <x-input wire:model="name" label="Nama Jenjang (Contoh: PAUD Al-Ishlah, Paket B Utama)" required />

                <x-select 
                    wire:model.live="education_level" 
                    label="Tingkat Pendidikan" 
                    :options="[
                        ['id' => 'paud', 'name' => 'PAUD / TK'],
                        ['id' => 'sd', 'name' => 'SD / Paket A'],
                        ['id' => 'smp', 'name' => 'SMP / Paket B'],
                        ['id' => 'sma', 'name' => 'SMA / Paket C'],
                    ]" 
                    required 
                />

                <div class="space-y-2">
                    <div class="text-sm font-medium">Skema Pengajaran</div>
                    <x-radio 
                        wire:model="type" 
                        :options="[
                            ['id' => 'class_teacher', 'label' => 'Sistem Guru Kelas'],
                            ['id' => 'subject_teacher', 'label' => 'Sistem Guru Mata Pelajaran'],
                        ]"
                    />
                </div>

                @if(!empty($phase_map))
                    <x-alert title="Preview Tingkat Kelas (Kurikulum Merdeka)" icon="o-information-circle" class="bg-base-200 shadow-inner">
                        <div class="grid grid-cols-2 gap-x-4 gap-y-1 mt-2">
                            @foreach($phase_map as $grade => $phase)
                                <div class="text-[10px] flex justify-between">
                                    <span class="opacity-50">Tingkat {{ $grade }}</span>
                                    <span class="font-bold">Fase {{ $phase }}</span>
                                </div>
                            @endforeach
                        </div>
                    </x-alert>
                @endif
            </div>

            <x-slot:actions>
                <x-button label="Batal" @click="$set('levelModal', false)" />
                <x-button label="Simpan" type="submit" class="btn-primary" spinner="save" />
            </x-slot:actions>
        </form>
    </x-modal>
</div>
