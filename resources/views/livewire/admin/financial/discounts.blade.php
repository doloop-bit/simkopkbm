<?php

declare(strict_types=1);

use App\Models\StudentFeeDiscount;
use App\Models\User;
use App\Models\FeeCategory;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('components.admin.layouts.app')] class extends Component {
    use WithPagination;

    public ?int $student_id = null;
    public ?int $fee_category_id = null;
    public string $name = '';
    public string $discount_type = 'fixed';
    public float $amount = 0;

    public ?StudentFeeDiscount $editing = null;

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
            \Flux::toast('Potongan/Beasiswa berhasil diperbarui.');
        } else {
            StudentFeeDiscount::create([
                'student_id' => $this->student_id,
                'fee_category_id' => $this->fee_category_id ?: null,
                'name' => $this->name,
                'discount_type' => $this->discount_type,
                'amount' => $this->amount,
            ]);
            \Flux::toast('Potongan/Beasiswa berhasil ditambahkan.');
        }

        $this->reset(['student_id', 'fee_category_id', 'name', 'discount_type', 'amount', 'editing']);
        $this->dispatch('close-modal');
    }

    public function edit(StudentFeeDiscount $discount): void
    {
        $this->editing = $discount;
        $this->student_id = $discount->student_id;
        $this->fee_category_id = $discount->fee_category_id;
        $this->name = $discount->name;
        $this->discount_type = $discount->discount_type;
        $this->amount = (float) $discount->amount;
    }

    public function delete(StudentFeeDiscount $discount): void
    {
        $discount->delete();
        \Flux::toast('Potongan/Beasiswa berhasil dihapus.');
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
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl" level="1">Potongan & Beasiswa</flux:heading>
            <flux:subheading>Kelola variasi biaya, diskon, dan beasiswa untuk siswa.</flux:subheading>
        </div>
        <flux:modal.trigger name="add-discount">
            <flux:button variant="primary" icon="plus">Tambah Diskon</flux:button>
        </flux:modal.trigger>
    </div>

    <div class="border rounded-lg bg-white dark:bg-zinc-900 overflow-hidden">
        <table class="w-full text-sm text-left border-collapse">
            <thead class="bg-zinc-50 dark:bg-zinc-800">
                <tr>
                    <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b">Nama Siswa</th>
                    <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b">Kategori Biaya</th>
                    <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b">Detail Diskon</th>
                    <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b">Nilai Potongan</th>
                    <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @foreach($discounts as $discount)
                    <tr wire:key="{{ $discount->id }}">
                        <td class="px-4 py-3 text-zinc-900 dark:text-white font-medium">
                            {{ $discount->student?->name ?? 'Siswa Dihapus' }}
                        </td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                            {{ $discount->feeCategory?->name ?? 'Semua Biaya' }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-zinc-900 dark:text-white">{{ $discount->name }}</span>
                            <span class="block text-xs text-zinc-500">{{ $discount->discount_type === 'percentage' ? 'Persentase' : 'Nominal Tetap' }}</span>
                        </td>
                        <td class="px-4 py-3 text-zinc-900 dark:text-white font-medium">
                            @if($discount->discount_type === 'percentage')
                                {{ $discount->amount }}%
                            @else
                                Rp {{ number_format($discount->amount, 0, ',', '.') }}
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-2">
                                <flux:modal.trigger name="add-discount">
                                    <flux:button variant="ghost" icon="pencil-square" size="sm" wire:click="edit({{ $discount->id }})" />
                                </flux:modal.trigger>
                                <flux:button variant="ghost" icon="trash" size="sm" wire:click="delete({{ $discount->id }})" />
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="p-4 border-t">
            {{ $discounts->links() }}
        </div>
    </div>

    <flux:modal name="add-discount" class="md:w-[500px]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $editing ? 'Edit Diskon' : 'Tambah Diskon' }}</flux:heading>
                <flux:subheading>Tentukan potongan atau beasiswa untuk siswa.</flux:subheading>
            </div>

            <flux:select wire:model="student_id" label="Pilih Siswa" :searchable="true">
                <option value="">-- Pilih Siswa --</option>
                @foreach($students as $student)
                    <option value="{{ $student->id }}">{{ $student->name }}</option>
                @endforeach
            </flux:select>
            
            <flux:select wire:model="fee_category_id" label="Kategori Biaya (Opsional)">
                <option value="">Semua Kategori Biaya</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }} ({{ $category->level ? $category->level->name : 'Umum' }})</option>
                @endforeach
            </flux:select>
            
            <flux:input wire:model="name" label="Nama Potongan / Beasiswa" placeholder="Contoh: Beasiswa Prestasi, Anak Guru" />
            
            <div class="grid grid-cols-2 gap-4">
                <flux:select wire:model="discount_type" label="Tipe Diskon">
                    <option value="fixed">Nominal Tetap (Rp)</option>
                    <option value="percentage">Persentase (%)</option>
                </flux:select>
                
                <flux:input wire:model="amount" type="number" label="Nilai Potongan" step="1" />
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Batal</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" wire:click="save">Simpan</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
