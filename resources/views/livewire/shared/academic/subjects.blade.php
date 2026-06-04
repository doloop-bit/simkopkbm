<?php

declare(strict_types=1);

use App\Models\Subject;
use App\Models\SubjectTp;
use App\Models\LearningAchievement;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public string $name = '';
    public string $code = '';
    public ?string $phase = null;
    public string $description = '';

    // Filters
    public string $search = '';
    public ?string $filterPhase = null;

    public ?Subject $editing = null;
    public bool $subjectModal = false;
    public bool $tpModal = false;

    // TP Management
    public ?Subject $managingSubject = null;
    public $subjectTps = [];
    public $tpCode = '';
    public $tpDescription = '';
    public ?int $editingTpId = null;

    // CP Management
    public $subjectCps = [];
    public string $newCpDescription = '';
    public ?int $selectedCpId = null;
    public ?string $selectedCpDescription = null;

    // Constants
    public array $phases = ['Fondasi', 'A', 'B', 'C', 'D', 'E', 'F'];

    public function createNew(): void
    {
        if (!auth()->user()->isAdmin()) return;
        
        $this->reset(['name', 'code', 'phase', 'description', 'editing']);
        $this->resetValidation();
        $this->subjectModal = true;
    }

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
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function save(): void
    {
        if (!auth()->user()->isAdmin()) return;
        
        $validated = $this->validate();

        if ($this->editing) {
            $this->editing->update($validated);
            session()->flash('success', 'Mata pelajaran berhasil diperbarui.');
        } else {
            Subject::create($validated);
            session()->flash('success', 'Mata pelajaran berhasil ditambahkan.');
        }

        $this->reset(['name', 'code', 'phase', 'description', 'editing']);
        $this->subjectModal = false;
    }

    public function edit(Subject $subject): void
    {
        if (!auth()->user()->isAdmin()) return;
        
        $this->editing = $subject;
        $this->name = $subject->name;
        $this->code = $subject->code;
        $this->phase = $subject->phase;
        $this->description = $subject->description ?? '';

        $this->subjectModal = true;
    }

    public function delete(Subject $subject): void
    {
        if (!auth()->user()->isAdmin()) return;
        
        $subject->delete();
        session()->flash('success', 'Mata pelajaran berhasil dihapus.');
    }

    public function manageTps(Subject $subject): void
    {
        $this->managingSubject = $subject;
        $this->selectedCpId = null;
        $this->selectedCpDescription = '';
        $this->newCpDescription = '';
        $this->subjectTps = [];
        $this->subjectCps = [];
        $this->cancelEditTp();

        $this->loadCps();

        $this->tpModal = true;
    }

    public function loadCps(): void
    {
        if (!$this->managingSubject || !$this->managingSubject->phase) {
            $this->subjectCps = [];
            $this->selectedCpId = null;
            $this->selectedCpDescription = '';
            return;
        }

        $this->subjectCps = LearningAchievement::where('subject_id', $this->managingSubject->id)
            ->where('phase', $this->managingSubject->phase)
            ->get();

        if ($this->subjectCps->isNotEmpty()) {
            if (!$this->selectedCpId || !$this->subjectCps->contains('id', $this->selectedCpId)) {
                $this->selectedCpId = $this->subjectCps->first()->id;
            }
            $selectedCp = $this->subjectCps->firstWhere('id', $this->selectedCpId);
            $this->selectedCpDescription = $selectedCp->description;
            $this->loadTps();
        } else {
            $this->selectedCpId = null;
            $this->selectedCpDescription = '';
            $this->subjectTps = [];
        }
    }

    public function selectCp(int $cpId): void
    {
        $this->selectedCpId = $cpId;
        $cp = LearningAchievement::find($cpId);
        if ($cp) {
            $this->selectedCpDescription = $cp->description;
        }
        $this->cancelEditTp();
        $this->loadTps();
    }

    public function addCp(): void
    {
        if (!auth()->user()->isAdmin()) return;

        $this->validate([
            'newCpDescription' => ['required', 'string', 'max:2000'],
        ]);

        $cp = LearningAchievement::create([
            'subject_id' => $this->managingSubject->id,
            'phase' => $this->managingSubject->phase,
            'description' => $this->newCpDescription,
        ]);

        $this->newCpDescription = '';
        $this->selectedCpId = $cp->id;
        $this->loadCps();
        session()->flash('success_modal', 'Capaian Pembelajaran (CP) berhasil ditambahkan.');
    }

    public function updateCpDescription(): void
    {
        if (!auth()->user()->isAdmin()) return;
        if (!$this->selectedCpId || !$this->selectedCpDescription) return;

        LearningAchievement::find($this->selectedCpId)?->update([
            'description' => $this->selectedCpDescription,
        ]);

        $this->loadCps();
        session()->flash('success_modal', 'Deskripsi CP berhasil diperbarui.');
    }

    public function deleteCp(int $cpId): void
    {
        if (!auth()->user()->isAdmin()) return;

        $cp = LearningAchievement::find($cpId);
        if ($cp) {
            $cp->delete();
            if ($this->selectedCpId === $cpId) {
                $this->selectedCpId = null;
            }
            $this->loadCps();
            session()->flash('success_modal', 'Capaian Pembelajaran (CP) berhasil dihapus.');
        }
    }

    public function loadTps(): void
    {
        if ($this->selectedCpId) {
            $this->subjectTps = SubjectTp::where('learning_achievement_id', $this->selectedCpId)->orderBy('code')->get();
        } else {
            $this->subjectTps = [];
        }
    }

    public function saveTp(): void
    {
        if (!auth()->user()->isAdmin()) return;

        $this->validate([
            'tpCode' => ['nullable', 'string', 'max:50'],
            'tpDescription' => ['required', 'string'],
        ]);

        if (!$this->selectedCpId) {
            session()->flash('error_modal', 'CP tidak ditemukan.');
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
        session()->flash('success_modal', 'TP berhasil disimpan.');
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
        if (!auth()->user()->isAdmin()) return;

        SubjectTp::find($id)?->delete();
        $this->loadTps();
        session()->flash('success_modal', 'TP berhasil dihapus.');
    }

    public function cancelEditTp(): void
    {
        $this->editingTpId = null;
        $this->tpCode = '';
        $this->tpDescription = '';
    }

    public function with(): array
    {
        $user = auth()->user();
        $isAdmin = $user->isAdmin();
        
        $query = Subject::query();

        if (!$isAdmin) {
            // Teacher filtering based on assigned classroom phases
            $classroomIds = $user->getAssignedClassroomIds();
            $classrooms = \App\Models\Classroom::with('level')->whereIn('id', $classroomIds)->get();
            
            $phases = [];
            foreach ($classrooms as $c) {
                $phaseMap = $c->level->phase_map ?? [];
                if (isset($phaseMap[$c->class_level])) {
                    $phases[] = $phaseMap[$c->class_level];
                }
            }
            $phases = array_unique($phases);
            $query->whereIn('phase', $phases);
        }

        return [
            'subjects' => $query
                ->when($this->search, function ($query) {
                    $query->where(function($q) {
                        $q->where('name', 'like', '%' . $this->search . '%')
                          ->orWhere('code', 'like', '%' . $this->search . '%');
                    });
                })
                ->when($this->filterPhase, fn($q) => $q->where('phase', $this->filterPhase))
                ->orderBy('name')
                ->paginate(15),
            'phases' => Subject::select('phase')->whereNotNull('phase')->distinct()->pluck('phase'),
            'isAdmin' => $isAdmin,
        ];
    }
}; ?>

<div class="p-4 md:p-6 space-y-6 text-slate-900 dark:text-white pb-24 md:pb-6">
    <x-ui.header :title="__('Mata Pelajaran')" :subtitle="$isAdmin ? __('Kelola daftar mata pelajaran kurikulum.') : __('Mata pelajaran pada jenjang kelas Anda')" separator>
        @if($isAdmin)
            <x-slot:actions>
                 <x-ui.button :label="__('Tambah Mapel')" icon="o-plus" class="btn-primary" wire:click="createNew" wire:loading.attr="disabled" />
            </x-slot:actions>
        @endif
    </x-ui.header>

    <div class="flex flex-col md:flex-row gap-4 items-center justify-between">
        <div class="flex flex-col md:flex-row flex-1 gap-4 w-full">
            <x-ui.input wire:model.live.debounce.300ms="search" :placeholder="__('Cari nama atau kode mapel...')" icon="o-magnifying-glass" class="w-full md:w-80" />
            
            <x-ui.select wire:model.live="filterPhase" :placeholder="__('Semua Fase')" class="w-full md:w-48" :options="$phases->map(fn($p) => ['id' => $p, 'name' => 'Fase ' . $p])" />
        </div>
    </div>

    @if (session('success'))
        <x-ui.alert :title="__('Berhasil')" icon="o-check-circle" class="bg-emerald-50 text-emerald-800 border-emerald-100" dismissible>
            {{ session('success') }}
        </x-ui.alert>
    @endif

    @if($isAdmin)
        {{-- Admin Table View --}}
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
                        <x-ui.button icon="o-pencil-square" wire:click="edit({{ $subject->id }})" ghost />
                        @if($subject->phase)
                            <x-ui.button icon="o-list-bullet" wire:click="manageTps({{ $subject->id }})" ghost />
                        @endif
                        <x-ui.button 
                            icon="o-trash" 
                            class="text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20" 
                            wire:confirm="{{ __('Yakin ingin menghapus mapel ini?') }}" 
                            wire:click="delete({{ $subject->id }})" 
                            ghost 
                        />
                    </div>
                @endscope
            </x-ui.table>
        </x-ui.card>
    @else
        {{-- Teacher Card View --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($subjects as $subject)
                <x-ui.card shadow padding="sm" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                    <div class="flex items-center gap-4 p-2">
                        <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400 shrink-0">
                            <x-ui.icon name="o-book-open" class="w-6 h-6" />
                        </div>
                        <div class="flex flex-col min-w-0">
                            <span class="font-bold text-lg text-slate-900 dark:text-white truncate">{{ $subject->name }}</span>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="text-xs font-mono text-slate-500">{{ $subject->code ?? '-' }}</span>
                                @if($subject->phase)
                                    <x-ui.badge :label="'Fase ' . $subject->phase" class="bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 text-[9px] font-black" />
                                @endif
                            </div>
                        </div>
                    </div>
                </x-ui.card>
            @empty
                <div class="col-span-full py-12 text-center text-slate-400 italic text-sm">
                    {{ __('Belum ada data mata pelajaran.') }}
                </div>
            @endforelse
        </div>
    @endif

    <div class="mt-4">
        {{ $subjects->links() }}
    </div>

    @if($isAdmin)
        {{-- Subject Create/Edit Modal --}}
        <x-ui.modal wire:model="subjectModal" persistent>
            <x-ui.header :title="$editing ? __('Edit Mata Pelajaran') : __('Tambah Mata Pelajaran')" :subtitle="__('Lengkapi detail mata pelajaran di bawah ini.')" separator />

            <form wire:submit="save" class="space-y-6">
                <x-ui.input wire:model="name" :label="__('Nama Mata Pelajaran')" required :placeholder="__('e.g. Matematika, Bahasa Indonesia')" />
                
                <div class="grid grid-cols-2 gap-4">
                    <x-ui.input wire:model="code" :label="__('Kode Mapel')" :placeholder="__('e.g. MAT-01')" required />
                    <x-ui.select 
                        wire:model="phase" 
                        :label="__('Fase (Kurikulum Merdeka)')" 
                        required
                        :options="[
                            ['id' => 'Fondasi', 'name' => 'Fase Fondasi (PAUD)'],
                            ['id' => 'A', 'name' => 'Fase A (Kelas 1-2)'],
                            ['id' => 'B', 'name' => 'Fase B (Kelas 3-4)'],
                            ['id' => 'C', 'name' => 'Fase C (Kelas 5-6)'],
                            ['id' => 'D', 'name' => 'Fase D (SMP)'],
                            ['id' => 'E', 'name' => 'Fase E (Kelas 10)'],
                            ['id' => 'F', 'name' => 'Fase F (Kelas 11-12)'],
                        ]"
                        :placeholder="__('Pilih Fase')" 
                    />
                </div>

                <x-ui.textarea wire:model="description" :label="__('Keterangan (Opsional)')" rows="3" :placeholder="__('Deskripsi singkat mata pelajaran...')" />

                <div class="flex justify-end gap-2 pt-4">
                    <x-ui.button :label="__('Batal')" ghost @click="show = false" />
                    <x-ui.button :label="__('Simpan')" type="submit" class="btn-primary" spinner="save" />
                </div>
            </form>
        </x-ui.modal>

        {{-- TP Management Modal --}}
        <x-ui.modal wire:model="tpModal" persistent class="backdrop-blur" maxWidth="max-w-6xl">
            @if($managingSubject)
                <x-ui.header :title="__('Kelola CP & Tujuan Pembelajaran (TP)')" separator>
                    <x-slot:subtitle>
                        {{ __('Mapel: :name', ['name' => $managingSubject->name]) }} — {{ __('Fase :phase', ['phase' => $managingSubject->phase]) }}
                    </x-slot:subtitle>
                </x-ui.header>

                @if(session('success_modal'))
                    <x-ui.alert :title="__('Berhasil')" icon="o-check-circle" class="bg-emerald-50 text-emerald-800 border-emerald-100 mb-4" dismissible>
                        {{ session('success_modal') }}
                    </x-ui.alert>
                @endif

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 max-h-[65vh] overflow-y-auto pr-2 custom-scrollbar">
                    {{-- Left side: CP Management (col-span-5) --}}
                    <div class="lg:col-span-5 space-y-6">
                        <div class="p-4 bg-slate-50 dark:bg-slate-800/40 rounded-xl border border-slate-200 dark:border-slate-800 space-y-4">
                            <h3 class="text-xs font-black uppercase tracking-wider text-slate-500 flex items-center gap-1.5">
                                <x-ui.icon name="o-presentation-chart-line" class="w-4 h-4 text-indigo-500" />
                                {{ __('1. Daftar Capaian Pembelajaran (CP)') }}
                            </h3>

                            <div class="space-y-2">
                                @forelse($subjectCps as $cp)
                                    <div 
                                        wire:key="cp-{{ $cp->id }}"
                                        wire:click="selectCp({{ $cp->id }})"
                                        class="p-3 rounded-xl border transition-all cursor-pointer flex items-start justify-between gap-3 text-left
                                            {{ $selectedCpId === $cp->id 
                                                ? 'bg-blue-50/70 border-blue-200 dark:bg-blue-950/20 dark:border-blue-900/60 ring-1 ring-blue-500/10' 
                                                : 'bg-white border-slate-200 hover:border-slate-300 dark:bg-slate-900 dark:border-slate-800 dark:hover:border-slate-700' 
                                            }}"
                                    >
                                        <div class="flex-1 min-w-0">
                                            <p class="text-xs text-slate-500 font-bold uppercase tracking-wider">{{ __('CP ID: ') }}{{ $cp->id }}</p>
                                            <p class="text-sm mt-1 text-slate-700 dark:text-slate-300 leading-relaxed line-clamp-3">{{ $cp->description }}</p>
                                        </div>
                                        <x-ui.button 
                                            icon="o-trash" 
                                            class="text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/30 btn-xs shrink-0" 
                                            wire:confirm="{{ __('Hapus CP ini beserta semua TP di dalamnya?') }}" 
                                            wire:click.stop="deleteCp({{ $cp->id }})" 
                                            ghost 
                                        />
                                    </div>
                                @empty
                                    <div class="text-center py-6 text-slate-400 italic text-xs border border-dashed border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900">
                                        {{ __('Belum ada CP. Silakan tambah CP baru di bawah.') }}
                                    </div>
                                @endforelse
                            </div>

                            {{-- Add New CP --}}
                            <div class="pt-4 border-t border-slate-200 dark:border-slate-800 space-y-2">
                                <label class="block text-[10px] font-black uppercase tracking-wider text-slate-400">{{ __('Tambah CP Baru') }}</label>
                                <div class="flex gap-2 items-start">
                                    <div class="flex-1">
                                        <x-ui.textarea wire:model="newCpDescription" rows="2" class="text-sm bg-white dark:bg-slate-900" :placeholder="__('Tulis deskripsi Capaian Pembelajaran baru...')" />
                                    </div>
                                    <x-ui.button icon="o-plus" wire:click="addCp" class="btn-primary mt-2" spinner="addCp" />
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Right side: TP Management for selected CP (col-span-7) --}}
                    <div class="lg:col-span-7 space-y-6">
                        @if($selectedCpId)
                            {{-- Edit Selected CP Description --}}
                            <div class="p-4 rounded-xl border border-blue-100 bg-blue-50/30 dark:bg-blue-950/10 dark:border-blue-900/40 space-y-3">
                                <div class="flex items-center justify-between">
                                    <label class="block text-[10px] font-black uppercase tracking-widest text-blue-600 dark:text-blue-400">
                                        {{ __('Detail CP Terpilih (ID: :id)', ['id' => $selectedCpId]) }}
                                    </label>
                                    <x-ui.button :label="__('Update Deskripsi CP')" wire:click="updateCpDescription" class="btn-xs btn-primary bg-blue-600 text-white border-none shrink-0" spinner="updateCpDescription" />
                                </div>
                                <x-ui.textarea wire:model="selectedCpDescription" rows="2" class="bg-white dark:bg-slate-900 text-sm focus:ring-blue-500/20" />
                            </div>

                            {{-- Add/Edit TP Form --}}
                            <div class="bg-slate-50 dark:bg-slate-800/40 p-4 rounded-xl border border-slate-200 dark:border-slate-800 space-y-3">
                                <h4 class="text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-wider flex items-center gap-1.5">
                                    <x-ui.icon name="o-list-bullet" class="w-4 h-4 text-emerald-500" />
                                    {{ $editingTpId ? __('2. Edit Tujuan Pembelajaran (TP)') : __('2. Tambah Tujuan Pembelajaran (TP) Baru') }}
                                </h4>
                                <form wire:submit="saveTp" class="space-y-3">
                                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                                        <div class="md:col-span-1">
                                            <x-ui.input wire:model="tpCode" :placeholder="__('Kode TP (e.g. TP 1)')" class="text-sm bg-white dark:bg-slate-900" />
                                        </div>
                                        <div class="md:col-span-3">
                                             <x-ui.input wire:model="tpDescription" :placeholder="__('Deskripsi TP...')" required class="text-sm bg-white dark:bg-slate-900" />
                                        </div>
                                    </div>
                                    <div class="flex justify-end gap-2">
                                        @if($editingTpId)
                                            <x-ui.button :label="__('Batal')" ghost wire:click="cancelEditTp" class="btn-sm" />
                                        @endif
                                        <x-ui.button :label="$editingTpId ? __('Update TP') : __('Tambah TP')" type="submit" class="btn-primary btn-sm" icon="o-plus" spinner="saveTp" />
                                    </div>
                                </form>
                            </div>

                            {{-- TP List for Selected CP --}}
                            <x-ui.card padding="false" shadow="false" class="border border-slate-200 dark:border-slate-800 !bg-transparent">
                                <x-ui.table 
                                    :headers="[
                                        ['key' => 'code', 'label' => __('Kode'), 'class' => 'w-24'],
                                        ['key' => 'description', 'label' => __('Deskripsi')],
                                        ['key' => 'actions', 'label' => '', 'class' => 'text-right w-24']
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
                                            <x-ui.button icon="o-pencil-square" wire:click="editTp({{ $tp->id }})" ghost class="btn-xs" />
                                            <x-ui.button 
                                                icon="o-trash" 
                                                class="text-red-500 dark:text-red-400 hover:bg-red-50 btn-xs" 
                                                wire:confirm="{{ __('Hapus TP ini?') }}" 
                                                wire:click="deleteTp({{ $tp->id }})" 
                                                ghost 
                                            />
                                        </div>
                                    @endscope
                                </x-ui.table>

                                @if(collect($subjectTps)->isEmpty())
                                    <div class="py-10 text-center text-slate-400 italic text-xs">
                                        {{ __('Belum ada TP untuk CP terpilih.') }}
                                    </div>
                                @endif
                            </x-ui.card>
                        @else
                            <div class="flex flex-col items-center justify-center py-20 border border-dashed border-slate-200 dark:border-slate-800 rounded-2xl bg-slate-50/50 dark:bg-slate-900/50">
                                <x-ui.icon name="o-presentation-chart-line" class="size-12 text-slate-300 dark:text-slate-700 mb-3" />
                                <p class="text-xs text-slate-400 font-bold uppercase tracking-wider">{{ __('Pilih atau Tambah CP terlebih dahulu') }}</p>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-6 border-t border-slate-100 dark:border-slate-800">
                    <x-ui.button :label="__('Tutup')" ghost @click="show = false" />
                </div>
            @endif
        </x-ui.modal>
    @endif
</div>
