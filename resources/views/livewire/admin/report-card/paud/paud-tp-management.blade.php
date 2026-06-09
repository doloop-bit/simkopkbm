<?php

declare(strict_types=1);

use App\Models\PaudCpElement;
use App\Models\PaudSklItem;
use App\Models\PaudTp;
use App\Models\Classroom;
use App\Models\AcademicYear;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public ?int $academic_year_id = null;
    public ?int $classroom_id = null;
    public string $semester = '1';

    // Form fields
    public ?int $tpId = null;
    public ?int $paud_cp_element_id = null;
    public ?int $paud_skl_item_id = null;
    public string $code = '';
    public string $description = '';
    public int $order = 0;

    public bool $showFormModal = false;
    public bool $isEditing = false;

    public function mount(): void
    {
        if (!auth()->user()->isAdmin() && !auth()->user()->teachesPaudLevel()) {
            abort(403);
        }

        $activeYear = AcademicYear::where('is_active', true)->first();
        if ($activeYear) {
            $this->academic_year_id = $activeYear->id;
        }
    }

    public function updatedClassroomId(): void
    {
        $this->resetForm();
    }

    public function updatedAcademicYearId(): void
    {
        $this->resetForm();
    }

    public function updatedSemester(): void
    {
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->reset(['tpId', 'paud_cp_element_id', 'paud_skl_item_id', 'code', 'description', 'order', 'isEditing', 'showFormModal']);
        $this->resetValidation();
    }

    public function openCreateModal(int $cpElementId): void
    {
        $this->resetForm();
        $this->paud_cp_element_id = $cpElementId;
        
        // Auto-generate code e.g. TP-1
        $count = PaudTp::where([
            'classroom_id' => $this->classroom_id,
            'academic_year_id' => $this->academic_year_id,
            'semester' => $this->semester,
            'paud_cp_element_id' => $cpElementId,
        ])->count();

        $cp = PaudCpElement::find($cpElementId);
        $prefix = $cp ? strtoupper(substr($cp->code, 0, 3)) : 'TP';
        $this->code = $prefix . '-' . ($count + 1);
        $this->order = $count + 1;
        $this->showFormModal = true;
    }

    public function editTp(int $id): void
    {
        $this->resetForm();
        $tp = PaudTp::findOrFail($id);
        $this->tpId = $tp->id;
        $this->paud_cp_element_id = $tp->paud_cp_element_id;
        $this->paud_skl_item_id = $tp->paud_skl_item_id;
        $this->code = $tp->code;
        $this->description = $tp->description;
        $this->order = $tp->order;
        $this->isEditing = true;
        $this->showFormModal = true;
    }

    public function saveTp(): void
    {
        $rules = [
            'classroom_id' => 'required|integer',
            'academic_year_id' => 'required|integer',
            'semester' => 'required|string',
            'paud_cp_element_id' => 'required|integer',
            'paud_skl_item_id' => 'nullable|integer',
            'code' => 'required|string|max:50',
            'description' => 'required|string',
            'order' => 'required|integer|min:0',
        ];

        $this->validate($rules);

        PaudTp::updateOrCreate(
            ['id' => $this->tpId],
            [
                'classroom_id' => $this->classroom_id,
                'academic_year_id' => $this->academic_year_id,
                'semester' => $this->semester,
                'paud_cp_element_id' => $this->paud_cp_element_id,
                'paud_skl_item_id' => $this->paud_skl_item_id,
                'code' => $this->code,
                'description' => $this->description,
                'order' => $this->order,
            ]
        );

        $this->resetForm();
        session()->flash('success', $this->tpId ? __('Tujuan Pembelajaran berhasil diperbarui.') : __('Tujuan Pembelajaran berhasil ditambahkan.'));
    }

    public function deleteTp(int $id): void
    {
        $tp = PaudTp::findOrFail($id);

        if ($tp->assessments()->exists()) {
            session()->flash('error', __('Tujuan Pembelajaran tidak dapat dihapus karena sudah ada nilai siswa terkait.'));
            return;
        }

        $tp->delete();
        session()->flash('success', __('Tujuan Pembelajaran berhasil dihapus.'));
    }

    public function with(): array
    {
        $cpElements = [];
        $tps = [];

        if ($this->classroom_id && $this->academic_year_id) {
            $cpElements = PaudCpElement::orderBy('order')->get();
            $tps = PaudTp::where([
                'classroom_id' => $this->classroom_id,
                'academic_year_id' => $this->academic_year_id,
                'semester' => $this->semester,
            ])->orderBy('order')->get()->groupBy('paud_cp_element_id');
        }

        return [
            'years' => AcademicYear::orderBy('name', 'desc')->get(),
            'classrooms' => Classroom::whereHas('level', fn($q) => $q->where('education_level', 'PAUD'))
                ->when($this->academic_year_id, fn($q) => $q->where('academic_year_id', $this->academic_year_id))
                ->orderBy('name')->get(),
            'cpElements' => $cpElements,
            'sklItems' => PaudSklItem::orderBy('order')->get(),
            'tps' => $tps,
        ];
    }
}; ?>

<div class="p-6 space-y-8 text-slate-900 dark:text-white pb-24 md:pb-6">
    @if (session('success'))
        <x-ui.alert :title="__('Sukses')" icon="o-check-circle" class="bg-emerald-50 text-emerald-800 border-emerald-100" dismissible>
            {{ session('success') }}
        </x-ui.alert>
    @endif

    @if (session('error'))
        <x-ui.alert :title="__('Error')" icon="o-x-circle" class="bg-rose-50 text-rose-800 border-rose-100" dismissible>
            {{ session('error') }}
        </x-ui.alert>
    @endif

    <x-ui.header :title="__('Tujuan Pembelajaran (TP) PAUD')" :subtitle="__('Kelola Tujuan Pembelajaran (TP) per Elemen Capaian Pembelajaran.')" separator />

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <x-ui.select wire:model.live="academic_year_id" :label="__('Tahun Ajaran')" :options="$years" />
        <x-ui.select 
            wire:model.live="semester" 
            :label="__('Semester')" 
            :options="[
                ['id' => '1', 'name' => __('Semester 1')],
                ['id' => '2', 'name' => __('Semester 2')],
            ]" 
        />
        <x-ui.select 
            wire:model.live="classroom_id" 
            :label="__('Kelas / Rombel')" 
            :placeholder="__('Pilih Kelas')"
            :options="$classrooms"
        />
    </div>

    @if ($classroom_id && $academic_year_id)
        <div class="space-y-6">
            @foreach ($cpElements as $cp)
                <div class="border border-slate-100 dark:border-slate-800 rounded-3xl bg-white dark:bg-slate-950 p-6 shadow-sm">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6 pb-4 border-b border-slate-100 dark:border-slate-800">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 bg-primary/10 text-primary text-[10px] font-black rounded-md">{{ strtoupper($cp->code) }}</span>
                                <h3 class="font-bold text-slate-800 dark:text-white">{{ $cp->name }}</h3>
                            </div>
                            <p class="text-xs text-slate-400 mt-1 italic">{{ $cp->description }}</p>
                        </div>
                        <x-ui.button :label="__('Tambah TP')" icon="o-plus" class="btn-primary btn-sm shrink-0" wire:click="openCreateModal({{ $cp->id }})" />
                    </div>

                    @php
                        $cpTps = $tps[$cp->id] ?? collect();
                    @endphp

                    <x-ui.table :headers="[
                        ['key' => 'code', 'label' => __('Kode'), 'class' => 'w-24'],
                        ['key' => 'description', 'label' => __('Deskripsi Tujuan Pembelajaran')],
                        ['key' => 'skl', 'label' => __('Tagging SKL')],
                        ['key' => 'actions', 'label' => '', 'class' => 'text-right']
                    ]" :rows="$cpTps">
                        @scope('cell_code', $tp)
                            <span class="font-bold font-mono text-xs">{{ $tp->code }}</span>
                        @endscope

                        @scope('cell_description', $tp)
                            <div class="flex flex-col">
                                <span class="text-sm font-medium">{{ $tp->description }}</span>
                                <span class="text-[10px] text-slate-400 font-mono mt-1">Urutan: {{ $tp->order }}</span>
                            </div>
                        @endscope

                        @scope('cell_skl', $tp)
                            @if ($tp->sklItem)
                                <x-ui.badge :label="$tp->sklItem->name" class="bg-indigo-50 text-indigo-700 border-none text-[10px] max-w-xs truncate" />
                            @else
                                <span class="text-xs text-slate-400 italic">{{ __('Tidak ditag') }}</span>
                            @endif
                        @endscope

                        @scope('cell_actions', $tp)
                            <div class="flex justify-end gap-2">
                                <x-ui.button icon="o-pencil" wire:click="editTp({{ $tp->id }})" class="btn-ghost btn-sm text-sky-500 hover:bg-sky-50 transition-colors" spinner />
                                <x-ui.button icon="o-trash" wire:click="deleteTp({{ $tp->id }})" wire:confirm="{{ __('Hapus TP ini secara permanen?') }}" class="btn-ghost btn-sm text-slate-400 hover:text-rose-600 transition-colors" spinner />
                            </div>
                        @endscope
                    </x-ui.table>

                    @if($cpTps->isEmpty())
                        <div class="py-12 text-center text-slate-400 italic text-sm">
                            {{ __('Belum ada Tujuan Pembelajaran untuk elemen CP ini.') }}
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <div class="flex flex-col items-center justify-center py-32 text-slate-300 dark:text-slate-700 border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-[32px] bg-slate-50/50 dark:bg-slate-900/50 transition-all">
            <x-ui.icon name="o-folder-open" class="size-20 mb-6 opacity-20" />
            <p class="text-sm font-black uppercase tracking-widest italic animate-pulse">{{ __('Tentukan Tahun Ajaran & Kelas Terlebih Dahulu') }}</p>
        </div>
    @endif

    {{-- Form Modal --}}
    <x-ui.modal wire:model="showFormModal" :title="$isEditing ? __('Ubah Tujuan Pembelajaran') : __('Tambah Tujuan Pembelajaran')" persistent>
        <form wire:submit="saveTp" class="space-y-6">
            <x-ui.input wire:model="code" :label="__('Kode TP')" :placeholder="__('Contoh: AGAMA-1')" required />
            <x-ui.textarea wire:model="description" :label="__('Deskripsi Tujuan Pembelajaran')" rows="4" :placeholder="__('Contoh: Anak dapat mempercayai adanya Tuhan melalui ciptaan-Nya...')" required />
            
            <x-ui.select 
                wire:model="paud_skl_item_id" 
                :label="__('Tagging SKL / STTPA (Opsional)')" 
                :placeholder="__('Pilih SKL/STTPA')"
                :options="$sklItems" 
            />

            <x-ui.input wire:model="order" type="number" :label="__('Urutan')" required />

            <div class="flex justify-end gap-3 pt-6">
                <x-ui.button :label="__('Batal')" wire:click="resetForm" class="btn-ghost" />
                <x-ui.button type="submit" :label="__('Simpan')" icon="o-check" class="btn-primary" spinner="saveTp" />
            </div>
        </form>
    </x-ui.modal>
</div>
