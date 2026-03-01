<?php

declare(strict_types=1);

use App\Models\AcademicYear;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('components.admin.layouts.app')] class extends Component {
    use WithPagination;

    public string $name = '';
    public string $start_date = '';
    public string $end_date = '';
    public bool $is_active = false;
    public string $status = 'open';
    public bool $yearModal = false;

    public ?AcademicYear $editing = null;

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'status' => ['required', 'in:open,closed'],
        ];
    }

    public function createNew(): void
    {
        $this->reset(['name', 'start_date', 'end_date', 'is_active', 'status', 'editing']);
        $this->yearModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->editing) {
            $this->editing->update($validated);
        } else {
            AcademicYear::create($validated);
        }

        if ($this->is_active) {
            $this->setActive($this->editing ?? AcademicYear::latest()->first());
        }

        $this->reset(['name', 'start_date', 'end_date', 'is_active', 'status', 'editing']);
        $this->yearModal = false;
    }

    public function edit(AcademicYear $year): void
    {
        $this->editing = $year;
        $this->name = $year->name;
        $this->start_date = $year->start_date->format('Y-m-d');
        $this->end_date = $year->end_date->format('Y-m-d');
        $this->is_active = $year->is_active;
        $this->status = $year->status;

        $this->yearModal = true;
    }

    public function setActive(AcademicYear $year): void
    {
        AcademicYear::where('id', '!=', $year->id)->update(['is_active' => false]);
        $year->update(['is_active' => true]);
    }

    public function delete(AcademicYear $year): void
    {
        $year->delete();
    }

    public function with(): array
    {
        return [
            'years' => AcademicYear::latest()->paginate(10),
        ];
    }
}; ?>

<div class="p-6 space-y-6">
    <x-ui.header :title="__('Tahun Ajaran')" :subtitle="__('Kelola tahun akademik sekolah Anda di sini.')" separator>
        <x-slot:actions>
            <x-ui.button :label="__('Tambah Tahun Ajaran')" icon="o-plus" class="btn-primary" wire:click="createNew" />
        </x-slot:actions>
    </x-ui.header>

    <x-ui.card shadow padding="false">
        <x-ui.table 
            :headers="[
                ['key' => 'name', 'label' => __('Nama')],
                ['key' => 'dates', 'label' => __('Rentang Waktu')],
                ['key' => 'status', 'label' => __('Status')],
                ['key' => 'actions', 'label' => '', 'class' => 'text-right']
            ]" 
            :rows="$years"
        >
            @scope('cell_name', $year)
                <div class="flex items-center gap-2">
                    <span class="font-bold text-slate-900 dark:text-white">{{ $year->name }}</span>
                    @if($year->is_active)
                        <x-ui.badge :label="__('Aktif')" class="bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 text-[10px]" />
                    @endif
                </div>
            @endscope

            @scope('cell_dates', $year)
                <span class="text-slate-500 dark:text-slate-400 text-sm">
                    {{ $year->start_date->format('d M Y') }} - {{ $year->end_date->format('d M Y') }}
                </span>
            @endscope

            @scope('cell_status', $year)
                <x-ui.badge 
                    :label="$year->status === 'open' ? __('Terbuka') : __('Ditutup')" 
                    class="{{ $year->status === 'open' ? 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' }} text-[10px]" 
                />
            @endscope

            @scope('cell_actions', $year)
                <div class="flex justify-end gap-1">
                    @if(!$year->is_active)
                        <x-ui.button :label="__('Set Aktif')" wire:click="setActive({{ $year->id }})" ghost sm />
                    @endif
                    <x-ui.button icon="o-pencil-square" wire:click="edit({{ $year->id }})" ghost sm />
                    <x-ui.button 
                        icon="o-trash" 
                        class="text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20" 
                        wire:confirm="{{ __('Yakin ingin menghapus ini?') }}" 
                        wire:click="delete({{ $year->id }})" 
                        ghost sm 
                    />
                </div>
            @endscope
        </x-ui.table>
    </x-ui.card>

    <div class="mt-4">
        {{ $years->links() }}
    </div>

    <x-ui.modal wire:model="yearModal" persistent>
        <x-ui.header :title="$editing ? __('Edit Tahun Ajaran') : __('Tambah Tahun Ajaran Baru')" :subtitle="__('Masukkan detail tahun ajaran di bawah ini.')" separator />

        <form wire:submit="save" class="space-y-6">
            <x-ui.input wire:model="name" :label="__('Nama (Contoh: 2024/2025)')" required />

            <div class="grid grid-cols-2 gap-4">
                <x-ui.input wire:model="start_date" type="date" :label="__('Tanggal Mulai')" required />
                <x-ui.input wire:model="end_date" type="date" :label="__('Tanggal Selesai')" required />
            </div>

            <x-ui.select 
                wire:model="status" 
                :label="__('Status')" 
                :options="[
                    ['id' => 'open', 'name' => __('Terbuka')],
                    ['id' => 'closed', 'name' => __('Ditutup')],
                ]" 
                option-label="name"
            />

            <x-ui.checkbox wire:model="is_active" :label="__('Jadikan Tahun Aktif')" />

            <div class="flex justify-end gap-2 pt-4">
                <x-ui.button :label="__('Batal')" ghost @click="show = false" />
                <x-ui.button :label="__('Simpan')" type="submit" class="btn-primary" spinner="save" />
            </div>
        </form>
    </x-ui.modal>
</div>
