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
    public function save(): void
    {
        $validated = $this->validate();

        if ($this->editing) {
            $this->editing->update($validated);
        } else {
            Subject::create($validated);
        }

        $this->reset(['name', 'code', 'phase', 'editing']);
        $this->dispatch('subject-saved');
        $this->modal('subject-modal')->close();
    }

    public function edit(Subject $subject): void
    {
        $this->editing = $subject;
        $this->name = $subject->name;
        $this->code = $subject->code;
        $this->phase = $subject->phase;

        $this->modal('subject-modal')->show();
    }

    public function delete(Subject $subject): void
    {
        $subject->delete();
    }

    public function manageTps(Subject $subject): void
    {
        $this->managingSubject = $subject;
        $this->selectedCpId = null;
        $this->selectedCpDescription = null;
        $this->subjectTps = [];
        $this->cancelEditTp();

        $this->loadTps();

        $this->modal('tp-modal')->show();
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

        \Flux::toast('Deskripsi CP berhasil diperbarui.');
    }

    public function saveTp(): void
    {
        $this->validate([
            'tpCode' => ['nullable', 'string', 'max:50'],
            'tpDescription' => ['required', 'string'],
        ]);

        if (!$this->selectedCpId) {
            \Flux::toast(variant: 'danger', text: 'CP tidak ditemukan.');
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
        \Flux::toast('TP berhasil disimpan.');
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
        \Flux::toast('TP berhasil dihapus.');
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
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl" level="1">Mata Pelajaran</flux:heading>
            <flux:subheading>Daftar mata pelajaran yang tersedia di semua jenjang.</flux:subheading>
        </div>

        <flux:modal.trigger name="subject-modal">
            <flux:button variant="primary" icon="plus" wire:click="$set('editing', null)">Tambah Mapel</flux:button>
        </flux:modal.trigger>
    </div>

    <div class="flex flex-col md:flex-row gap-4 mb-6 items-center justify-between">
        <div class="flex flex-col md:flex-row flex-1 gap-4 w-full">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Cari kode atau nama mapel..." icon="magnifying-glass" class="w-full md:w-80" />
            
            <flux:select wire:model.live="filterPhase" placeholder="Semua Fase" class="w-full md:w-64">
                <option value="">Semua Fase</option>
                @foreach($phases as $p)
                    <option value="{{ $p }}">Fase {{ $p }}</option>
                @endforeach
            </flux:select>
        </div>
    </div>

    <div class="overflow-hidden border rounded-lg border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-sm text-left border-collapse">
            <thead class="bg-zinc-50 dark:bg-zinc-800">
                <tr>
                    <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b border-zinc-200 dark:border-zinc-700">Kode</th>
                    <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b border-zinc-200 dark:border-zinc-700">Nama</th>
                    <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b border-zinc-200 dark:border-zinc-700">Jenis/Fase</th>
                    <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b border-zinc-200 dark:border-zinc-700 text-center">CP / TP</th>
                    <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b border-zinc-200 dark:border-zinc-700 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @foreach ($subjects as $subject)
                    <tr wire:key="{{ $subject->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                        <td class="px-4 py-3 font-mono text-zinc-600 dark:text-zinc-400">
                            {{ $subject->code }}
                        </td>
                        <td class="px-4 py-3 font-medium text-zinc-900 dark:text-white">
                            {{ $subject->name }}
                        </td>
                        <td class="px-4 py-3">
                            @if($subject->phase)
                                <flux:badge size="sm" variant="neutral">Fase {{ $subject->phase }}</flux:badge>
                            @else
                                <span class="text-zinc-400 text-sm">Umum</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($subject->phase)
                                @php
                                    $tpCount = $subject->tpsForPhase($subject->phase)->count();
                                @endphp
                                <div class="flex items-center justify-center gap-2 text-xs">
                                    <span class="text-zinc-500">{{ $tpCount }} TP</span>
                                </div>
                            @else
                                <span class="text-zinc-400 text-xs">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right space-x-2">
                            <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="edit({{ $subject->id }})" x-on:click="$flux.modal('subject-modal').show()" />
                            @if($subject->phase)
                                <flux:button size="sm" variant="ghost" icon="list-bullet" wire:click="manageTps({{ $subject->id }})" x-on:click="$flux.modal('tp-modal').show()" tooltip="Kelola CP & TP" />
                            @endif
                            <flux:button size="sm" variant="ghost" icon="trash" class="text-red-500" wire:confirm="Yakin ingin menghapus mapel ini?" wire:click="delete({{ $subject->id }})" />
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
    <flux:modal name="subject-modal" class="max-w-md" x-on:subject-saved.window="$flux.modal('subject-modal').close()">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $editing ? 'Edit Mata Pelajaran' : 'Tambah Mata Pelajaran' }}</flux:heading>
                <flux:subheading>Lengkapi detail mata pelajaran di bawah ini.</flux:subheading>
            </div>

            <flux:input wire:model="code" label="Kode Mapel (e.g. MAT-A, INDO-P1)" required />
            <flux:input wire:model="name" label="Nama Mata Pelajaran" required />

            <flux:select wire:model="phase" label="Fase (Kurikulum Merdeka)" required>
                <option value="">Pilih Fase</option>
                @foreach($phases as $p)
                    <option value="{{ $p }}">Fase {{ $p }}</option>
                @endforeach
            </flux:select>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Batal</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Simpan</flux:button>
            </div>
        </form>

    </flux:modal>

    {{-- TP Management Modal --}}
    <flux:modal name="tp-modal" class="max-w-3xl">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Kelola CP & Tujuan Pembelajaran (TP)</flux:heading>
                <flux:subheading>
                    Mata Pelajaran: <strong>{{ $managingSubject?->name }}</strong>
                    @if($managingSubject?->phase)
                        — Fase {{ $managingSubject->phase }}
                    @endif
                </flux:subheading>
            </div>

            @if($selectedCpId)
                {{-- CP Description --}}
                <div class="p-4 rounded-lg border border-blue-200 bg-blue-50 dark:bg-blue-950/30 dark:border-blue-900">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1">
                            <label class="block text-xs font-semibold text-blue-600 dark:text-blue-400 mb-1">
                                Capaian Pembelajaran (CP)
                            </label>
                            <flux:textarea
                                wire:model="selectedCpDescription"
                                rows="2"
                                class="text-sm"
                                placeholder="Deskripsi Capaian Pembelajaran..."
                            />
                        </div>
                        <flux:button size="sm" variant="ghost" icon="check" wire:click="updateCpDescription" tooltip="Simpan CP" class="mt-5" />
                    </div>
                </div>

                {{-- Add TP Form --}}
                <div class="p-4 border rounded-lg bg-zinc-50 dark:bg-zinc-800">
                    <form wire:submit="saveTp" class="grid gap-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <flux:input wire:model="tpCode" label="Kode TP (Opsional)" placeholder="e.g. TP.1" />
                            <div class="md:col-span-2">
                                 <flux:input wire:model="tpDescription" label="Deskripsi TP" placeholder="Peserta didik mampu..." required />
                            </div>
                        </div>
                        <div class="flex justify-end gap-2">
                            @if($editingTpId)
                                <flux:button variant="ghost" size="sm" wire:click="cancelEditTp">Batal</flux:button>
                            @endif
                            <flux:button type="submit" variant="primary" size="sm" icon="plus">{{ $editingTpId ? 'Update TP' : 'Tambah TP' }}</flux:button>
                        </div>
                    </form>
                </div>

                {{-- TP List --}}
                <div class="overflow-hidden border rounded-lg border-zinc-200 dark:border-zinc-700">
                    <table class="w-full text-sm text-left border-collapse">
                        <thead class="bg-zinc-50 dark:bg-zinc-800">
                            <tr>
                                <th class="px-4 py-2 font-medium border-b w-24">Kode</th>
                                <th class="px-4 py-2 font-medium border-b">Deskripsi</th>
                                <th class="px-4 py-2 font-medium border-b text-right w-32">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @forelse($subjectTps as $tp)
                                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                    <td class="px-4 py-2 font-mono text-xs">{{ $tp->code }}</td>
                                    <td class="px-4 py-2">{{ $tp->description }}</td>
                                    <td class="px-4 py-2 text-right space-x-1">
                                        <flux:button size="xs" variant="ghost" icon="pencil-square" wire:click="editTp({{ $tp->id }})" />
                                        <flux:button size="xs" variant="ghost" icon="trash" class="text-red-500" wire:confirm="Hapus TP ini?" wire:click="deleteTp({{ $tp->id }})" />
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-8 text-center text-zinc-500">
                                        Belum ada TP untuk Fase {{ $managingSubject?->phase }}. Tambahkan TP di atas.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @elseif($managingSubject && !$managingSubject->phase)
                <div class="flex flex-col items-center justify-center py-8 text-zinc-500 border-2 border-dashed rounded-xl">
                    <flux:icon icon="exclamation-triangle" class="w-10 h-10 mb-2 opacity-20" />
                    <p>Mata pelajaran ini tidak memiliki fase yang valid.</p>
                </div>
            @endif

            <div class="flex justify-end">
                <flux:modal.close>
                    <flux:button variant="ghost">Tutup</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>
</div>
