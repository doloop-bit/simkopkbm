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

<div class="p-6 space-y-8 text-slate-900 dark:text-white pb-24 md:pb-6">
    <x-ui.header :title="__('Analitik & Pelaporan')" :subtitle="__('Pantau indikator performa utama keuangan dan tingkat partisipasi akademik secara komprehensif.')" separator />

    {{-- Report Navigation Tabs --}}
    <div class="flex items-center gap-1 p-1 bg-slate-100 dark:bg-slate-900 rounded-2xl w-fit shadow-inner">
        <x-ui.button 
            wire:click="$set('tab', 'financial')" 
            :label="__('Keuangan')" 
            icon="o-banknotes" 
            class="rounded-xl px-6 font-black italic tracking-tight py-2 h-auto {{ $tab === 'financial' ? 'bg-white text-primary shadow-sm border-none' : 'btn-ghost text-slate-400' }}" 
        />
        <x-ui.button 
            wire:click="$set('tab', 'attendance')" 
            :label="__('Presensi')" 
            icon="o-clipboard-document-check" 
            class="rounded-xl px-6 font-black italic tracking-tight py-2 h-auto {{ $tab === 'attendance' ? 'bg-white text-primary shadow-sm border-none' : 'btn-ghost text-slate-400' }}" 
        />
    </div>

    {{-- Dynamic Content & Filters --}}
    <div class="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-700">
        {{-- Specialized Filters Card --}}
        <x-ui.card shadow padding="false" class="border-none ring-1 ring-slate-100 dark:ring-slate-800 bg-slate-50/30 dark:bg-slate-900/10">
            <div class="p-8">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-end">
                    @if($tab === 'financial')
                        <div class="md:col-span-4">
                            <x-ui.select 
                                wire:model.live="fee_category_id" 
                                :label="__('Spesifikasi Kategori Biaya')" 
                                :placeholder="__('Seluruh Item Pembayaran')"
                                :options="$categories"
                                class="font-bold italic uppercase tracking-tighter"
                            />
                        </div>
                        <div class="md:col-span-3">
                            <x-ui.input wire:model.live="start_date" type="date" :label="__('Rentang Awal')" class="font-mono" />
                        </div>
                        <div class="md:col-span-3">
                            <x-ui.input wire:model.live="end_date" type="date" :label="__('Rentang Akhir')" class="font-mono" />
                        </div>
                    @endif

                    @if($tab === 'attendance')
                        <div class="md:col-span-5">
                            <x-ui.select 
                                wire:model.live="academic_year_id" 
                                :label="__('Periode Akademik Aktif')" 
                                :options="$years"
                                class="font-black italic uppercase tracking-tighter"
                            />
                        </div>
                        <div class="md:col-span-5">
                            <x-ui.select 
                                wire:model.live="classroom_id" 
                                :label="__('Fokus Grup / Rombel')" 
                                :placeholder="__('Seluruh Kelas & Level')"
                                :options="$classrooms"
                                class="font-black italic uppercase tracking-tighter"
                            />
                        </div>
                    @endif
                    
                    <div class="md:col-span-2">
                        <x-ui.button :label="__('Ekspor Data')" icon="o-printer" class="btn-primary w-full shadow-lg shadow-primary/20" />
                    </div>
                </div>
            </div>
        </x-ui.card>

        {{-- Analytical Results --}}
        <x-ui.card shadow padding="false" class="border-none ring-1 ring-slate-100 dark:ring-slate-800 overflow-hidden">
            @if($tab === 'financial')
                <x-ui.table :headers="[
                    ['key' => 'payment_date', 'label' => __('Waktu Transaksi')],
                    ['key' => 'student_name', 'label' => __('Entitas Siswa')],
                    ['key' => 'category_name', 'label' => __('Klasifikasi')],
                    ['key' => 'payment_method', 'label' => __('Metode'), 'class' => 'uppercase text-[9px] font-black italic tracking-widest'],
                    ['key' => 'amount', 'label' => __('Volume Nominal'), 'class' => 'text-right font-black italic uppercase tracking-tighter']
                ]" :rows="$financialData">
                    @scope('cell_payment_date', $tx)
                        <span class="text-[10px] font-bold text-slate-400 font-mono tracking-tighter uppercase italic">{{ $tx->payment_date->format('d / M / Y') }}</span>
                    @endscope

                    @scope('cell_student_name', $tx)
                        <div class="font-black text-slate-800 dark:text-white uppercase tracking-tighter italic">{{ $tx->billing?->student?->name ?? __('Siswa Terhapus') }}</div>
                    @endscope

                    @scope('cell_category_name', $tx)
                        <x-ui.badge :label="$tx->billing?->feeCategory?->name ?? __('Umum')" class="bg-indigo-50 text-indigo-600 border-none font-black italic text-[8px] px-3 uppercase tracking-tighter" />
                    @endscope

                    @scope('cell_amount', $tx)
                        <div class="text-right flex flex-col">
                            <span class="text-xs font-black text-slate-900 dark:text-white font-mono tracking-tighter">Rp {{ number_format($tx->amount, 0, ',', '.') }}</span>
                            <span class="text-[9px] font-bold text-emerald-500 italic uppercase tracking-widest">{{ __('TERKONFIRMASI') }}</span>
                        </div>
                    @endscope

                    <x-slot:append>
                        @php $totalIncome = $financialData->sum('amount'); @endphp
                        <tr class="bg-slate-50 dark:bg-slate-900/50">
                            <td colspan="4" class="p-6 text-right">
                                <span class="font-black italic text-slate-400 uppercase tracking-[0.2em] text-[10px]">{{ __('Total Akumulasi Pendapatan') }}</span>
                            </td>
                            <td class="p-6 text-right">
                                <span class="text-xl font-black text-primary italic font-mono tracking-tighter drop-shadow-sm">
                                    Rp {{ number_format($totalIncome, 0, ',', '.') }}
                                </span>
                            </td>
                        </tr>
                    </x-slot:append>
                </x-ui.table>
            @endif

            @if($tab === 'attendance')
                <x-ui.table :headers="[
                    ['key' => 'date', 'label' => __('Tanggal Presensi')],
                    ['key' => 'classroom.name', 'label' => __('Grup / Kelas')],
                    ['key' => 'subject_name', 'label' => __('Materi / Mapel')],
                    ['key' => 'percentage', 'label' => __('Rasio Kehadiran'), 'class' => 'text-center']
                ]" :rows="$attendanceData">
                    @scope('cell_date', $att)
                        <span class="text-[10px] font-bold text-slate-400 font-mono tracking-tighter uppercase italic">{{ $att->date->format('d / M / Y') }}</span>
                    @endscope

                    @scope('cell_classroom_name', $att)
                         <span class="font-black text-slate-700 dark:text-slate-300 uppercase tracking-tighter italic">{{ $att->classroom?->name }}</span>
                    @endscope

                    @scope('cell_subject_name', $att)
                        <span class="text-xs font-bold text-slate-500 italic">{{ $att->subject?->name ?? __('Presensi Harian / Apel') }}</span>
                    @endscope

                    @scope('cell_percentage', $att)
                        @php 
                            $items = $att->items;
                            $present = $items->filter(fn($i) => $i->status === 'h')->count();
                            $total = $items->count();
                            $percent = $total > 0 ? round(($present / $total) * 100) : 0;
                            $barColor = $percent >= 80 ? 'bg-emerald-500' : ($percent >= 60 ? 'bg-amber-500' : 'bg-rose-500');
                        @endphp
                        <div class="flex flex-col items-center gap-2">
                            <div class="flex items-end gap-1">
                                <span class="text-lg font-black text-slate-900 dark:text-white italic tracking-tighter leading-none">{{ $percent }}%</span>
                                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-0.5">({{ $present }}/{{ $total }})</span>
                            </div>
                            <div class="w-32 h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden shadow-inner flex">
                                <div class="h-full {{ $barColor }} transition-all duration-1000 ease-out" style="width: {{ $percent }}%"></div>
                            </div>
                        </div>
                    @endscope
                </x-ui.table>
            @endif
        </x-ui.card>
    </div>
</div>
