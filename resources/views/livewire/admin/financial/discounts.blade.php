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
            session()->flash('success', __('Potongan/Beasiswa berhasil diperbarui.'));
        } else {
            StudentFeeDiscount::create([
                'student_id' => $this->student_id,
                'fee_category_id' => $this->fee_category_id ?: null,
                'name' => $this->name,
                'discount_type' => $this->discount_type,
                'amount' => $this->amount,
            ]);
            session()->flash('success', __('Potongan/Beasiswa berhasil ditambahkan.'));
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
        session()->flash('success', __('Potongan/Beasiswa berhasil dihapus.'));
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

<div class="p-6 space-y-6 text-slate-900 dark:text-white pb-24 md:pb-6">
    @if (session('success'))
        <x-ui.alert :title="__('Sukses')" icon="o-check-circle" class="bg-emerald-50 text-emerald-800 border-emerald-100" dismissible>
            {{ session('success') }}
        </x-ui.alert>
    @endif

    <x-ui.header :title="__('Potongan & Beasiswa')" :subtitle="__('Kelola variasi biaya, diskon, dan keringanan pembayaran untuk siswa.')" separator>
        <x-slot:actions>
            <x-ui.button :label="__('Tambah Diskon')" icon="o-plus" class="btn-primary" wire:click="create" />
        </x-slot:actions>
    </x-ui.header>

    <x-ui.card shadow padding="false">
        <x-ui.table 
            :headers="[
                ['key' => 'student_name', 'label' => __('Nama Siswa')],
                ['key' => 'category_name', 'label' => __('Berlaku Untuk')],
                ['key' => 'discount_name', 'label' => __('Detail Potongan')],
                ['key' => 'value_label', 'label' => __('Nilai Potongan'), 'class' => 'text-right'],
                ['key' => 'actions', 'label' => __('Aksi'), 'class' => 'text-right']
            ]" 
            :rows="$discounts"
        >
            @scope('cell_student_name', $discount)
                <div class="flex flex-col">
                    <span class="font-bold text-slate-900 dark:text-white">{{ $discount->student?->name ?? __('Siswa Dihapus') }}</span>
                    <span class="text-[9px] text-slate-400 font-mono tracking-tighter">{{ $discount->student?->email }}</span>
                </div>
            @endscope

            @scope('cell_category_name', $discount)
                <x-ui.badge 
                    :label="$discount->feeCategory?->name ?? __('Semua Kategori')" 
                    class="{{ $discount->fee_category_id ? 'bg-slate-100 text-slate-600' : 'bg-amber-100 text-amber-700' }} border-none text-[8px] font-black uppercase tracking-widest px-2 py-0.5" 
                />
            @endscope

            @scope('cell_discount_name', $discount)
                <div class="flex flex-col">
                    <span class="font-medium text-slate-700 dark:text-slate-300">{{ $discount->name }}</span>
                    <span class="text-[9px] text-slate-400 uppercase tracking-widest">{{ $discount->discount_type === 'percentage' ? __('Persentase') : __('Nominal Tetap') }}</span>
                </div>
            @endscope

            @scope('cell_value_label', $discount)
                <span class="font-mono text-sm font-black text-emerald-600">
                    {{ $discount->discount_type === 'percentage' ? $discount->amount.'%' : 'Rp '.number_format($discount->amount, 0, ',', '.') }}
                </span>
            @endscope

            @scope('cell_actions', $discount)
                <div class="flex justify-end gap-2 text-right">
                    <x-ui.button icon="o-pencil" class="btn-ghost btn-xs text-slate-400 hover:text-primary" wire:click="edit({{ $discount->id }})" />
                    <x-ui.button icon="o-trash" class="btn-ghost btn-xs text-slate-400 hover:text-rose-600" wire:click="delete({{ $discount->id }})" wire:confirm="{{ __('Yakin ingin menghapus data ini?') }}" />
                </div>
            @endscope
        </x-ui.table>

        @if($discounts->isEmpty())
            <div class="py-12 text-center text-slate-400 italic text-sm">
                {{ __('Belum ada data potongan atau beasiswa.') }}
            </div>
        @endif

        <div class="p-4 border-t border-slate-100 dark:border-slate-800">
            {{ $discounts->links() }}
        </div>
    </x-ui.card>

    <x-ui.modal wire:model="discountModal">
        <x-ui.header :title="$editing ? __('Edit Potongan') : __('Tambah Potongan Baru')" :subtitle="__('Konfigurasi detail beasiswa/keringanan siswa.')" separator />

        <div class="space-y-6">
            <x-ui.select wire:model="student_id" :label="__('Target Siswa')" :placeholder="__('Pilih Siswa')" :options="$students" required />
            <x-ui.select wire:model="fee_category_id" :label="__('Khusus Kategori (Opsional)')" :placeholder="__('Berlaku untuk semua jenis biaya')" :options="$categories" />
            <x-ui.input wire:model="name" :label="__('Nama Potongan/Beasiswa')" required :placeholder="__('Contoh: Beasiswa Prestasi')" />
            
            <div class="grid grid-cols-2 gap-4">
                <x-ui.select 
                    wire:model.live="discount_type" 
                    :label="__('Jenis Potongan')" 
                    :options="[
                        ['id' => 'fixed', 'name' => __('Nominal Tetap (Rp)')],
                        ['id' => 'percentage', 'name' => __('Persentase (%)')]
                    ]" 
                    required 
                />
                <x-ui.input wire:model="amount" type="number" :label="__('Nilai Potongan')" icon="o-currency-dollar" required />
            </div>

            <x-ui.alert icon="o-information-circle" class="bg-blue-50 text-blue-700 border-blue-100 font-medium text-[10px]">
                {{ __('Potongan akan otomatis memotong nilai tagihan setiap kali tagihan di-generate untuk siswa tersebut.') }}
            </x-ui.alert>
        </div>

        <div class="flex justify-end gap-3 mt-8 pt-6 border-t border-slate-100 dark:border-slate-800">
            <x-ui.button :label="__('Batal')" wire:click="$set('discountModal', false)" />
            <x-ui.button :label="__('Simpan Data')" class="btn-primary" wire:click="save" spinner="save" />
        </div>
    </x-ui.modal>
</div>
