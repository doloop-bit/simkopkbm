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

<div class="p-6">
    <x-header title="Tahun Ajaran" subtitle="Kelola tahun akademik sekolah Anda di sini." separator>
        <x-slot:actions>
            <x-button label="Tambah Tahun Ajaran" icon="o-plus" class="btn-primary" wire:click="createNew" />
        </x-slot:actions>
    </x-header>

    <div class="bg-base-100 rounded-lg shadow-sm border border-base-200">
        <table class="table">
            <thead>
                <tr>
                    <th class="bg-base-200">Nama</th>
                    <th class="bg-base-200">Rentang Waktu</th>
                    <th class="bg-base-200">Status</th>
                    <th class="bg-base-200 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($years as $year)
                    <tr wire:key="{{ $year->id }}" class="hover">
                        <td>
                            <div class="flex items-center gap-2">
                                <span class="font-bold">{{ $year->name }}</span>
                                @if($year->is_active)
                                    <x-badge label="Aktif" class="badge-success badge-sm" />
                                @endif
                            </div>
                        </td>
                        <td class="opacity-70">
                            {{ $year->start_date->format('d M Y') }} - {{ $year->end_date->format('d M Y') }}
                        </td>
                        <td>
                            <x-badge 
                                :label="$year->status === 'open' ? 'Terbuka' : 'Ditutup'" 
                                class="{{ $year->status === 'open' ? 'badge-neutral' : 'badge-warning' }} badge-sm" 
                            />
                        </td>
                        <td class="text-right">
                            <div class="flex justify-end gap-1">
                                @if(!$year->is_active)
                                    <x-button label="Set Aktif" wire:click="setActive({{ $year->id }})" ghost sm />
                                @endif
                                <x-button icon="o-pencil-square" wire:click="edit({{ $year->id }})" ghost sm />
                                <x-button 
                                    icon="o-trash" 
                                    class="text-error" 
                                    wire:confirm="Yakin ingin menghapus ini?" 
                                    wire:click="delete({{ $year->id }})" 
                                    ghost sm 
                                />
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $years->links() }}
    </div>

    <x-modal wire:model="yearModal" class="backdrop-blur" persistent>
        <x-header :title="$editing ? 'Edit Tahun Ajaran' : 'Tambah Tahun Ajaran Baru'" subtitle="Masukkan detail tahun ajaran di bawah ini." separator />

        <form wire:submit="save">
            <div class="space-y-4">
                <x-input wire:model="name" label="Nama (Contoh: 2024/2025)" required />

                <div class="grid grid-cols-2 gap-4">
                    <x-input wire:model="start_date" type="date" label="Tanggal Mulai" required />
                    <x-input wire:model="end_date" type="date" label="Tanggal Selesai" required />
                </div>

                <x-select 
                    wire:model="status" 
                    label="Status" 
                    :options="[
                        ['id' => 'open', 'name' => 'Terbuka'],
                        ['id' => 'closed', 'name' => 'Ditutup'],
                    ]" 
                />

                <x-checkbox wire:model="is_active" label="Jadikan Tahun Aktif" />
            </div>

            <x-slot:actions>
                <x-button label="Batal" @click="$set('yearModal', false)" />
                <x-button label="Simpan" type="submit" class="btn-primary" spinner="save" />
            </x-slot:actions>
        </form>
    </x-modal>
</div>
