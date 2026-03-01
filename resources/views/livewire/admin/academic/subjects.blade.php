<?php

declare(strict_types=1);

use App\Models\Subject;
use App\Models\SubjectTp;
use App\Models\Level;
use App\Models\LearningAchievement;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('components.admin.layouts.app')] class extends Component {
    use WithPagination;

    public string $name = '';
    public string $code = '';
    public ?string $phase = null; // Replaces level_id
    
    // Filters
    public string $search = '';
    public ?string $filterPhase = null; // Replaces filterLevelId
    
    // Modals
    public bool $subjectModal = false;
    public bool $tpModal = false;

    // TP Management
    public ?Subject $managingSubject = null;
    public $subjectTps = [];
    public $tpCode = '';
    public $tpDescription = '';
    public ?int $editingTpId = null;

    // CP selection for TP management (Phase is now fixed per subject)
    public ?int $selectedCpId = null;
    public ?string $selectedCpDescription = null;

    public ?Subject $editing = null;

    // Constants
    public array $phases = ['Fondasi', 'A', 'B', 'C', 'D', 'E', 'F'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterPhase(): void
    {
        $this->resetPage();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:subjects,code,' . ($this->editing->id ?? 'NULL')],
            'phase' => ['required', 'string', 'in:Fondasi,A,B,C,D,E,F'],
        ];
    }
    
    public function createNew(): void
    {
        $this->reset(['name', 'code', 'phase', 'editing']);
        $this->subjectModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->editing) {
            $this->editing->update($validated);
        } else {
            Subject::create($validated);
        }

        $this->reset(['name', 'code', 'phase', 'editing']);
        $this->subjectModal = false;
        session()->flash('success', 'Mata pelajaran berhasil disimpan.');
    }

    public function edit(Subject $subject): void
    {
        $this->editing = $subject;
        $this->name = $subject->name;
        $this->code = $subject->code;
        $this->phase = $subject->phase;

        $this->subjectModal = true;
    }

    public function delete(Subject $subject): void
    {
        $subject->delete();
        session()->flash('success', 'Mata pelajaran berhasil dihapus.');
    }

    public function manageTps(Subject $subject): void
    {
        $this->managingSubject = $subject;
        $this->selectedCpId = null;
        $this->selectedCpDescription = null;
        $this->subjectTps = [];
        $this->cancelEditTp();

        $this->loadTps();

        $this->tpModal = true;
    }

    public function loadTps(): void
    {
        if (!$this->managingSubject || !$this->managingSubject->phase) {
            $this->subjectTps = [];
            $this->selectedCpId = null;
            $this->selectedCpDescription = null;
            return;
        }

        // Find or create the CP for this subject + phase
        $cp = LearningAchievement::firstOrCreate(
            [
                'subject_id' => $this->managingSubject->id,
                'phase' => $this->managingSubject->phase,
            ],
            [
                'description' => "CP Fase {$this->managingSubject->phase} - {$this->managingSubject->name}",
            ]
        );

        $this->selectedCpId = $cp->id;
        $this->selectedCpDescription = $cp->description;
        $this->subjectTps = $cp->tps()->orderBy('code')->get();
    }

    public function updateCpDescription(): void
    {
        if (!$this->selectedCpId || !$this->selectedCpDescription) return;

        LearningAchievement::find($this->selectedCpId)?->update([
            'description' => $this->selectedCpDescription,
        ]);

        session()->flash('success', 'Deskripsi CP berhasil diperbarui.');
    }

    public function saveTp(): void
    {
        $this->validate([
            'tpCode' => ['nullable', 'string', 'max:50'],
            'tpDescription' => ['required', 'string'],
        ]);

        if (!$this->selectedCpId) {
            session()->flash('error', 'CP tidak ditemukan.');
            return;
        }

        if ($this->editingTpId) {
            SubjectTp::find($this->editingTpId)?->update([
                'code' => $this->tpCode,
                'description' => $this->tpDescription,
            ]);
        } else {
            SubjectTp::create([
                'learning_achievement_id' => $this->selectedCpId,
                'code' => $this->tpCode,
                'description' => $this->tpDescription,
            ]);
        }

        $this->cancelEditTp();
        $this->loadTps();
        session()->flash('success', 'TP berhasil disimpan.');
    }

    public function editTp($id): void
    {
        $tp = SubjectTp::find($id);
        $this->editingTpId = $id;
        $this->tpCode = $tp->code;
        $this->tpDescription = $tp->description;
    }

    public function deleteTp($id): void
    {
        SubjectTp::find($id)?->delete();
        $this->loadTps();
        session()->flash('success', 'TP berhasil dihapus.');
    }

    public function cancelEditTp(): void
    {
        $this->editingTpId = null;
        $this->tpCode = '';
        $this->tpDescription = '';
    }

    public function with(): array
    {
        return [
            'subjects' => Subject::query()
                ->when($this->search, function ($query) {
                    $query->where(function ($q) {
                        $q->where('name', 'like', '%' . $this->search . '%')
                          ->orWhere('code', 'like', '%' . $this->search . '%');
                    });
                })
                ->when($this->filterPhase, fn($q) => $q->where('phase', $this->filterPhase))
                ->latest()
                ->paginate(15),
        ];
    }
}; ?>

<div class="p-6 space-y-6 text-slate-900 dark:text-white">
    <x-ui.header :title="__('Mata Pelajaran')" :subtitle="__('Daftar mata pelajaran yang tersedia di semua jenjang.')" separator>
        <x-slot:actions>
             <x-ui.button :label="__('Tambah Mapel')" icon="o-plus" class="btn-primary" wire:click="createNew" />
        </x-slot:actions>
    </x-ui.header>

    <div class="flex flex-col md:flex-row gap-4 items-center justify-between">
        <div class="flex flex-col md:flex-row flex-1 gap-4 w-full">
            <x-ui.input wire:model.live.debounce.300ms="search" :placeholder="__('Cari kode atau nama mapel...')" icon="o-magnifying-glass" class="w-full md:w-80" />
            
            <x-ui.select 
                wire:model.live="filterPhase" 
                :placeholder="__('Semua Fase')" 
                class="w-full md:w-64" 
                sm
                :options="collect($phases)->map(fn($p) => ['id' => $p, 'name' => __('Fase :phase', ['phase' => $p])])->toArray()" 
            />
        </div>
    </div>

    @if (session('success'))
        <x-ui.alert :title="__('Berhasil')" icon="o-check-circle" class="bg-emerald-50 text-emerald-800 border-emerald-100" dismissible>
            {{ session('success') }}
        </x-ui.alert>
    @endif

    <x-ui.card shadow padding="false">
        <x-ui.table 
            :headers="[
                ['key' => 'code', 'label' => __('Kode')],
                ['key' => 'name', 'label' => __('Nama')],
                ['key' => 'phase', 'label' => __('Jenis/Fase')],
                ['key' => 'tp_count', 'label' => __('CP / TP'), 'class' => 'text-center'],
                ['key' => 'actions', 'label' => '', 'class' => 'text-right']
            ]" 
            :rows="$subjects"
        >
            @scope('cell_code', $subject)
                <span class="font-mono text-xs opacity-60 text-slate-500 dark:text-slate-400">{{ $subject->code }}</span>
            @endscope

            @scope('cell_name', $subject)
                <span class="font-bold text-slate-900 dark:text-white">{{ $subject->name }}</span>
            @endscope

            @scope('cell_phase', $subject)
                @if($subject->phase)
                    <x-ui.badge :label="__('Fase :phase', ['phase' => $subject->phase])" class="bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 text-[10px]" />
                @else
                    <span class="opacity-30 text-xs">{{ __('Umum') }}</span>
                @endif
            @endscope

            @scope('cell_tp_count', $subject)
                @if($subject->phase)
                    @php
                        $tpCount = $subject->tpsForPhase($subject->phase)->count();
                    @endphp
                    <div class="text-xs font-bold text-indigo-600 dark:text-indigo-400">
                        {{ $tpCount }} TP
                    </div>
                @else
                    <span class="opacity-20 text-xs">-</span>
                @endif
            @endscope

            @scope('cell_actions', $subject)
                <div class="flex justify-end gap-1">
                    <x-ui.button icon="o-pencil-square" wire:click="edit({{ $subject->id }})" ghost sm />
                    @if($subject->phase)
                        <x-ui.button icon="o-list-bullet" wire:click="manageTps({{ $subject->id }})" ghost sm />
                    @endif
                    <x-ui.button 
                        icon="o-trash" 
                        class="text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20" 
                        wire:confirm="{{ __('Yakin ingin menghapus mapel ini?') }}" 
                        wire:click="delete({{ $subject->id }})" 
                        ghost sm 
                    />
                </div>
            @endscope
        </x-ui.table>
    </x-ui.card>

    <div class="mt-4">
        {{ $subjects->links() }}
    </div>

    {{-- Subject Create/Edit Modal --}}
    <x-ui.modal wire:model="subjectModal">
        <x-ui.header :title="$editing ? __('Edit Mata Pelajaran') : __('Tambah Mata Pelajaran')" :subtitle="__('Lengkapi detail mata pelajaran di bawah ini.')" separator />
        
        <form wire:submit="save" class="space-y-6">
            <x-ui.input wire:model="code" :label="__('Kode Mapel (e.g. MAT-A, INDO-P1)')" required />
            <x-ui.input wire:model="name" :label="__('Nama Mata Pelajaran')" required />

            <x-ui.select 
                wire:model="phase" 
                :label="__('Fase (Kurikulum Merdeka)')" 
                required 
                :options="collect($phases)->map(fn($p) => ['id' => $p, 'name' => __('Fase :phase', ['phase' => $p])])->toArray()" 
                :placeholder="__('Pilih Fase')" 
            />

            <div class="flex justify-end gap-2 pt-4">
                <x-ui.button :label="__('Batal')" ghost @click="show = false" />
                <x-ui.button :label="__('Simpan')" type="submit" class="btn-primary" spinner="save" />
            </div>
        </form>
    </x-ui.modal>

    {{-- TP Management Modal --}}
    <x-ui.modal wire:model="tpModal">
        @if($managingSubject)
            <x-ui.header :title="__('Kelola CP & Tujuan Pembelajaran (TP)')" separator>
                <x-slot:subtitle>
                    {{ __('Mapel: :name', ['name' => $managingSubject->name]) }} — {{ __('Fase :phase', ['phase' => $managingSubject->phase]) }}
                </x-slot:subtitle>
            </x-ui.header>

            <div class="space-y-8">
                @if($selectedCpId)
                    {{-- CP Description --}}
                    <div class="p-5 rounded-2xl border border-blue-100 bg-blue-50/50 dark:bg-blue-900/10 dark:border-blue-900/50">
                        <div class="space-y-3">
                            <label class="block text-[10px] font-black uppercase tracking-widest text-blue-600 dark:text-blue-400">{{ __('Capaian Pembelajaran (CP)') }}</label>
                            <div class="flex items-start gap-4">
                                <x-ui.textarea wire:model="selectedCpDescription" rows="3" class="flex-1 bg-white dark:bg-slate-900 !border-blue-200 dark:!border-blue-800 focus:!ring-blue-500/20" />
                                <x-ui.button icon="o-check" wire:click="updateCpDescription" class="bg-blue-600 text-white shadow-lg shadow-blue-600/20 hover:brightness-110 mt-1" />
                            </div>
                        </div>
                    </div>

                    {{-- Add TP Form --}}
                    <div class="bg-slate-50 dark:bg-slate-800/50 p-6 rounded-2xl border border-slate-100 dark:border-slate-800">
                        <form wire:submit="saveTp" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div class="md:col-span-1">
                                    <x-ui.input wire:model="tpCode" :label="__('Kode TP')" :placeholder="__('e.g. TP.1')" />
                                </div>
                                <div class="md:col-span-3">
                                     <x-ui.input wire:model="tpDescription" :label="__('Deskripsi TP')" :placeholder="__('Peserta didik mampu...')" required />
                                </div>
                            </div>
                            <div class="flex justify-end gap-2">
                                @if($editingTpId)
                                    <x-ui.button :label="__('Batal')" ghost wire:click="cancelEditTp" />
                                @endif
                                <x-ui.button :label="$editingTpId ? __('Update TP') : __('Tambah TP')" type="submit" class="btn-primary" icon="o-plus" spinner="saveTp" />
                            </div>
                        </form>
                    </div>

                    {{-- TP List --}}
                    <x-ui.card padding="false" shadow="false" class="border border-slate-100 dark:border-slate-800 !bg-transparent">
                        <x-ui.table 
                            :headers="[
                                ['key' => 'code', 'label' => __('Kode')],
                                ['key' => 'description', 'label' => __('Deskripsi')],
                                ['key' => 'actions', 'label' => '', 'class' => 'text-right']
                            ]" 
                            :rows="$subjectTps"
                            sm
                        >
                            @scope('cell_code', $tp)
                                <span class="font-mono text-xs font-bold text-slate-500">{{ $tp->code }}</span>
                            @endscope

                            @scope('cell_description', $tp)
                                <span class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">{{ $tp->description }}</span>
                            @endscope

                            @scope('cell_actions', $tp)
                                <div class="flex justify-end gap-1">
                                    <x-ui.button icon="o-pencil-square" wire:click="editTp({{ $tp->id }})" ghost sm />
                                    <x-ui.button 
                                        icon="o-trash" 
                                        class="text-red-500 dark:text-red-400 hover:bg-red-50" 
                                        wire:confirm="{{ __('Hapus TP ini?') }}" 
                                        wire:click="deleteTp({{ $tp->id }})" 
                                        ghost sm 
                                    />
                                </div>
                            @endscope
                        </x-ui.table>

                        @if(collect($subjectTps)->isEmpty())
                            <div class="py-12 text-center text-slate-400 italic text-sm">
                                {{ __('Belum ada TP untuk Fase :phase.', ['phase' => $managingSubject?->phase]) }}
                            </div>
                        @endif
                    </x-ui.card>
                @endif
            </div>

            <div class="flex justify-end gap-2 pt-6">
                <x-ui.button :label="__('Tutup')" ghost @click="show = false" />
            </div>
        @endif
    </x-ui.modal>
</div>
