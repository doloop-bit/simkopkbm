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

<div class="p-6">
    <x-header title="Mata Pelajaran" subtitle="Daftar mata pelajaran yang tersedia di semua jenjang." separator>
        <x-slot:actions>
             <x-button label="Tambah Mapel" icon="o-plus" class="btn-primary" wire:click="createNew" />
        </x-slot:actions>
    </x-header>

    <div class="flex flex-col md:flex-row gap-4 mb-6 items-center justify-between">
        <div class="flex flex-col md:flex-row flex-1 gap-4 w-full">
            <x-input wire:model.live.debounce.300ms="search" placeholder="Cari kode atau nama mapel..." icon="o-magnifying-glass" class="w-full md:w-80" />
            
            <x-select wire:model.live="filterPhase" placeholder="Semua Fase" class="w-full md:w-64" :options="collect($phases)->map(fn($p) => ['id' => $p, 'name' => 'Fase ' . $p])->toArray()" />
        </div>
    </div>

    @if (session('success'))
        <x-alert title="Berhasil" icon="o-check-circle" class="alert-success mb-6" dismissible>
            {{ session('success') }}
        </x-alert>
    @endif

    <div class="overflow-hidden border rounded-xl border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm">
        <table class="table">
            <thead>
                <tr>
                    <th class="bg-base-200">Kode</th>
                    <th class="bg-base-200">Nama</th>
                    <th class="bg-base-200">Jenis/Fase</th>
                    <th class="bg-base-200 text-center">CP / TP</th>
                    <th class="bg-base-200 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($subjects as $subject)
                    <tr wire:key="{{ $subject->id }}" class="hover">
                        <td class="font-mono opacity-60">
                            {{ $subject->code }}
                        </td>
                        <td class="font-bold">
                            {{ $subject->name }}
                        </td>
                        <td>
                            @if($subject->phase)
                                <x-badge :label="'Fase ' . $subject->phase" class="badge-neutral badge-sm" />
                            @else
                                <span class="opacity-30 text-xs">Umum</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($subject->phase)
                                @php
                                    $tpCount = $subject->tpsForPhase($subject->phase)->count();
                                @endphp
                                <div class="text-xs opacity-60">
                                    {{ $tpCount }} TP
                                </div>
                            @else
                                <span class="opacity-20 text-xs">-</span>
                            @endif
                        </td>
                        <td class="text-right">
                            <div class="flex justify-end gap-1">
                                <x-button icon="o-pencil-square" wire:click="edit({{ $subject->id }})" ghost sm />
                                @if($subject->phase)
                                    <x-button icon="o-list-bullet" wire:click="manageTps({{ $subject->id }})" ghost sm tooltip="Kelola CP & TP" />
                                @endif
                                <x-button icon="o-trash" class="text-error" wire:confirm="Yakin ingin menghapus mapel ini?" wire:click="delete({{ $subject->id }})" ghost sm />
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $subjects->links() }}
    </div>

    {{-- Subject Create/Edit Modal --}}
    <x-modal wire:model="subjectModal" class="backdrop-blur">
        <x-header :title="$editing ? 'Edit Mata Pelajaran' : 'Tambah Mata Pelajaran'" subtitle="Lengkapi detail mata pelajaran di bawah ini." separator />
        
        <form wire:submit="save">
            <div class="grid grid-cols-1 gap-4">
                <x-input wire:model="code" label="Kode Mapel (e.g. MAT-A, INDO-P1)" required />
                <x-input wire:model="name" label="Nama Mata Pelajaran" required />

                <x-select wire:model="phase" label="Fase (Kurikulum Merdeka)" required :options="collect($phases)->map(fn($p) => ['id' => $p, 'name' => 'Fase ' . $p])->toArray()" placeholder="Pilih Fase" />
            </div>

            <x-slot:actions>
                <x-button label="Batal" @click="$set('subjectModal', false)" />
                <x-button label="Simpan" type="submit" class="btn-primary" spinner="save" />
            </x-slot:actions>
        </form>
    </x-modal>

    {{-- TP Management Modal --}}
    <x-modal wire:model="tpModal" class="backdrop-blur max-w-4xl">
        @if($managingSubject)
            <x-header title="Kelola CP & Tujuan Pembelajaran (TP)" separator>
                <x-slot:subtitle>
                    Mapel: <strong>{{ $managingSubject->name }}</strong> — Fase {{ $managingSubject->phase }}
                </x-slot:subtitle>
            </x-header>

            <div class="space-y-6 max-h-[60vh] overflow-y-auto pr-2 custom-scrollbar">
                @if($selectedCpId)
                    {{-- CP Description --}}
                    <div class="p-4 rounded-xl border border-blue-200 bg-blue-50 dark:bg-blue-900/10 dark:border-blue-800">
                        <div class="flex items-start gap-4">
                            <div class="flex-1">
                                <label class="block text-[10px] font-bold uppercase tracking-wider text-blue-600 mb-1">Capaian Pembelajaran (CP)</label>
                                <x-textarea wire:model="selectedCpDescription" rows="2" class="text-sm border-none shadow-none bg-transparent focus:ring-0" />
                            </div>
                            <x-button icon="o-check" wire:click="updateCpDescription" class="btn-primary btn-sm mt-5" />
                        </div>
                    </div>

                    {{-- Add TP Form --}}
                    <div class="bg-base-200 p-4 rounded-xl">
                        <form wire:submit="saveTp">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <x-input wire:model="tpCode" label="Kode TP (Opsional)" placeholder="e.g. TP.1" />
                                <div class="md:col-span-2">
                                     <x-input wire:model="tpDescription" label="Deskripsi TP" placeholder="Peserta didik mampu..." required />
                                </div>
                            </div>
                            <div class="flex justify-end gap-2 mt-4">
                                @if($editingTpId)
                                    <x-button label="Batal" wire:click="cancelEditTp" />
                                @endif
                                <x-button label="{{ $editingTpId ? 'Update TP' : 'Tambah TP' }}" type="submit" class="btn-primary" icon="o-plus" spinner="saveTp" />
                            </div>
                        </form>
                    </div>

                    {{-- TP List --}}
                    <div class="border rounded-xl border-base-200 overflow-hidden bg-white dark:bg-base-300">
                        <table class="table table-sm">
                            <thead>
                                <tr class="bg-base-200">
                                    <th class="w-24">Kode</th>
                                    <th>Deskripsi</th>
                                    <th class="text-right w-24">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($subjectTps as $tp)
                                    <tr class="hover">
                                        <td class="font-mono text-xs">{{ $tp->code }}</td>
                                        <td class="text-sm">{{ $tp->description }}</td>
                                        <td class="text-right">
                                            <div class="flex justify-end gap-1">
                                                <x-button icon="o-pencil-square" wire:click="editTp({{ $tp->id }})" ghost sm />
                                                <x-button icon="o-trash" class="text-error" wire:confirm="Hapus TP ini?" wire:click="deleteTp({{ $tp->id }})" ghost sm />
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center py-8 opacity-40 italic font-serif">
                                            Belum ada TP untuk Fase {{ $managingSubject?->phase }}.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <x-slot:actions>
                <x-button label="Tutup" @click="$set('tpModal', false)" />
            </x-slot:actions>
        @endif
    </x-modal>
</div>
