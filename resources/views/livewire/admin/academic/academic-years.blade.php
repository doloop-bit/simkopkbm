<?php

declare(strict_types=1);

use App\Models\AcademicYear;
use App\Models\Classroom;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public string $name = '';
    public string $start_date = '';
    public string $end_date = '';
    public bool $is_active = false;
    public string $status = 'open';
    public bool $yearModal = false;

    // Copy Classrooms Options
    public bool $copy_classrooms = true;
    public ?int $copy_from_year_id = null;
    public bool $copy_homeroom = true;
    public bool $copy_assignments = true;

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

    public function updatedName(string $value): void
    {
        if (preg_match('/^(\d{4})\s*[\/\-]\s*(\d{4})$/', trim($value), $matches)) {
            $startYear = (int) $matches[1];
            $endYear = (int) $matches[2];
            if ($endYear === $startYear + 1) {
                $this->start_date = "{$startYear}-07-01";
                $this->end_date = "{$endYear}-06-30";
            }
        }
    }

    public function applyYearPreset(int $startYear): void
    {
        $endYear = $startYear + 1;
        $this->name = "{$startYear}/{$endYear}";
        $this->start_date = "{$startYear}-07-01";
        $this->end_date = "{$endYear}-06-30";
    }

    public function createNew(): void
    {
        $this->reset(['name', 'start_date', 'end_date', 'is_active', 'status', 'editing']);
        $this->resetValidation();

        // Auto-detect next academic year from latest existing year or current date
        $latestYear = AcademicYear::orderByDesc('end_date')->first();
        $activeYear = AcademicYear::where('is_active', true)->first() ?? $latestYear;

        if ($latestYear && preg_match('/^(\d{4})\/(\d{4})$/', $latestYear->name, $matches)) {
            $nextStart = (int) $matches[2];
        } elseif ($latestYear && $latestYear->end_date) {
            $nextStart = (int) $latestYear->end_date->format('Y');
        } else {
            $nextStart = (int) date('Y');
        }

        $this->applyYearPreset($nextStart);

        if ($activeYear) {
            $this->copy_from_year_id = $activeYear->id;
            $this->copy_classrooms = true;
        } else {
            $this->copy_from_year_id = null;
            $this->copy_classrooms = false;
        }

        $this->copy_homeroom = true;
        $this->copy_assignments = true;
        $this->yearModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate();

        DB::transaction(function () use ($validated) {
            $copiedCount = 0;

            if ($this->editing) {
                $this->editing->update($validated);
                $year = $this->editing;
            } else {
                $year = AcademicYear::create($validated);

                if ($this->copy_classrooms && $this->copy_from_year_id) {
                    $copiedCount = Classroom::copyFromYear(
                        (int) $this->copy_from_year_id,
                        $year->id,
                        $this->copy_homeroom,
                        $this->copy_assignments
                    );
                }
            }

            if ($this->is_active) {
                $this->setActive($year);
            }

            if (!$this->editing && $copiedCount > 0) {
                session()->flash('success', __('Tahun ajaran :name berhasil dibuat beserta :count kelas yang disalin.', [
                    'name' => $year->name,
                    'count' => $copiedCount,
                ]));
            } else {
                session()->flash('success', __('Tahun ajaran berhasil disimpan.'));
            }
        });

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
        $currentYear = (int) date('Y');
        $presetYears = [
            $currentYear - 1,
            $currentYear,
            $currentYear + 1,
        ];

        return [
            'years' => AcademicYear::latest()->paginate(10),
            'allYears' => AcademicYear::orderByDesc('end_date')->get(),
            'presetYears' => $presetYears,
        ];
    }
}; ?>

<div class="p-6 space-y-6">
    @if (session('success'))
        <x-ui.alert :title="__('Berhasil')" icon="o-check-circle" class="bg-emerald-50 text-emerald-800 border-emerald-100" dismissible>
            {{ session('success') }}
        </x-ui.alert>
    @endif

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
                        <x-ui.badge :label="__('Aktif')" flat size="xs" />
                    @else
                        <x-ui.badge :label="__('Non-aktif')" flat size="xs" />
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
                    :variant="$year->status === 'open' ? 'success' : 'amber'"
                    flat
                    size="xs"
                />
            @endscope

            @scope('cell_actions', $year)
                <div class="flex justify-end gap-1">
                    @if(!$year->is_active)
                        <x-ui.button :label="__('Set Aktif')" wire:click="setActive({{ $year->id }})" ghost />
                    @endif
                    <x-ui.button icon="o-pencil-square" wire:click="edit({{ $year->id }})" ghost />
                    <x-ui.button 
                        icon="o-trash" 
                        class="text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20" 
                        wire:confirm="{{ __('Yakin ingin menghapus ini?') }}" 
                        wire:click="delete({{ $year->id }})" 
                        ghost 
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
            <div class="space-y-2">
                <x-ui.input wire:model.live.debounce.300ms="name" :label="__('Nama Tahun Ajaran (Contoh: 2025/2026)')" required />
                <div class="flex flex-wrap items-center gap-1.5 pt-1">
                    <span class="text-xs text-slate-500 dark:text-slate-400 mr-1">{{ __('Pilih Cepat (1 Juli - 30 Juni):') }}</span>
                    @foreach($presetYears as $startYr)
                        @php $label = $startYr . '/' . ($startYr + 1); @endphp
                        <button
                            type="button"
                            wire:click="applyYearPreset({{ $startYr }})"
                            class="px-2.5 py-1 text-xs font-medium rounded-lg border transition-colors {{ $name === $label ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-slate-100 hover:bg-slate-200 text-slate-700 border-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 dark:border-slate-700' }}"
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>

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

            @if(!$editing && $allYears->isNotEmpty())
                <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 space-y-3">
                    <x-ui.checkbox wire:model.live="copy_classrooms" :label="__('Salin struktur kelas dari tahun ajaran sebelumnya')" />

                    @if($copy_classrooms)
                        <div class="pl-6 space-y-3 pt-1">
                            <x-ui.select
                                wire:model="copy_from_year_id"
                                :label="__('Salin Dari Tahun Ajaran')"
                                :options="$allYears"
                            />
                            <div class="flex flex-col sm:flex-row gap-3 pt-1">
                                <x-ui.checkbox wire:model="copy_homeroom" :label="__('Sertakan Wali Kelas')" />
                                <x-ui.checkbox wire:model="copy_assignments" :label="__('Sertakan Penugasan Guru Mapel')" />
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            <div class="flex justify-end gap-2 pt-4">
                <x-ui.button :label="__('Batal')" ghost @click="show = false" />
                <x-ui.button :label="__('Simpan')" type="submit" class="btn-primary" spinner="save" />
            </div>
        </form>
    </x-ui.modal>
</div>
