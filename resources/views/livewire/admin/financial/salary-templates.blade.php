<?php

declare(strict_types=1);

use App\Models\SalaryTemplate;
use App\Models\SalaryTemplateComponent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public string $search = '';

    public bool $templateModal = false;
    public ?int $editingUserId = null;
    public string $editingUserName = '';
    public int $base_salary = 0;
    public string $effective_date = '';
    public string $notes = '';

    /** @var array<int, array{type: string, name: string, amount: int, description: string}> */
    public array $allowances = [];

    /** @var array<int, array{type: string, name: string, amount: int, description: string}> */
    public array $deductions = [];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function editTemplate(int $userId): void
    {
        $user = User::findOrFail($userId);
        $this->editingUserId = $userId;
        $this->editingUserName = $user->name;

        $template = SalaryTemplate::where('user_id', $userId)->first();

        if ($template) {
            $this->base_salary = (int) $template->base_salary;
            $this->effective_date = $template->effective_date->format('Y-m-d');
            $this->notes = $template->notes ?? '';

            $this->allowances = $template->allowances()
                ->get()
                ->map(fn ($c) => [
                    'type' => 'allowance',
                    'name' => $c->name,
                    'amount' => (int) $c->amount,
                    'description' => $c->description ?? '',
                ])
                ->values()
                ->toArray();

            $this->deductions = $template->deductions()
                ->get()
                ->map(fn ($c) => [
                    'type' => 'deduction',
                    'name' => $c->name,
                    'amount' => (int) $c->amount,
                    'description' => $c->description ?? '',
                ])
                ->values()
                ->toArray();
        } else {
            $this->base_salary = 0;
            $this->effective_date = now()->format('Y-m-d');
            $this->notes = '';
            $this->allowances = [];
            $this->deductions = [];
        }

        $this->templateModal = true;
    }

    public function addAllowance(): void
    {
        $this->allowances[] = ['type' => 'allowance', 'name' => '', 'amount' => 0, 'description' => ''];
    }

    public function removeAllowance(int $index): void
    {
        unset($this->allowances[$index]);
        $this->allowances = array_values($this->allowances);
    }

    public function addDeduction(): void
    {
        $this->deductions[] = ['type' => 'deduction', 'name' => '', 'amount' => 0, 'description' => ''];
    }

    public function removeDeduction(int $index): void
    {
        unset($this->deductions[$index]);
        $this->deductions = array_values($this->deductions);
    }

    public function save(): void
    {
        $this->validate([
            'editingUserId' => 'required|exists:users,id',
            'base_salary' => 'required|integer|min:0',
            'effective_date' => 'required|date',
            'notes' => 'nullable|string',
            'allowances' => 'array',
            'allowances.*.name' => 'required|string|max:255',
            'allowances.*.amount' => 'required|integer|min:0',
            'deductions' => 'array',
            'deductions.*.name' => 'required|string|max:255',
            'deductions.*.amount' => 'required|integer|min:0',
        ], [
            'allowances.*.name.required' => 'Nama tunjangan wajib diisi.',
            'allowances.*.amount.required' => 'Nominal tunjangan wajib diisi.',
            'deductions.*.name.required' => 'Nama potongan wajib diisi.',
            'deductions.*.amount.required' => 'Nominal potongan wajib diisi.',
        ]);

        DB::transaction(function () {
            $template = SalaryTemplate::updateOrCreate(
                ['user_id' => $this->editingUserId],
                [
                    'base_salary' => $this->base_salary,
                    'effective_date' => $this->effective_date,
                    'notes' => $this->notes ?: null,
                ]
            );

            $template->components()->delete();

            foreach ($this->allowances as $item) {
                $template->components()->create([
                    'type' => 'allowance',
                    'name' => $item['name'],
                    'amount' => $item['amount'],
                    'description' => $item['description'] ?: null,
                ]);
            }

            foreach ($this->deductions as $item) {
                $template->components()->create([
                    'type' => 'deduction',
                    'name' => $item['name'],
                    'amount' => $item['amount'],
                    'description' => $item['description'] ?: null,
                ]);
            }
        });

        $this->templateModal = false;
        session()->flash('success', __('Template gaji berhasil disimpan.'));
    }

    public function getComputedNetProperty(): int
    {
        $totalAllowances = collect($this->allowances)->sum('amount');
        $totalDeductions = collect($this->deductions)->sum('amount');

        return $this->base_salary + $totalAllowances - $totalDeductions;
    }

    public function with(): array
    {
        $ptkUsers = User::query()
            ->where('role', '!=', 'siswa')
            ->where('role', '!=', 'admin')
            ->where('is_active', true)
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%');
            })
            ->with('salaryTemplate.components')
            ->orderBy('name')
            ->paginate(15);

        $isViewOnly = auth()->user()->isYayasan();

        return [
            'ptkUsers' => $ptkUsers,
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

    <x-ui.header :title="__('Template Gaji')" :subtitle="__('Atur komposisi gaji pokok, tunjangan, dan potongan untuk setiap PTK.')" separator />

    {{-- Search --}}
    <div class="flex items-center gap-4">
        <x-ui.input wire:model.live.debounce.300ms="search" :placeholder="__('Cari nama PTK...')" icon="o-magnifying-glass" class="max-w-sm" />
    </div>

    <x-ui.card shadow padding="false">
        <x-ui.table
            :headers="[
                ['key' => 'name', 'label' => __('Nama PTK')],
                ['key' => 'role', 'label' => __('Jabatan')],
                ['key' => 'base_salary', 'label' => __('Gaji Pokok'), 'class' => 'text-right'],
                ['key' => 'total_tunjangan', 'label' => __('Tunjangan'), 'class' => 'text-right'],
                ['key' => 'total_potongan', 'label' => __('Potongan'), 'class' => 'text-right'],
                ['key' => 'net_salary', 'label' => __('Gaji Bersih'), 'class' => 'text-right'],
                ['key' => 'actions', 'label' => __('Aksi'), 'class' => 'text-right'],
            ]"
            :rows="$ptkUsers"
        >
            @scope('cell_name', $user)
                <span class="font-semibold">{{ $user->name }}</span>
            @endscope

            @scope('cell_role', $user)
                <x-ui.badge
                    :label="ucfirst($user->role)"
                    class="bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300 border-none text-[10px] uppercase tracking-wider px-2 py-0.5"
                />
            @endscope

            @scope('cell_base_salary', $user)
                @if($user->salaryTemplate)
                    <span class="font-mono text-sm font-bold">
                        Rp {{ number_format($user->salaryTemplate->base_salary, 0, ',', '.') }}
                    </span>
                @else
                    <span class="text-xs text-slate-400 italic">{{ __('Belum diatur') }}</span>
                @endif
            @endscope

            @scope('cell_total_tunjangan', $user)
                @if($user->salaryTemplate)
                    <span class="font-mono text-sm text-emerald-600 dark:text-emerald-400">
                        +Rp {{ number_format($user->salaryTemplate->allowances->sum('amount'), 0, ',', '.') }}
                    </span>
                @else
                    <span class="text-xs text-slate-400">-</span>
                @endif
            @endscope

            @scope('cell_total_potongan', $user)
                @if($user->salaryTemplate)
                    <span class="font-mono text-sm text-rose-600 dark:text-rose-400">
                        -Rp {{ number_format($user->salaryTemplate->deductions->sum('amount'), 0, ',', '.') }}
                    </span>
                @else
                    <span class="text-xs text-slate-400">-</span>
                @endif
            @endscope

            @scope('cell_net_salary', $user)
                @if($user->salaryTemplate)
                    @php
                        $net = $user->salaryTemplate->base_salary
                            + $user->salaryTemplate->allowances->sum('amount')
                            - $user->salaryTemplate->deductions->sum('amount');
                    @endphp
                    <span class="font-mono text-sm font-bold text-slate-900 dark:text-white">
                        Rp {{ number_format($net, 0, ',', '.') }}
                    </span>
                @else
                    <span class="text-xs text-slate-400">-</span>
                @endif
            @endscope

            @scope('cell_actions', $user)
                <div class="flex justify-end">
                    @if(!auth()->user()->isYayasan())
                        <x-ui.button
                            :icon="$user->salaryTemplate ? 'o-pencil' : 'o-plus'"
                            class="btn-ghost btn-xs text-slate-400 hover:text-primary"
                            wire:click="editTemplate({{ $user->id }})"
                        />
                    @else
                        <x-ui.button
                            icon="o-eye"
                            class="btn-ghost btn-xs text-slate-400 hover:text-primary"
                            wire:click="editTemplate({{ $user->id }})"
                        />
                    @endif
                </div>
            @endscope
        </x-ui.table>

        @if($ptkUsers->isEmpty())
            <div class="py-12 text-center text-slate-400 italic text-sm">
                {{ __('Tidak ada data PTK ditemukan.') }}
            </div>
        @endif

        <div class="p-4 border-t border-slate-100 dark:border-slate-800">
            {{ $ptkUsers->links() }}
        </div>
    </x-ui.card>

    {{-- Template Modal --}}
    <x-ui.modal wire:model="templateModal" class="max-w-3xl">
        <x-ui.header
            :title="__('Template Gaji: ') . $editingUserName"
            :subtitle="__('Atur komposisi gaji pokok, tunjangan, dan potongan.')"
            separator
        />

        <div class="space-y-6 mt-4">
            {{-- Gaji Pokok --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-ui.money wire:model="base_salary" :label="__('Gaji Pokok (Rp)')" required />
                <x-ui.input wire:model="effective_date" type="date" :label="__('Berlaku Sejak')" required />
            </div>

            <x-ui.textarea wire:model="notes" :label="__('Catatan')" rows="2" :placeholder="__('Contoh: Kenaikan tahunan 2026')" />

            {{-- Tunjangan Section --}}
            <div class="border border-emerald-200 dark:border-emerald-800 rounded-xl p-4 space-y-3 bg-emerald-50/50 dark:bg-emerald-950/20">
                <div class="flex items-center justify-between">
                    <h3 class="font-bold text-emerald-700 dark:text-emerald-400 flex items-center gap-2">
                        <x-ui.icon name="o-plus-circle" class="size-5" />
                        {{ __('Tunjangan') }}
                    </h3>
                    @if(!$isViewOnly)
                        <x-ui.button
                            :label="__('Tambah')"
                            icon="o-plus"
                            class="btn-xs bg-emerald-600 hover:bg-emerald-700 text-white border-none"
                            wire:click="addAllowance"
                        />
                    @endif
                </div>

                @forelse($allowances as $index => $allowance)
                    <div class="flex items-start gap-3 p-3 bg-white dark:bg-slate-800 rounded-lg border border-emerald-100 dark:border-emerald-900">
                        <div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-3">
                            <x-ui.input
                                wire:model="allowances.{{ $index }}.name"
                                :placeholder="__('Nama tunjangan')"
                                sm
                            />
                            <x-ui.money
                                wire:model="allowances.{{ $index }}.amount"
                                :placeholder="__('Nominal')"
                                sm
                            />
                        </div>
                        @if(!$isViewOnly)
                            <button
                                wire:click="removeAllowance({{ $index }})"
                                class="mt-1 p-1.5 rounded-lg text-rose-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950 transition-colors"
                            >
                                <x-ui.icon name="o-x-mark" class="size-4" />
                            </button>
                        @endif
                    </div>
                @empty
                    <p class="text-xs text-slate-400 italic text-center py-2">{{ __('Belum ada tunjangan.') }}</p>
                @endforelse
            </div>

            {{-- Potongan Section --}}
            <div class="border border-rose-200 dark:border-rose-800 rounded-xl p-4 space-y-3 bg-rose-50/50 dark:bg-rose-950/20">
                <div class="flex items-center justify-between">
                    <h3 class="font-bold text-rose-700 dark:text-rose-400 flex items-center gap-2">
                        <x-ui.icon name="o-minus-circle" class="size-5" />
                        {{ __('Potongan') }}
                    </h3>
                    @if(!$isViewOnly)
                        <x-ui.button
                            :label="__('Tambah')"
                            icon="o-plus"
                            class="btn-xs bg-rose-600 hover:bg-rose-700 text-white border-none"
                            wire:click="addDeduction"
                        />
                    @endif
                </div>

                @forelse($deductions as $index => $deduction)
                    <div class="flex items-start gap-3 p-3 bg-white dark:bg-slate-800 rounded-lg border border-rose-100 dark:border-rose-900">
                        <div class="flex-1 grid grid-cols-1 md:grid-cols-3 gap-3">
                            <x-ui.input
                                wire:model="deductions.{{ $index }}.name"
                                :placeholder="__('Nama potongan')"
                                sm
                            />
                            <x-ui.money
                                wire:model="deductions.{{ $index }}.amount"
                                :placeholder="__('Nominal')"
                                sm
                            />
                            <x-ui.input
                                wire:model="deductions.{{ $index }}.description"
                                :placeholder="__('Keterangan')"
                                sm
                            />
                        </div>
                        @if(!$isViewOnly)
                            <button
                                wire:click="removeDeduction({{ $index }})"
                                class="mt-1 p-1.5 rounded-lg text-rose-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950 transition-colors"
                            >
                                <x-ui.icon name="o-x-mark" class="size-4" />
                            </button>
                        @endif
                    </div>
                @empty
                    <p class="text-xs text-slate-400 italic text-center py-2">{{ __('Belum ada potongan.') }}</p>
                @endforelse
            </div>

            {{-- Preview Net --}}
            <div class="bg-slate-50 dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2">
                    <span class="text-sm font-semibold text-slate-500">{{ __('Estimasi Gaji Bersih') }}</span>
                    <span class="text-xl font-black text-slate-900 dark:text-white font-mono">
                        Rp {{ number_format($this->computedNet, 0, ',', '.') }}
                    </span>
                </div>
                <div class="mt-2 flex flex-wrap gap-4 text-xs text-slate-500">
                    <span>{{ __('Pokok') }}: Rp {{ number_format($base_salary, 0, ',', '.') }}</span>
                    <span class="text-emerald-600">+{{ __('Tunjangan') }}: Rp {{ number_format(collect($allowances)->sum('amount'), 0, ',', '.') }}</span>
                    <span class="text-rose-600">-{{ __('Potongan') }}: Rp {{ number_format(collect($deductions)->sum('amount'), 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3 mt-8 pt-6 border-t border-slate-100 dark:border-slate-800">
            <x-ui.button :label="__('Batal')" wire:click="$set('templateModal', false)" />
            @if(!$isViewOnly)
                <x-ui.button :label="__('Simpan Template')" class="btn-primary" wire:click="save" spinner="save" />
            @endif
        </div>
    </x-ui.modal>

    @if($errors->any())
        <x-ui.alert :title="__('Validasi Gagal')" icon="o-x-circle" class="bg-rose-50 text-rose-800 border-rose-100" dismissible>
            <ul class="list-disc list-inside text-xs">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-ui.alert>
    @endif
</div>
