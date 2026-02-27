<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Transaction;
use App\Models\StudentBilling;
use App\Models\FeeCategory;
use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\AcademicYear;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('components.admin.layouts.app')] class extends Component {
    public string $tab = 'financial';
    
    // Financial Filters
    public ?int $fee_category_id = null;
    public ?string $start_date = null;
    public ?string $end_date = null;
    
    // Academic Filters
    public ?int $classroom_id = null;
    public ?int $academic_year_id = null;

    public function mount(): void
    {
        $this->start_date = now()->startOfMonth()->format('Y-m-d');
        $this->end_date = now()->format('Y-m-d');
        
        $activeYear = AcademicYear::where('is_active', true)->first();
        if ($activeYear) {
            $this->academic_year_id = $activeYear->id;
        }
    }

    public function with(): array
    {
        $financialData = [];
        if ($this->tab === 'financial') {
            $financialData = Transaction::with(['billing.student', 'billing.feeCategory'])
                ->when($this->fee_category_id, function($q) {
                    $q->whereHas('billing', fn($bq) => $bq->where('fee_category_id', $this->fee_category_id));
                })
                ->when($this->start_date, fn($q) => $q->whereDate('payment_date', '>=', $this->start_date))
                ->when($this->end_date, fn($q) => $q->whereDate('payment_date', '<=', $this->end_date))
                ->latest()
                ->get();
        }

        $attendanceData = [];
        if ($this->tab === 'attendance') {
            $attendanceData = Attendance::with(['classroom', 'subject'])
                ->when($this->classroom_id, fn($q) => $q->where('classroom_id', $this->classroom_id))
                ->when($this->academic_year_id, fn($q) => $q->where('academic_year_id', $this->academic_year_id))
                ->latest()
                ->get();
        }

        return [
            'financialData' => $financialData,
            'attendanceData' => $attendanceData,
            'categories' => FeeCategory::all(),
            'classrooms' => Classroom::all(),
            'years' => AcademicYear::all(),
        ];
    }
}; ?>

<div class="p-6">
<div class="p-6 flex flex-col gap-6">
    <x-header title="Laporan & Analitik" subtitle="Pantau performa akademik dan keuangan PKBM." separator />

    <!-- Tabs -->
    <x-tabs wire:model="tab">
        <x-tab name="financial" label="Laporan Keuangan" icon="o-banknotes" />
        <x-tab name="attendance" label="Laporan Presensi" icon="o-clipboard-document-check" />
    </x-tabs>

    <!-- Filters -->
    <x-card shadow class="bg-base-200/50 border-dashed">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            @if($tab === 'financial')
                <x-select 
                    wire:model.live="fee_category_id" 
                    label="Kategori Biaya" 
                    placeholder="Semua Kategori"
                    :options="$categories"
                />
                <x-input wire:model.live="start_date" type="date" label="Dari Tanggal" />
                <x-input wire:model.live="end_date" type="date" label="Sampai Tanggal" />
            @endif

            @if($tab === 'attendance')
                <x-select 
                    wire:model.live="academic_year_id" 
                    label="Tahun Ajaran" 
                    :options="$years"
                />
                <x-select 
                    wire:model.live="classroom_id" 
                    label="Kelas" 
                    placeholder="Semua Kelas"
                    :options="$classrooms"
                />
            @endif
            
            <x-button label="Cetak / Export" icon="o-printer" class="btn-primary w-full" />
        </div>
    </x-card>

    <!-- Results -->
    <x-card shadow>
        @if($tab === 'financial')
            <x-table :headers="[
                ['key' => 'payment_date', 'label' => 'Tanggal'],
                ['key' => 'student_name', 'label' => 'Siswa'],
                ['key' => 'category_name', 'label' => 'Kategori'],
                ['key' => 'payment_method', 'label' => 'Metode', 'class' => 'uppercase text-xs'],
                ['key' => 'amount', 'label' => 'Nominal', 'class' => 'text-right font-mono']
            ]" :rows="$financialData">
                @scope('cell_payment_date', $tx)
                    <span class="opacity-70">{{ $tx->payment_date->format('d/m/Y') }}</span>
                @endscope

                @scope('cell_student_name', $tx)
                    <span class="font-medium">{{ $tx->billing?->student?->name ?? 'Siswa Dihapus' }}</span>
                @endscope

                @scope('cell_category_name', $tx)
                    <span class="text-zinc-600 dark:text-zinc-400">{{ $tx->billing?->feeCategory?->name ?? 'Kategori Dihapus' }}</span>
                @endscope

                @scope('cell_amount', $tx)
                    Rp {{ number_format($tx->amount, 0, ',', '.') }}
                @endscope

                <x-slot:append>
                    @php $totalIncome = $financialData->sum('amount'); @endphp
                    <tr class="bg-base-200 font-bold">
                        <td colspan="4" class="text-right uppercase tracking-wider text-xs">Total Pendapatan</td>
                        <td class="text-right font-mono text-lg text-primary">
                            Rp {{ number_format($totalIncome, 0, ',', '.') }}
                        </td>
                    </tr>
                </x-slot:append>
            </x-table>
        @endif

        @if($tab === 'attendance')
            <x-table :headers="[
                ['key' => 'date', 'label' => 'Tanggal'],
                ['key' => 'classroom.name', 'label' => 'Kelas'],
                ['key' => 'subject_name', 'label' => 'Mata Pelajaran'],
                ['key' => 'percentage', 'label' => 'Kehadiran', 'class' => 'text-center']
            ]" :rows="$attendanceData">
                @scope('cell_date', $att)
                    <span class="opacity-70">{{ $att->date->format('d/m/Y') }}</span>
                @endscope

                @scope('cell_subject_name', $att)
                    {{ $att->subject?->name ?? 'Harian' }}
                @endscope

                @scope('cell_percentage', $att)
                    @php 
                        $items = $att->items;
                        $present = $items->filter(fn($i) => $i->status === 'h')->count();
                        $total = $items->count();
                        $percent = $total > 0 ? round(($present / $total) * 100) : 0;
                    @endphp
                    <div class="flex items-center justify-center gap-2">
                        <div class="text-xs font-bold">{{ $percent }}%</div>
                        <div class="w-16 h-1.5 bg-base-300 rounded-full overflow-hidden">
                            <div class="h-full bg-success" style="width: {{ $percent }}%"></div>
                        </div>
                        <div class="text-[10px] opacity-50">({{ $present }}/{{ $total }})</div>
                    </div>
                @endscope
            </x-table>
        @endif
    </x-card>
</div>
</div>
