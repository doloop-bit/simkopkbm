<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Transaction;
use App\Models\StudentBilling;
use App\Models\Classroom;
use App\Models\Attendance;
use Livewire\Component;

new class extends Component {
    public function with(): array
    {
        $totalStudents = User::where('role', 'siswa')->count();
        $totalTeachers = User::where('role', 'guru')->count();
        $totalClassrooms = Classroom::count();
        
        $start = now()->startOfMonth();
        $end = now()->endOfMonth();
        
        $incomeMonth = Transaction::whereBetween('payment_date', [$start, $end])
            ->sum('amount');
            
        $pendingBillings = StudentBilling::where('status', '!=', 'paid')
            ->sum(\Illuminate\Support\Facades\DB::raw('amount - paid_amount'));

        $recentTransactions = Transaction::with(['billing.student', 'billing.feeCategory'])
            ->latest()
            ->limit(5)
            ->get();

        $recentAttendance = Attendance::with(['classroom'])
            ->withCount('items')
            ->latest()
            ->limit(5)
            ->get();

        return [
            'stats' => [
                'students' => $totalStudents,
                'teachers' => $totalTeachers,
                'classrooms' => $totalClassrooms,
                'income_month' => $incomeMonth,
                'pending_billings' => $pendingBillings,
            ],
            'recentTransactions' => $recentTransactions,
            'recentAttendance' => $recentAttendance,
        ];
    }
}; ?>

<div class="space-y-6">
    <!-- Header -->
    <x-header title="Selamat Datang, {{ auth()->user()->name }}" subtitle="Ringkasan aktivitas PKBM hari ini.">
        <x-slot:actions>
            <div class="text-sm font-medium text-base-content/60">{{ now()->translatedFormat('l, d F Y') }}</div>
        </x-slot:actions>
    </x-header>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <x-stat
            title="Total Siswa"
            value="{{ $stats['students'] }}"
            icon="o-users"
            class="bg-base-100 shadow-sm"
            color="text-primary"
        />

        <x-stat
            title="Total Guru"
            value="{{ $stats['teachers'] }}"
            icon="o-academic-cap"
            class="bg-base-100 shadow-sm"
            color="text-secondary"
        />

        <x-stat
            title="Pendapatan (Bulan Ini)"
            value="Rp {{ number_format($stats['income_month'] / 1000, 0) }}k"
            icon="o-banknotes"
            class="bg-base-100 shadow-sm"
            color="text-success"
        />

        <x-stat
            title="Piutang Tagihan"
            value="Rp {{ number_format($stats['pending_billings'] / 1000000, 1) }}M"
            icon="o-document-minus"
            class="bg-base-100 shadow-sm"
            color="text-warning"
        />
    </div>

    <!-- Main Content -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Transactions -->
        <x-card title="Transaksi Terakhir" separator progress-indicator shadow>
            <x-slot:menu>
                <x-button label="Lihat Semua" link="{{ route('financial.transactions') }}" wire:navigate ghost sm />
            </x-slot:menu>
            
            <div class="divide-y divide-base-200">
                @forelse($recentTransactions as $tx)
                    <div class="py-3 flex justify-between items-start">
                        <x-list-item :item="$tx" no-separator no-hover class="!p-0">
                            <x-slot:avatar>
                                <div class="w-10 h-10 rounded-full bg-base-200 flex items-center justify-center font-bold text-base-content/50">
                                    {{ substr($tx->billing?->student?->name ?? '?', 0, 1) }}
                                </div>
                            </x-slot:avatar>
                            <x-slot:value>
                                {{ $tx->billing?->student?->name ?? 'Siswa Dihapus' }}
                            </x-slot:value>
                            <x-slot:sub-value>
                                {{ $tx->billing?->feeCategory?->name ?? 'Kategori Dihapus' }} - {{ $tx->payment_method }}
                            </x-slot:sub-value>
                        </x-list-item>
                        <div class="text-right">
                            <div class="font-bold text-success font-mono">+ Rp {{ number_format($tx->amount, 0, ',', '.') }}</div>
                            <div class="text-[10px] opacity-60">{{ $tx->payment_date->diffForHumans() }}</div>
                        </div>
                    </div>
                @empty
                    <div class="py-8 text-center opacity-50 text-sm">Belum ada transaksi masuk.</div>
                @endforelse
            </div>
        </x-card>

        <!-- Recent Attendance -->
        <x-card title="Input Presensi Terakhir" separator progress-indicator shadow>
            <x-slot:menu>
                <x-button label="Lihat Semua" link="{{ route('academic.attendance') }}" wire:navigate ghost sm />
            </x-slot:menu>
            
            <div class="divide-y divide-base-200">
                @forelse($recentAttendance as $att)
                    <div class="py-3 flex justify-between items-center text-sm">
                        <div class="flex gap-3 items-center">
                            <div class="p-2 bg-primary/10 rounded-lg">
                                <x-icon name="o-clipboard-document-check" class="text-primary size-5" />
                            </div>
                            <div>
                                <div class="font-medium">Kelas {{ $att->classroom->name }}</div>
                                <div class="text-xs opacity-60">
                                    {{ $att->subject?->name ?? 'Harian' }} • {{ $att->date->format('d/m/Y') }}
                                </div>
                            </div>
                        </div>
                        <div class="text-xs opacity-50">
                            {{ $att->items_count ?? $att->items()->count() }} Siswa
                        </div>
                    </div>
                @empty
                    <div class="py-8 text-center opacity-50 text-sm">Belum ada data presensi.</div>
                @endforelse
            </div>
        </x-card>
    </div>
</div>
