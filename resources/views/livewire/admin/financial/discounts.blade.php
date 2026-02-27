<?php

declare(strict_types=1);

use App\Models\StudentFeeDiscount;
use App\Models\User;
use App\Models\FeeCategory;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

new #[Layout('components.admin.layouts.app')] class extends Component {
    use WithPagination, Toast;

    public ?int $student_id = null;
    public ?int $fee_category_id = null;
    public string $name = '';
    public string $discount_type = 'fixed';
    public float $amount = 0;

    public ?StudentFeeDiscount $editing = null;
    public bool $discountModal = false;

    public function rules(): array
    {
        return [
            'student_id' => 'required|exists:users,id',
            'fee_category_id' => 'nullable|exists:fee_categories,id',
            'name' => 'required|string|max:255',
            'discount_type' => 'required|in:percentage,fixed',
            'amount' => 'required|numeric|min:0',
        ];
    }

    public function create(): void
    {
        $this->reset(['student_id', 'fee_category_id', 'name', 'discount_type', 'amount', 'editing']);
        $this->discountModal = true;
    }

    public function save(): void
    {
        $this->validate();

        if ($this->editing) {
            $this->editing->update([
                'student_id' => $this->student_id,
                'fee_category_id' => $this->fee_category_id ?: null,
                'name' => $this->name,
                'discount_type' => $this->discount_type,
                'amount' => $this->amount,
            ]);
            $this->success('Potongan/Beasiswa berhasil diperbarui.');
        } else {
            StudentFeeDiscount::create([
                'student_id' => $this->student_id,
                'fee_category_id' => $this->fee_category_id ?: null,
                'name' => $this->name,
                'discount_type' => $this->discount_type,
                'amount' => $this->amount,
            ]);
            $this->success('Potongan/Beasiswa berhasil ditambahkan.');
        }

        $this->discountModal = false;
        $this->reset(['student_id', 'fee_category_id', 'name', 'discount_type', 'amount', 'editing']);
    }

    public function edit(StudentFeeDiscount $discount): void
    {
        $this->editing = $discount;
        $this->student_id = $discount->student_id;
        $this->fee_category_id = $discount->fee_category_id;
        $this->name = $discount->name;
        $this->discount_type = $discount->discount_type;
        $this->amount = (float) $discount->amount;
        $this->discountModal = true;
    }

    public function delete(StudentFeeDiscount $discount): void
    {
        $discount->delete();
        $this->success('Potongan/Beasiswa berhasil dihapus.');
    }

    public function with(): array
    {
        return [
            'discounts' => StudentFeeDiscount::with(['student', 'feeCategory'])->latest()->paginate(10),
            'students' => User::where('role', 'siswa')->orderBy('name')->get(),
            'categories' => FeeCategory::all(),
        ];
    }
}; ?>

<div class="p-6">
    <x-header title="Potongan & Beasiswa" subtitle="Kelola variasi biaya, diskon, dan beasiswa untuk siswa." separator>
        <x-slot:actions>
            <x-button label="Tambah Diskon" icon="o-plus" class="btn-primary" wire:click="create" />
        </x-slot:actions>
    </x-header>

    <div class="overflow-x-auto border rounded-xl border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm">
        <table class="table">
            <thead>
                <tr>
                    <th class="bg-base-200">Nama Siswa</th>
                    <th class="bg-base-200">Kategori Biaya</th>
                    <th class="bg-base-200">Detail Diskon</th>
                    <th class="bg-base-200 text-right">Nilai Potongan</th>
                    <th class="bg-base-200 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($discounts as $discount)
                    <tr class="hover" wire:key="{{ $discount->id }}">
                        <td class="font-medium whitespace-nowrap">
                            {{ $discount->student?->name ?? 'Siswa Dihapus' }}
                        </td>
                        <td class="opacity-70 whitespace-nowrap">
                            {{ $discount->feeCategory?->name ?? 'Semua Biaya' }}
                        </td>
                        <td>
                            <div class="flex flex-col">
                                <span class="font-bold">{{ $discount->name }}</span>
                                <span class="text-xs opacity-60">{{ $discount->discount_type === 'percentage' ? 'Persentase' : 'Nominal Tetap' }}</span>
                            </div>
                        </td>
                        <td class="text-right font-mono font-bold">
                            @if($discount->discount_type === 'percentage')
                                {{ $discount->amount }}%
                            @else
                                Rp {{ number_format($discount->amount, 0, ',', '.') }}
                            @endif
                        </td>
                        <td class="text-right">
                            <div class="flex justify-end gap-1">
                                <x-button icon="o-pencil-square" wire:click="edit({{ $discount->id }})" ghost sm />
                                <x-button icon="o-trash" class="text-error" wire:confirm="Hapus diskon ini?" wire:click="delete({{ $discount->id }})" ghost sm />
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $discounts->links() }}
    </div>

    {{-- Add/Edit Modal --}}
    <x-modal wire:model="discountModal" class="backdrop-blur">
        <x-header :title="$editing ? 'Edit Diskon' : 'Tambah Diskon'" subtitle="Tentukan potongan atau beasiswa untuk siswa." separator />

        <div class="grid grid-cols-1 gap-4 text-left">
            <x-select wire:model="student_id" label="Pilih Siswa" placeholder="Pilih Siswa" :options="$students" />
            
            <x-select wire:model="fee_category_id" label="Kategori Biaya (Opsional)" placeholder="Semua Kategori Biaya" :options="collect($categories)->map(fn($c) => ['id' => $c->id, 'name' => $c->name . ($c->level ? ' (' . $c->level->name . ')' : ' (Umum)')])->toArray()" />
            
            <x-input wire:model="name" label="Nama Potongan / Beasiswa" placeholder="Contoh: Beasiswa Prestasi, Anak Guru" />
            
            <div class="grid grid-cols-2 gap-4">
                <x-select wire:model="discount_type" label="Tipe Diskon" :options="[['id' => 'fixed', 'name' => 'Nominal Tetap (Rp)'], ['id' => 'percentage', 'name' => 'Persentase (%)']]" />
                <x-input wire:model="amount" type="number" label="Nilai Potongan" step="1" />
            </div>
        </div>

        <x-slot:actions>
            <x-button label="Batal" @click="$set('discountModal', false)" />
            <x-button label="Simpan" class="btn-primary" wire:click="save" spinner="save" />
        </x-slot:actions>
    </x-modal>
</div>
