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

<div class="p-6 space-y-6 text-slate-900 dark:text-white">
    <x-ui.header :title="__('Jenjang Pendidikan')" :subtitle="__('Atur jenjang SPP dan skema pengajaran (Guru Kelas vs Mata Pelajaran).')" separator>
        <x-slot:actions>
            <x-ui.button :label="__('Tambah Jenjang')" icon="o-plus" class="btn-primary" wire:click="createNew" wire:loading.attr="disabled" />
        </x-slot:actions>
    </x-ui.header>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        @foreach ($levels as $level)
            <x-ui.card wire:key="{{ $level->id }}" shadow>
                <div class="flex items-start justify-between min-h-[4rem]">
                    <div class="flex-1 pr-2">
                        <h3 class="text-lg font-bold leading-tight line-clamp-2">{{ $level->name }}</h3>
                        <div class="text-[10px] font-black text-primary uppercase tracking-[0.2em] mt-2 bg-primary/5 px-2 py-0.5 rounded-full inline-block">
                            {{ match($level->education_level) {
                                'paud' => __('PAUD'),
                                'sd' => __('Paket A'),
                                'smp' => __('Paket B'),
                                'sma' => __('Paket C'),
                                default => __('Lainnya'),
                            } }}
                        </div>
                    </div>
                    <x-ui.badge 
                        :label="$level->type === 'class_teacher' ? __('Guru Kelas') : __('Guru Mapel')" 
                        class="{{ $level->type === 'class_teacher' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' }} text-[10px] font-bold" 
                    />
                </div>

                <div class="mt-4 text-[11px] font-medium text-slate-500 dark:text-slate-400 leading-relaxed italic opacity-80">
                    {{ $level->type === 'class_teacher' ? __('Satu guru mengampu semua mata pelajaran.') : __('Satu mata pelajaran diampu oleh satu guru spesialis.') }}
                </div>

                <x-slot:actions>
                    <div class="flex items-center gap-1">
                        <x-ui.button icon="o-pencil-square" wire:click="edit({{ $level->id }})" ghost sm wire:loading.attr="disabled" />
                        <x-ui.button 
                            icon="o-trash" 
                            class="text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20" 
                            wire:confirm="{{ __('Yakin ingin menghapus jenjang ini?') }}" 
                            wire:click="delete({{ $level->id }})" 
                            ghost sm 
                        />
                    </div>
                </x-slot:actions>
            </x-ui.card>
        @endforeach
    </div>

    <x-ui.modal wire:model="levelModal" persistent>
        <x-ui.header :title="$editing ? __('Edit Jenjang') : __('Tambah Jenjang Baru')" :subtitle="__('Lengkapi detail jenjang pendidikan di bawah ini.')" separator />

        <form wire:submit="save" class="space-y-6">
            <x-ui.input wire:model="name" :label="__('Nama Jenjang (Contoh: PAUD Al-Ishlah, Paket B Utama)')" required />

            <x-ui.select 
                wire:model.live="education_level" 
                :label="__('Tingkat Pendidikan')" 
                :options="[
                    ['id' => 'paud', 'name' => __('PAUD / TK')],
                    ['id' => 'sd', 'name' => __('SD / Paket A')],
                    ['id' => 'smp', 'name' => __('SMP / Paket B')],
                    ['id' => 'sma', 'name' => __('SMA / Paket C')],
                ]" 
                option-label="name"
                required 
            />

            <div class="space-y-3">
                <div class="text-sm font-bold text-slate-900 dark:text-white">{{ __('Skema Pengajaran') }}</div>
                <x-ui.radio 
                    wire:model="type" 
                    :options="[
                        ['id' => 'class_teacher', 'label' => __('Sistem Guru Kelas')],
                        ['id' => 'subject_teacher', 'label' => __('Sistem Guru Mata Pelajaran')],
                    ]"
                />
            </div>

            @if(!empty($phase_map))
                <x-ui.alert :title="__('Preview Tingkat Kelas (Kurikulum Merdeka)')" icon="o-information-circle" class="bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700">
                    <div class="grid grid-cols-2 gap-x-6 gap-y-2 mt-3 p-3 bg-white dark:bg-slate-900 rounded-xl border border-slate-100 dark:border-slate-800 shadow-inner">
                        @foreach($phase_map as $grade => $phase)
                            <div class="text-[10px] flex justify-between items-center border-b border-dotted border-slate-200 dark:border-slate-700 pb-1">
                                <span class="text-slate-500 font-medium">{{ __('Tingkat :grade', ['grade' => $grade]) }}</span>
                                <span class="font-black text-primary">{{ __('Fase :phase', ['phase' => $phase]) }}</span>
                            </div>
                        @endforeach
                    </div>
                </x-ui.alert>
            @endif

            <div class="flex justify-end gap-2 pt-4">
                <x-ui.button :label="__('Batal')" ghost @click="show = false" />
                <x-ui.button :label="__('Simpan')" type="submit" class="btn-primary" spinner="save" />
            </div>
        </form>
    </x-ui.modal>
</div>
