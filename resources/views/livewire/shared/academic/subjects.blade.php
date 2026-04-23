<?php

declare(strict_types=1);

use App\Models\Subject;
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
            'code' => ['nullable', 'string', 'max:50'],
            'phase' => ['nullable', 'string', 'max:10'],
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
        $this->code = $subject->code ?? '';
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

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($subjects as $subject)
            <x-ui.card shadow padding="sm" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors group">
                <div class="flex items-center gap-4 p-2">
                    <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400 shrink-0">
                        <x-ui.icon name="o-book-open" class="w-6 h-6" />
                    </div>
                    <div class="flex flex-col min-w-0 flex-1">
                        <div class="flex justify-between items-start">
                            <span class="font-bold text-lg text-slate-900 dark:text-white truncate">{{ $subject->name }}</span>
                            @if($isAdmin)
                                <div class="opacity-0 group-hover:opacity-100 transition-opacity flex gap-0.5">
                                    <x-ui.button icon="o-pencil-square" wire:click="edit({{ $subject->id }})" ghost class="btn-xs" />
                                    <x-ui.button icon="o-trash" wire:click="delete({{ $subject->id }})" wire:confirm="{{ __('Hapus mapel ini?') }}" ghost class="btn-xs text-rose-500" />
                                </div>
                            @endif
                        </div>
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
                    <x-ui.input wire:model="code" :label="__('Kode Mapel')" :placeholder="__('e.g. MAT-01')" />
                    <x-ui.select 
                        wire:model="phase" 
                        :label="__('Fase (Kurikulum Merdeka)')" 
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
    @endif
</div>
