<?php

declare(strict_types=1);

use App\Models\FeeCategory;
use App\Models\Level;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('components.admin.layouts.app')] class extends Component {
    use WithPagination;

    public string $name = '';
    public string $code = '';
    public string $description = '';
    public float $default_amount = 0;
    public ?int $level_id = null;

    public ?FeeCategory $editing = null;
    public bool $categoryModal = false;
    public bool $deleteModal = false;
    public ?int $deletingId = null;

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:15|unique:fee_categories,code,' . ($this->editing?->id ?? 'NULL'),
            'description' => 'nullable|string',
            'default_amount' => 'required|numeric|min:0',
            'level_id' => 'nullable|exists:levels,id',
        ];
    }

    public function create(): void
    {
        $this->reset(['name', 'code', 'description', 'default_amount', 'level_id', 'editing']);
        $this->categoryModal = true;
    }

    public function edit(FeeCategory $category): void
    {
        $this->editing = $category;
        $this->name = $category->name;
        $this->code = $category->code;
        $this->description = $category->description ?? '';
        $this->default_amount = (float) $category->default_amount;
        $this->level_id = $category->level_id;
        $this->categoryModal = true;
    }

    public function save(): void
    {
        $this->validate();

        if ($this->editing) {
            $this->editing->update([
                'name' => $this->name,
                'code' => $this->code,
                'description' => $this->description,
                'default_amount' => $this->default_amount,
                'level_id' => $this->level_id ?: null,
            ]);
            session()->flash('success', __('Kategori biaya berhasil diperbarui.'));
        } else {
            FeeCategory::create([
                'name' => $this->name,
                'code' => $this->code,
                'description' => $this->description,
                'default_amount' => $this->default_amount,
                'level_id' => $this->level_id ?: null,
            ]);
            session()->flash('success', __('Kategori biaya berhasil ditambahkan.'));
        }

        $this->categoryModal = false;
        $this->reset(['name', 'code', 'description', 'default_amount', 'level_id', 'editing']);
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->deleteModal = true;
    }

    public function delete(): void
    {
        $category = FeeCategory::find($this->deletingId);
        if (!$category) return;

        if ($category->billings()->exists()) {
            session()->flash('error', __('Kategori tidak bisa dihapus karena sudah digunakan dalam penagihan.'));
            $this->deleteModal = false;
            return;
        }

        $category->delete();
        session()->flash('success', __('Kategori biaya berhasil dihapus.'));
        $this->deleteModal = false;
    }

    public function with(): array
    {
        return [
            'categories' => FeeCategory::with('level')->latest()->paginate(10),
            'levels' => Level::all(),
        ];
    }
}; ?>

<div class="p-6 space-y-6 text-slate-900 dark:text-white pb-24 md:pb-6">
    @if (session('success'))
        <x-ui.alert :title="__('Sukses')" icon="o-check-circle" class="bg-emerald-50 text-emerald-800 border-emerald-100" dismissible>
            {{ session('success') }}
        </x-ui.alert>
    @endif

    @if (session('error'))
        <x-ui.alert :title="__('Gagal')" icon="o-x-circle" class="bg-rose-50 text-rose-800 border-rose-100" dismissible>
            {{ session('error') }}
        </x-ui.alert>
    @endif

    <x-ui.header :title="__('Kategori Biaya')" :subtitle="__('Daftar jenis biaya sekolah (SPP, Gedung, Operasional, dll).')" separator>
        <x-slot:actions>
            <x-ui.button :label="__('Tambah Kategori')" icon="o-plus" class="btn-primary" wire:click="create" />
        </x-slot:actions>
    </x-ui.header>

    <x-ui.card shadow padding="false">
        <x-ui.table 
            :headers="[
                ['key' => 'code', 'label' => __('Kode')],
                ['key' => 'name_label', 'label' => __('Nama Kategori')],
                ['key' => 'level_name', 'label' => __('Jenjang')],
                ['key' => 'amount_label', 'label' => __('Nominal Default'), 'class' => 'text-right'],
                ['key' => 'actions', 'label' => __('Aksi'), 'class' => 'text-right']
            ]" 
            :rows="$categories"
        >
            @scope('cell_code', $category)
                <span class="text-[10px] font-mono font-black text-slate-400 uppercase tracking-tighter">{{ $category->code }}</span>
            @endscope

            @scope('cell_name_label', $category)
                <div class="flex flex-col">
                    <span class="font-bold text-slate-900 dark:text-white">{{ $category->name }}</span>
                    @if($category->description)
                        <span class="text-[10px] text-slate-400 italic line-clamp-1 max-w-[200px]">{{ $category->description }}</span>
                    @endif
                </div>
            @endscope

            @scope('cell_level_name', $category)
                <x-ui.badge 
                    :label="$category->level?->name ?? __('Semua Jenjang')" 
                    class="{{ $category->level_id ? 'bg-slate-100 text-slate-600' : 'bg-amber-100 text-amber-700 font-bold' }} border-none text-[8px] uppercase tracking-widest px-2 py-0.5" 
                />
            @endscope

            @scope('cell_amount_label', $category)
                <span class="font-mono text-sm font-black text-slate-700 dark:text-slate-300">
                    Rp {{ number_format($category->default_amount, 0, ',', '.') }}
                </span>
            @endscope

            @scope('cell_actions', $category)
                <div class="flex justify-end gap-2">
                    <x-ui.button icon="o-pencil" class="btn-ghost btn-xs text-slate-400 hover:text-primary" wire:click="edit({{ $category->id }})" />
                    <x-ui.button icon="o-trash" class="btn-ghost btn-xs text-slate-400 hover:text-rose-600" wire:click="confirmDelete({{ $category->id }})" />
                </div>
            @endscope
        </x-ui.table>

        @if($categories->isEmpty())
            <div class="py-12 text-center text-slate-400 italic text-sm">
                {{ __('Belum ada kategori biaya yang dibuat.') }}
            </div>
        @endif

        <div class="p-4 border-t border-slate-100 dark:border-slate-800">
            {{ $categories->links() }}
        </div>
    </x-ui.card>

    <x-ui.modal wire:model="categoryModal">
        <x-ui.header :title="$editing ? __('Edit Kategori') : __('Tambah Kategori Baru')" :subtitle="__('Konfigurasi detail biaya sekolah.')" separator />

        <div class="space-y-6">
            <x-ui.input wire:model="name" :label="__('Nama Kategori')" required :placeholder="__('Contoh: SPP Bulanan')" />
            <x-ui.input wire:model="code" :label="__('Kode Unik')" required :placeholder="__('Contoh: SPP-BULAN')" />
            <x-ui.input wire:model="default_amount" type="number" :label="__('Nominal Default (Rp)')" icon="o-banknotes" required />
            <x-ui.select wire:model="level_id" :label="__('Khusus Jenjang (Opsional)')" :placeholder="__('Tersedia untuk semua jenjang')" :options="$levels" />
            <x-ui.textarea wire:model="description" :label="__('Deskripsi Keterangan')" rows="2" />
        </div>

        <div class="flex justify-end gap-3 mt-8 pt-6 border-t border-slate-100 dark:border-slate-800">
            <x-ui.button :label="__('Batal')" wire:click="$set('categoryModal', false)" />
            <x-ui.button :label="__('Simpan Perubahan')" class="btn-primary" wire:click="save" spinner="save" />
        </div>
    </x-ui.modal>

    <x-ui.modal wire:model="deleteModal" class="backdrop-blur">
        <div class="text-center p-4">
            <div class="w-16 h-16 bg-rose-50 text-rose-600 rounded-full flex items-center justify-center mx-auto mb-4">
                <x-ui.icon name="o-exclamation-triangle" class="size-8" />
            </div>
            <h3 class="text-lg font-black text-slate-900 dark:text-white mb-2">{{ __('Konfirmasi Hapus') }}</h3>
            <p class="text-xs text-slate-500 font-medium leading-relaxed">
                {{ __('Apakah Anda yakin ingin menghapus kategori biaya ini? Tindakan ini tidak dapat dibatalkan jika kategori belum digunakan.') }}
            </p>
        </div>

        <div class="flex justify-center gap-3 mt-6">
            <x-ui.button :label="__('Batal')" wire:click="$set('deleteModal', false)" />
            <x-ui.button :label="__('Ya, Hapus')" class="bg-rose-600 hover:bg-rose-700 text-white border-none" wire:click="delete" spinner="delete" />
        </div>
    </x-ui.modal>
</div>
