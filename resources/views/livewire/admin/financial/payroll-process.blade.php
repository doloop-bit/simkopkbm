<?php

declare(strict_types=1);

use App\Models\AcademicYear;
use App\Models\Payroll;
use App\Models\SalaryTemplate;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public string $selectedMonth = '';
    public ?int $selectedAcademicYearId = null;
    public string $search = '';

    public bool $detailModal = false;
    public ?Payroll $editingPayroll = null;
    public int $detail_base_salary = 0;
    public array $detail_allowances = [];
    public array $detail_deductions = [];
    public string $detail_notes = '';

    public bool $confirmFinalizeAll = false;

    public function mount(): void
    {
        $this->selectedMonth = now()->format('Y-m');
        $activeYear = AcademicYear::where('is_active', true)->first();
        $this->selectedAcademicYearId = $activeYear?->id;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedMonth(): void
    {
        $this->resetPage();
    }

    public function generatePayrolls(): void
    {
        if (!$this->selectedAcademicYearId || !$this->selectedMonth) {
            session()->flash('error', __('Pilih tahun ajaran dan bulan terlebih dahulu.'));
            return;
        }

        $templates = SalaryTemplate::with('components')->get();

        if ($templates->isEmpty()) {
            session()->flash('error', __('Belum ada template gaji yang dikonfigurasi.'));
            return;
        }

        $generated = 0;
        $skipped = 0;

        DB::transaction(function () use ($templates, &$generated, &$skipped) {
            foreach ($templates as $template) {
                $exists = Payroll::where('user_id', $template->user_id)
                    ->where('month', $this->selectedMonth)
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                $components = $template->components->map(fn($c) => [
                    'type' => $c->type,
                    'name' => $c->name,
                    'amount' => (int) $c->amount,
                    'description' => $c->description,
                ])->toArray();

                $totalAllowances = collect($components)->where('type', 'allowance')->sum('amount');
                $totalDeductions = collect($components)->where('type', 'deduction')->sum('amount');

                Payroll::create([
                    'user_id' => $template->user_id,
                    'academic_year_id' => $this->selectedAcademicYearId,
                    'month' => $this->selectedMonth,
                    'base_salary' => (int) $template->base_salary,
                    'components' => $components,
                    'total_allowances' => $totalAllowances,
                    'total_deductions' => $totalDeductions,
                    'net_salary' => (int) $template->base_salary + $totalAllowances - $totalDeductions,
                    'status' => 'draft',
                ]);

                $generated++;
            }
        });

        if ($generated > 0) {
            session()->flash('success', __(':count slip gaji berhasil di-generate.', ['count' => $generated]) .
                ($skipped > 0 ? ' ' . __(':skipped sudah ada sebelumnya.', ['skipped' => $skipped]) : ''));
        } else {
            session()->flash('info', __('Semua slip gaji untuk bulan ini sudah ada.'));
        }
    }

    public function showDetail(int $payrollId): void
    {
        $payroll = Payroll::findOrFail($payrollId);
        $this->editingPayroll = $payroll;
        $this->detail_base_salary = (int) $payroll->base_salary;
        $this->detail_notes = $payroll->notes ?? '';

        $grouped = $payroll->getGroupedComponents();
        $this->detail_allowances = array_map(fn($c) => [
            'type' => 'allowance',
            'name' => $c['name'],
            'amount' => (int) $c['amount'],
            'description' => $c['description'] ?? '',
        ], $grouped['allowances']);

        $this->detail_deductions = array_map(fn($c) => [
            'type' => 'deduction',
            'name' => $c['name'],
            'amount' => (int) $c['amount'],
            'description' => $c['description'] ?? '',
        ], $grouped['deductions']);

        $this->detailModal = true;
    }

    public function addDetailAllowance(): void
    {
        $this->detail_allowances[] = ['type' => 'allowance', 'name' => '', 'amount' => 0, 'description' => ''];
    }

    public function removeDetailAllowance(int $index): void
    {
        unset($this->detail_allowances[$index]);
        $this->detail_allowances = array_values($this->detail_allowances);
    }

    public function addDetailDeduction(): void
    {
        $this->detail_deductions[] = ['type' => 'deduction', 'name' => '', 'amount' => 0, 'description' => ''];
    }

    public function removeDetailDeduction(int $index): void
    {
        unset($this->detail_deductions[$index]);
        $this->detail_deductions = array_values($this->detail_deductions);
    }

    public function saveDetail(): void
    {
        if (!$this->editingPayroll || $this->editingPayroll->isFinalized()) {
            return;
        }

        $this->validate([
            'detail_base_salary' => 'required|integer|min:0',
            'detail_allowances' => 'array',
            'detail_allowances.*.name' => 'required|string|max:255',
            'detail_allowances.*.amount' => 'required|integer|min:0',
            'detail_deductions' => 'array',
            'detail_deductions.*.name' => 'required|string|max:255',
            'detail_deductions.*.amount' => 'required|integer|min:0',
        ], [
            'detail_allowances.*.name.required' => 'Nama tunjangan wajib diisi.',
            'detail_allowances.*.amount.required' => 'Nominal tunjangan wajib diisi.',
            'detail_deductions.*.name.required' => 'Nama potongan wajib diisi.',
            'detail_deductions.*.amount.required' => 'Nominal potongan wajib diisi.',
        ]);

        $components = array_merge(
            array_map(fn($a) => [
                'type' => 'allowance',
                'name' => $a['name'],
                'amount' => (int) $a['amount'],
                'description' => $a['description'] ?? null,
            ], $this->detail_allowances),
            array_map(fn($d) => [
                'type' => 'deduction',
                'name' => $d['name'],
                'amount' => (int) $d['amount'],
                'description' => $d['description'] ?? null,
            ], $this->detail_deductions)
        );

        $totalAllowances = collect($this->detail_allowances)->sum('amount');
        $totalDeductions = collect($this->detail_deductions)->sum('amount');

        $this->editingPayroll->update([
            'base_salary' => $this->detail_base_salary,
            'components' => $components,
            'total_allowances' => $totalAllowances,
            'total_deductions' => $totalDeductions,
            'net_salary' => $this->detail_base_salary + $totalAllowances - $totalDeductions,
            'notes' => $this->detail_notes ?: null,
        ]);

        $this->detailModal = false;
        session()->flash('success', __('Slip gaji berhasil diperbarui.'));
    }

    public function finalizePayroll(int $payrollId): void
    {
        $payroll = Payroll::findOrFail($payrollId);

        if ($payroll->isFinalized()) {
            return;
        }

        $payroll->update(['status' => 'finalized']);
        session()->flash('success', __('Slip gaji :name berhasil difinalisasi.', ['name' => $payroll->user->name]));
    }

    public function finalizeAll(): void
    {
        $count = Payroll::where('month', $this->selectedMonth)
            ->where('status', 'draft')
            ->update(['status' => 'finalized']);

        $this->confirmFinalizeAll = false;
        session()->flash('success', __(':count slip gaji berhasil difinalisasi.', ['count' => $count]));
    }

    public function getDetailNetProperty(): int
    {
        $totalAllowances = collect($this->detail_allowances)->sum('amount');
        $totalDeductions = collect($this->detail_deductions)->sum('amount');

        return $this->detail_base_salary + $totalAllowances - $totalDeductions;
    }

    public function with(): array
    {
        $payrolls = Payroll::query()
            ->where('month', $this->selectedMonth)
            ->when($this->selectedAcademicYearId, fn($q) => $q->where('academic_year_id', $this->selectedAcademicYearId))
            ->when($this->search, fn($q) => $q->whereHas('user', fn($uq) => $uq->where('name', 'like', '%' . $this->search . '%')))
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $summary = [
            'total_base' => $payrolls->sum('base_salary'),
            'total_allowances' => $payrolls->sum('total_allowances'),
            'total_deductions' => $payrolls->sum('total_deductions'),
            'total_net' => $payrolls->sum('net_salary'),
            'count_draft' => $payrolls->where('status', 'draft')->count(),
            'count_finalized' => $payrolls->where('status', 'finalized')->count(),
        ];

        $isViewOnly = auth()->user()->isYayasan();

        return [
            'payrolls' => $payrolls,
            'academicYears' => AcademicYear::orderBy('start_date', 'desc')->get(),
            'summary' => $summary,
            'isViewOnly' => $isViewOnly,
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

    @if (session('info'))
    <x-ui.alert :title="__('Info')" icon="o-information-circle" class="bg-blue-50 text-blue-800 border-blue-100" dismissible>
        {{ session('info') }}
    </x-ui.alert>
    @endif

    <x-ui.header :title="__('Proses Penggajian')" :subtitle="__('Generate dan kelola slip gaji bulanan PTK.')" separator>
        @if(!$isViewOnly)
        <x-slot:actions>
            <x-ui.button
                :label="__('Generate Slip Gaji')"
                icon="o-bolt"
                class="btn-primary"
                wire:click="generatePayrolls"
                spinner="generatePayrolls" />
        </x-slot:actions>
        @endif
    </x-ui.header>

    {{-- Filters --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <x-ui.input wire:model.live="selectedMonth" type="month" :label="__('Bulan')" />
        <x-ui.select
            wire:model.live="selectedAcademicYearId"
            :label="__('Tahun Ajaran')"
            :options="$academicYears"
            :placeholder="__('Pilih tahun ajaran')" />
        <x-ui.input wire:model.live.debounce.300ms="search" :label="__('Cari PTK')" :placeholder="__('Nama PTK...')" icon="o-magnifying-glass" />
    </div>

    {{-- Summary Cards --}}
    @if($payrolls->isNotEmpty())
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
            <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('Gaji Pokok') }}</div>
            <div class="text-lg font-black font-mono mt-1">Rp {{ number_format($summary['total_base'], 0, ',', '.') }}</div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-emerald-200 dark:border-emerald-800">
            <div class="text-xs font-semibold text-emerald-600 uppercase tracking-wider">{{ __('Total Tunjangan') }}</div>
            <div class="text-lg font-black font-mono text-emerald-600 mt-1">+Rp {{ number_format($summary['total_allowances'], 0, ',', '.') }}</div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-rose-200 dark:border-rose-800">
            <div class="text-xs font-semibold text-rose-600 uppercase tracking-wider">{{ __('Total Potongan') }}</div>
            <div class="text-lg font-black font-mono text-rose-600 mt-1">-Rp {{ number_format($summary['total_deductions'], 0, ',', '.') }}</div>
        </div>
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 dark:from-slate-700 dark:to-slate-800 rounded-xl p-4 border border-slate-700">
            <div class="text-xs font-semibold text-slate-300 uppercase tracking-wider">{{ __('Grand Total') }}</div>
            <div class="text-lg font-black font-mono text-white mt-1">Rp {{ number_format($summary['total_net'], 0, ',', '.') }}</div>
        </div>
    </div>
    @endif

    {{-- Table --}}
    <x-ui.card shadow padding="false">
        <x-ui.table
            :headers="[
                ['key' => 'user_name', 'label' => __('Nama PTK')],
                ['key' => 'base_salary', 'label' => __('Gaji Pokok'), 'class' => 'text-right'],
                ['key' => 'total_allowances', 'label' => __('Tunjangan'), 'class' => 'text-right'],
                ['key' => 'total_deductions', 'label' => __('Potongan'), 'class' => 'text-right'],
                ['key' => 'net_salary', 'label' => __('Gaji Bersih'), 'class' => 'text-right'],
                ['key' => 'status', 'label' => __('Status')],
                ['key' => 'actions', 'label' => __('Aksi'), 'class' => 'text-right'],
            ]"
            :rows="$payrolls">
            @scope('cell_user_name', $payroll)
            <div class="flex flex-col">
                <span class="font-semibold">{{ $payroll->user->name }}</span>
                <span class="text-[10px] text-slate-400 uppercase">{{ $payroll->user->role }}</span>
            </div>
            @endscope

            @scope('cell_base_salary', $payroll)
            <span class="font-mono text-sm">Rp {{ number_format($payroll->base_salary, 0, ',', '.') }}</span>
            @endscope

            @scope('cell_total_allowances', $payroll)
            <span class="font-mono text-sm text-emerald-600 dark:text-emerald-400">
                +Rp {{ number_format($payroll->total_allowances, 0, ',', '.') }}
            </span>
            @endscope

            @scope('cell_total_deductions', $payroll)
            <span class="font-mono text-sm text-rose-600 dark:text-rose-400">
                -Rp {{ number_format($payroll->total_deductions, 0, ',', '.') }}
            </span>
            @endscope

            @scope('cell_net_salary', $payroll)
            <span class="font-mono text-sm font-bold">Rp {{ number_format($payroll->net_salary, 0, ',', '.') }}</span>
            @endscope

            @scope('cell_status', $payroll)
            @if($payroll->status === 'finalized')
            <x-ui.badge label="Final" class="bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300 border-none text-[10px] uppercase tracking-wider px-2 py-0.5" />
            @else
            <x-ui.badge label="Draft" class="bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-300 border-none text-[10px] uppercase tracking-wider px-2 py-0.5" />
            @endif
            @endscope

            @scope('cell_actions', $payroll)
            <div class="flex justify-end gap-1">
                    <a
                        href="{{ route('financial.payroll.slip', $payroll) }}"
                        target="_blank"
                        class="p-1.5 rounded-lg text-emerald-600 hover:bg-emerald-50 transition-colors"
                        title="{{ __('Cetak Slip') }}"
                    >
                        <x-ui.icon name="o-printer" class="w-4 h-4" />
                    </a>
                <x-ui.button
                    icon="o-eye"
                    class="btn-ghost btn-xs text-slate-400 hover:text-primary"
                    wire:click="showDetail({{ $payroll->id }})"
                    title="{{ __('Detail') }}" />
                @if(!auth()->user()->isYayasan() && $payroll->status === 'draft')
                <x-ui.button
                    icon="o-check"
                    class="btn-ghost btn-xs text-slate-400 hover:text-emerald-600"
                    wire:click="finalizePayroll({{ $payroll->id }})"
                    wire:confirm="{{ __('Finalisasi slip gaji ini? Data tidak bisa diubah setelah difinalisasi.') }}"
                    title="{{ __('Finalisasi') }}" />
                @endif
            </div>
            @endscope
        </x-ui.table>

        @if($payrolls->isEmpty())
        <div class="py-12 text-center text-slate-400 italic text-sm">
            {{ __('Belum ada slip gaji untuk bulan ini. Klik "Generate Slip Gaji" untuk membuat.') }}
        </div>
        @endif

        <div class="p-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <div>
                @if(!$isViewOnly && $payrolls->isNotEmpty() && $summary['count_draft'] > 0)
                <x-ui.button
                    :label="__('Finalisasi Semua (:count)', ['count' => $summary['count_draft']])"
                    icon="o-check-circle"
                    class="btn-xs bg-emerald-600 hover:bg-emerald-700 text-white border-none"
                    wire:click="$set('confirmFinalizeAll', true)" />
                @endif
            </div>
            {{ $payrolls->links() }}
        </div>
    </x-ui.card>

    {{-- Detail/Edit Modal --}}
    <x-ui.modal wire:model="detailModal" class="max-w-3xl">
        @if($editingPayroll)
        <x-ui.header
            :title="__('Slip Gaji: ') . $editingPayroll->user->name"
            :subtitle="__('Bulan: ') . $editingPayroll->month"
            separator />

        @php $isLocked = $editingPayroll->isFinalized() || $isViewOnly; @endphp

        @if($isLocked)
        <x-ui.alert icon="o-lock-closed" class="bg-amber-50 text-amber-800 border-amber-100 mt-4" dismissible>
            {{ $editingPayroll->isFinalized() ? __('Slip gaji ini sudah difinalisasi dan tidak bisa diubah.') : __('Anda hanya memiliki akses lihat.') }}
        </x-ui.alert>
        @endif

        <div class="space-y-6 mt-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-ui.money wire:model="detail_base_salary" :label="__('Gaji Pokok (Rp)')" :disabled="$isLocked" />
                <x-ui.textarea wire:model="detail_notes" :label="__('Catatan')" rows="2" :disabled="$isLocked" />
            </div>

            {{-- Tunjangan --}}
            <div class="border border-emerald-200 dark:border-emerald-800 rounded-xl p-4 space-y-3 bg-emerald-50/50 dark:bg-emerald-950/20">
                <div class="flex items-center justify-between">
                    <h3 class="font-bold text-emerald-700 dark:text-emerald-400 flex items-center gap-2">
                        <x-ui.icon name="o-plus-circle" class="size-5" />
                        {{ __('Tunjangan') }}
                    </h3>
                    @if(!$isLocked)
                    <x-ui.button
                        :label="__('Tambah')"
                        icon="o-plus"
                        class="btn-xs bg-emerald-600 hover:bg-emerald-700 text-white border-none"
                        wire:click="addDetailAllowance" />
                    @endif
                </div>

                @forelse($detail_allowances as $index => $allowance)
                <div class="flex items-start gap-3 p-3 bg-white dark:bg-slate-800 rounded-lg border border-emerald-100 dark:border-emerald-900">
                    <div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-3">
                        <x-ui.input wire:model="detail_allowances.{{ $index }}.name" :placeholder="__('Nama tunjangan')" sm :disabled="$isLocked" />
                        <x-ui.money wire:model="detail_allowances.{{ $index }}.amount" :placeholder="__('Nominal')" sm :disabled="$isLocked" />
                    </div>
                    @if(!$isLocked)
                    <button wire:click="removeDetailAllowance({{ $index }})" class="mt-1 p-1.5 rounded-lg text-rose-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950 transition-colors">
                        <x-ui.icon name="o-x-mark" class="size-4" />
                    </button>
                    @endif
                </div>
                @empty
                <p class="text-xs text-slate-400 italic text-center py-2">{{ __('Tidak ada tunjangan.') }}</p>
                @endforelse
            </div>

            {{-- Potongan --}}
            <div class="border border-rose-200 dark:border-rose-800 rounded-xl p-4 space-y-3 bg-rose-50/50 dark:bg-rose-950/20">
                <div class="flex items-center justify-between">
                    <h3 class="font-bold text-rose-700 dark:text-rose-400 flex items-center gap-2">
                        <x-ui.icon name="o-minus-circle" class="size-5" />
                        {{ __('Potongan') }}
                    </h3>
                    @if(!$isLocked)
                    <x-ui.button
                        :label="__('Tambah')"
                        icon="o-plus"
                        class="btn-xs bg-rose-600 hover:bg-rose-700 text-white border-none"
                        wire:click="addDetailDeduction" />
                    @endif
                </div>

                @forelse($detail_deductions as $index => $deduction)
                <div class="flex items-start gap-3 p-3 bg-white dark:bg-slate-800 rounded-lg border border-rose-100 dark:border-rose-900">
                    <div class="flex-1 grid grid-cols-1 md:grid-cols-3 gap-3">
                        <x-ui.input wire:model="detail_deductions.{{ $index }}.name" :placeholder="__('Nama potongan')" sm :disabled="$isLocked" />
                        <x-ui.money wire:model="detail_deductions.{{ $index }}.amount" :placeholder="__('Nominal')" sm :disabled="$isLocked" />
                        <x-ui.input wire:model="detail_deductions.{{ $index }}.description" :placeholder="__('Keterangan')" sm :disabled="$isLocked" />
                    </div>
                    @if(!$isLocked)
                    <button wire:click="removeDetailDeduction({{ $index }})" class="mt-1 p-1.5 rounded-lg text-rose-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950 transition-colors">
                        <x-ui.icon name="o-x-mark" class="size-4" />
                    </button>
                    @endif
                </div>
                @empty
                <p class="text-xs text-slate-400 italic text-center py-2">{{ __('Tidak ada potongan.') }}</p>
                @endforelse
            </div>

            {{-- Net Preview --}}
            <div class="bg-slate-50 dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2">
                    <span class="text-sm font-semibold text-slate-500">{{ __('Gaji Bersih') }}</span>
                    <span class="text-xl font-black text-slate-900 dark:text-white font-mono">
                        Rp {{ number_format($this->detailNet, 0, ',', '.') }}
                    </span>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3 mt-8 pt-6 border-t border-slate-100 dark:border-slate-800">
            <a
                href="{{ route('financial.payroll.slip', $editingPayroll ?? 0) }}"
                target="_blank"
                class="flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition-colors"
            >
                <x-ui.icon name="o-printer" class="w-4 h-4" />
                {{ __('Cetak Slip PDF') }}
            </a>
            <div class="flex-1"></div>
            <x-ui.button :label="__('Tutup')" wire:click="$set('detailModal', false)" />
            @if(!$isLocked)
            <x-ui.button :label="__('Simpan Perubahan')" class="btn-primary" wire:click="saveDetail" spinner="saveDetail" />
            @endif
        </div>
        @endif
    </x-ui.modal>

    {{-- Finalize All Confirmation --}}
    <x-ui.modal wire:model="confirmFinalizeAll" class="backdrop-blur">
        <div class="text-center p-4">
            <div class="w-16 h-16 bg-emerald-50 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-4">
                <x-ui.icon name="o-check-circle" class="size-8" />
            </div>
            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">{{ __('Finalisasi Semua Slip Gaji') }}</h3>
            <p class="text-xs text-slate-500 font-medium leading-relaxed">
                {{ __('Semua slip gaji draft untuk bulan ini akan difinalisasi. Data yang sudah difinalisasi tidak bisa diubah lagi. Lanjutkan?') }}
            </p>
        </div>

        <div class="flex justify-center gap-3 mt-6">
            <x-ui.button :label="__('Batal')" wire:click="$set('confirmFinalizeAll', false)" />
            <x-ui.button :label="__('Ya, Finalisasi Semua')" class="bg-emerald-600 hover:bg-emerald-700 text-white border-none" wire:click="finalizeAll" spinner="finalizeAll" />
        </div>
    </x-ui.modal>
</div>