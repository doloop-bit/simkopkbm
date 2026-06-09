<?php

declare(strict_types=1);

use App\Models\PaudCpElement;
use App\Models\PaudSklItem;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public string $activeTab = 'cp'; // 'cp' or 'skl'

    // Form inputs for CP Elements
    public ?int $cpId = null;
    public string $cpName = '';
    public string $cpCode = '';
    public string $cpDescription = '';
    public int $cpOrder = 0;

    // Form inputs for SKL Items
    public ?int $sklId = null;
    public string $sklName = '';
    public string $sklCode = '';
    public string $sklDescription = '';
    public int $sklOrder = 0;

    public bool $isEditingCp = false;
    public bool $isEditingSkl = false;

    public function mount(): void
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }
    }

    public function selectTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->reset([
            'cpId', 'cpName', 'cpCode', 'cpDescription', 'cpOrder', 'isEditingCp',
            'sklId', 'sklName', 'sklCode', 'sklDescription', 'sklOrder', 'isEditingSkl'
        ]);
        $this->resetValidation();
    }

    // --- CP Elements CRUD ---
    public function saveCp(): void
    {
        $rules = [
            'cpName' => 'required|string|max:255',
            'cpCode' => 'required|string|max:50|unique:paud_cp_elements,code,' . ($this->cpId ?? 'NULL') . ',id',
            'cpDescription' => 'nullable|string',
            'cpOrder' => 'required|integer|min:0',
        ];

        $this->validate($rules);

        PaudCpElement::updateOrCreate(
            ['id' => $this->cpId],
            [
                'name' => $this->cpName,
                'code' => $this->cpCode,
                'description' => $this->cpDescription,
                'order' => $this->cpOrder,
            ]
        );

        $this->resetForm();
        session()->flash('success', $this->cpId ? __('Elemen CP berhasil diperbarui.') : __('Elemen CP berhasil ditambahkan.'));
    }

    public function editCp(int $id): void
    {
        $this->resetForm();
        $cp = PaudCpElement::findOrFail($id);
        $this->cpId = $cp->id;
        $this->cpName = $cp->name;
        $this->cpCode = $cp->code;
        $this->cpDescription = $cp->description ?? '';
        $this->cpOrder = $cp->order;
        $this->isEditingCp = true;
    }

    public function deleteCp(int $id): void
    {
        $cp = PaudCpElement::findOrFail($id);

        if ($cp->tps()->exists()) {
            session()->flash('error', __('Elemen CP tidak dapat dihapus karena memiliki TP terkait.'));
            return;
        }

        $cp->delete();
        session()->flash('success', __('Elemen CP berhasil dihapus.'));
    }

    // --- SKL Items CRUD ---
    public function saveSkl(): void
    {
        $rules = [
            'sklName' => 'required|string|max:255',
            'sklCode' => 'required|string|max:50|unique:paud_skl_items,code,' . ($this->sklId ?? 'NULL') . ',id',
            'sklDescription' => 'nullable|string',
            'sklOrder' => 'required|integer|min:0',
        ];

        $this->validate($rules);

        PaudSklItem::updateOrCreate(
            ['id' => $this->sklId],
            [
                'name' => $this->sklName,
                'code' => $this->sklCode,
                'description' => $this->sklDescription,
                'order' => $this->sklOrder,
            ]
        );

        $this->resetForm();
        session()->flash('success', $this->sklId ? __('Item SKL berhasil diperbarui.') : __('Item SKL berhasil ditambahkan.'));
    }

    public function editSkl(int $id): void
    {
        $this->resetForm();
        $skl = PaudSklItem::findOrFail($id);
        $this->sklId = $skl->id;
        $this->sklName = $skl->name;
        $this->sklCode = $skl->code;
        $this->sklDescription = $skl->description ?? '';
        $this->sklOrder = $skl->order;
        $this->isEditingSkl = true;
    }

    public function deleteSkl(int $id): void
    {
        $skl = PaudSklItem::findOrFail($id);

        if ($skl->tps()->exists()) {
            session()->flash('error', __('Item SKL tidak dapat dihapus karena memiliki TP terkait.'));
            return;
        }

        $skl->delete();
        session()->flash('success', __('Item SKL berhasil dihapus.'));
    }

    public function moveCp(int $id, string $direction): void
    {
        $cp = PaudCpElement::findOrFail($id);
        $currentOrder = $cp->order;

        if ($direction === 'up') {
            $swapCp = PaudCpElement::where('order', '<', $currentOrder)->orderBy('order', 'desc')->first();
        } else {
            $swapCp = PaudCpElement::where('order', '>', $currentOrder)->orderBy('order', 'asc')->first();
        }

        if ($swapCp) {
            $cp->update(['order' => $swapCp->order]);
            $swapCp->update(['order' => $currentOrder]);
        }
    }

    public function moveSkl(int $id, string $direction): void
    {
        $skl = PaudSklItem::findOrFail($id);
        $currentOrder = $skl->order;

        if ($direction === 'up') {
            $swapSkl = PaudSklItem::where('order', '<', $currentOrder)->orderBy('order', 'desc')->first();
        } else {
            $swapSkl = PaudSklItem::where('order', '>', $currentOrder)->orderBy('order', 'asc')->first();
        }

        if ($swapSkl) {
            $skl->update(['order' => $swapSkl->order]);
            $swapSkl->update(['order' => $currentOrder]);
        }
    }

    public function with(): array
    {
        return [
            'cpElements' => PaudCpElement::orderBy('order')->get(),
            'sklItems' => PaudSklItem::orderBy('order')->get(),
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

    <x-ui.header :title="__('Master Data Rapor PAUD')" :subtitle="__('Kelola Elemen Capaian Pembelajaran (CP) dan Item SKL/STTPA untuk jenjang PAUD.')" separator />

    {{-- Tabs --}}
    <div class="flex gap-4 border-b border-slate-100 dark:border-slate-800 pb-px">
        <button 
            wire:click="selectTab('cp')" 
            class="pb-4 px-2 font-black text-sm uppercase tracking-wider border-b-2 transition-all duration-300 {{ $activeTab === 'cp' ? 'border-primary text-primary' : 'border-transparent text-slate-400 hover:text-slate-600' }}">
            {{ __('Elemen CP') }}
        </button>
        <button 
            wire:click="selectTab('skl')" 
            class="pb-4 px-2 font-black text-sm uppercase tracking-wider border-b-2 transition-all duration-300 {{ $activeTab === 'skl' ? 'border-primary text-primary' : 'border-transparent text-slate-400 hover:text-slate-600' }}">
            {{ __('SKL / STTPA') }}
        </button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {{-- List Component --}}
        <div class="lg:col-span-2">
            @if ($activeTab === 'cp')
                <x-ui.card shadow padding="false">
                    <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                        <h3 class="font-bold text-slate-800 dark:text-white uppercase tracking-tight text-sm">
                            {{ __('Daftar Elemen Capaian Pembelajaran (CP)') }}
                        </h3>
                    </div>

                    <x-ui.table :headers="[
                        ['key' => 'order', 'label' => '#', 'class' => 'w-12 text-center'],
                        ['key' => 'name', 'label' => __('Nama Elemen CP')],
                        ['key' => 'code', 'label' => __('Kode')],
                        ['key' => 'actions', 'label' => '', 'class' => 'text-right']
                    ]" :rows="$cpElements">
                        @scope('cell_order', $cp)
                            <div class="flex flex-col items-center justify-center gap-1">
                                <button wire:click="moveCp({{ $cp->id }}, 'up')" class="text-slate-400 hover:text-primary transition-colors">
                                    <x-ui.icon name="o-chevron-up" class="size-3" />
                                </button>
                                <span class="font-mono text-xs font-bold">{{ $cp->order }}</span>
                                <button wire:click="moveCp({{ $cp->id }}, 'down')" class="text-slate-400 hover:text-primary transition-colors">
                                    <x-ui.icon name="o-chevron-down" class="size-3" />
                                </button>
                            </div>
                        @endscope

                        @scope('cell_name', $cp)
                            <div class="flex flex-col">
                                <span class="font-bold text-slate-900 dark:text-white">{{ $cp->name }}</span>
                                <span class="text-xs text-slate-400 italic">{{ Str::limit($cp->description, 100) }}</span>
                            </div>
                        @endscope

                        @scope('cell_code', $cp)
                            <x-ui.badge :label="$cp->code" class="bg-slate-100 text-slate-700 border-none font-mono text-[10px]" />
                        @endscope

                        @scope('cell_actions', $cp)
                            <div class="flex justify-end gap-2">
                                <x-ui.button icon="o-pencil" wire:click="editCp({{ $cp->id }})" class="btn-ghost btn-sm text-sky-500 hover:bg-sky-50 transition-colors" spinner />
                                <x-ui.button icon="o-trash" wire:click="deleteCp({{ $cp->id }})" wire:confirm="{{ __('Hapus elemen CP ini secara permanen?') }}" class="btn-ghost btn-sm text-slate-400 hover:text-rose-600 transition-colors" spinner />
                            </div>
                        @endscope
                    </x-ui.table>

                    @if($cpElements->isEmpty())
                        <div class="py-12 text-center text-slate-400 italic text-sm">
                            {{ __('Belum ada elemen CP terdaftar.') }}
                        </div>
                    @endif
                </x-ui.card>
            @else
                <x-ui.card shadow padding="false">
                    <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                        <h3 class="font-bold text-slate-800 dark:text-white uppercase tracking-tight text-sm">
                            {{ __('Daftar Standar Tingkat Pencapaian Perkembangan Anak (STTPA/SKL)') }}
                        </h3>
                    </div>

                    <x-ui.table :headers="[
                        ['key' => 'order', 'label' => '#', 'class' => 'w-12 text-center'],
                        ['key' => 'name', 'label' => __('Nama Standar SKL')],
                        ['key' => 'code', 'label' => __('Kode')],
                        ['key' => 'actions', 'label' => '', 'class' => 'text-right']
                    ]" :rows="$sklItems">
                        @scope('cell_order', $skl)
                            <div class="flex flex-col items-center justify-center gap-1">
                                <button wire:click="moveSkl({{ $skl->id }}, 'up')" class="text-slate-400 hover:text-primary transition-colors">
                                    <x-ui.icon name="o-chevron-up" class="size-3" />
                                </button>
                                <span class="font-mono text-xs font-bold">{{ $skl->order }}</span>
                                <button wire:click="moveSkl({{ $skl->id }}, 'down')" class="text-slate-400 hover:text-primary transition-colors">
                                    <x-ui.icon name="o-chevron-down" class="size-3" />
                                </button>
                            </div>
                        @endscope

                        @scope('cell_name', $skl)
                            <div class="flex flex-col">
                                <span class="font-bold text-slate-900 dark:text-white">{{ $skl->name }}</span>
                                <span class="text-xs text-slate-400 italic">{{ Str::limit($skl->description, 100) }}</span>
                            </div>
                        @endscope

                        @scope('cell_code', $skl)
                            <x-ui.badge :label="$skl->code" class="bg-slate-100 text-slate-700 border-none font-mono text-[10px]" />
                        @endscope

                        @scope('cell_actions', $skl)
                            <div class="flex justify-end gap-2">
                                <x-ui.button icon="o-pencil" wire:click="editSkl({{ $skl->id }})" class="btn-ghost btn-sm text-sky-500 hover:bg-sky-50 transition-colors" spinner />
                                <x-ui.button icon="o-trash" wire:click="deleteSkl({{ $skl->id }})" wire:confirm="{{ __('Hapus item SKL ini secara permanen?') }}" class="btn-ghost btn-sm text-slate-400 hover:text-rose-600 transition-colors" spinner />
                            </div>
                        @endscope
                    </x-ui.table>

                    @if($sklItems->isEmpty())
                        <div class="py-12 text-center text-slate-400 italic text-sm">
                            {{ __('Belum ada item SKL terdaftar.') }}
                        </div>
                    @endif
                </x-ui.card>
            @endif
        </div>

        {{-- Form Component --}}
        <div>
            @if ($activeTab === 'cp')
                <x-ui.card shadow>
                    <h3 class="font-bold text-slate-800 dark:text-white uppercase tracking-tight text-sm mb-6">
                        {{ $isEditingCp ? __('Ubah Elemen CP') : __('Tambah Elemen CP') }}
                    </h3>

                    <form wire:submit="saveCp" class="space-y-6">
                        <x-ui.input wire:model="cpName" :label="__('Nama Elemen CP')" :placeholder="__('Contoh: Jati Diri')" required />
                        <x-ui.input wire:model="cpCode" :label="__('Kode Elemen CP (Unik)')" :placeholder="__('Contoh: jati_diri')" required />
                        <x-ui.textarea wire:model="cpDescription" :label="__('Deskripsi / Cakupan')" rows="4" :placeholder="__('Tulis deskripsi elemen di sini...')" />
                        <x-ui.input wire:model="cpOrder" type="number" :label="__('Urutan Tampilan')" required />

                        <div class="flex gap-2 justify-end pt-4">
                            @if ($isEditingCp)
                                <x-ui.button type="button" :label="__('Batal')" wire:click="resetForm" class="btn-ghost" />
                            @endif
                            <x-ui.button type="submit" :label="__('Simpan')" icon="o-check" class="btn-primary" spinner="saveCp" />
                        </div>
                    </form>
                </x-ui.card>
            @else
                <x-ui.card shadow>
                    <h3 class="font-bold text-slate-800 dark:text-white uppercase tracking-tight text-sm mb-6">
                        {{ $isEditingSkl ? __('Ubah Item SKL') : __('Tambah Item SKL') }}
                    </h3>

                    <form wire:submit="saveSkl" class="space-y-6">
                        <x-ui.input wire:model="sklName" :label="__('Nama Standar SKL')" :placeholder="__('Contoh: Kreativitas dan Estetika')" required />
                        <x-ui.input wire:model="sklCode" :label="__('Kode SKL (Unik)')" :placeholder="__('Contoh: kreativitas')" required />
                        <x-ui.textarea wire:model="sklDescription" :label="__('Deskripsi / Cakupan')" rows="4" :placeholder="__('Tulis deskripsi item di sini...')" />
                        <x-ui.input wire:model="sklOrder" type="number" :label="__('Urutan Tampilan')" required />

                        <div class="flex gap-2 justify-end pt-4">
                            @if ($isEditingSkl)
                                <x-ui.button type="button" :label="__('Batal')" wire:click="resetForm" class="btn-ghost" />
                            @endif
                            <x-ui.button type="submit" :label="__('Simpan')" icon="o-check" class="btn-primary" spinner="saveSkl" />
                        </div>
                    </form>
                </x-ui.card>
            @endif
        </div>
    </div>
</div>
