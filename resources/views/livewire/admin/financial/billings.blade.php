<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\FeeCategory;
use App\Models\StudentBilling;
use App\Models\AcademicYear;
use App\Models\Classroom;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('components.admin.layouts.app')] class extends Component {
    use WithPagination;

    public ?int $academic_year_id = null;
    public ?int $classroom_id = null;
    public ?int $fee_category_id = null;
    public string $month = '';
    public ?float $amount = null;

    public string $search = '';
    public bool $billingModal = false;

    public function mount(): void
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        if ($activeYear) {
            $this->academic_year_id = $activeYear->id;
        }
        $this->month = now()->format('Y-m');
    }

    public function updatedFeeCategoryId($value): void
    {
        if ($value) {
            $category = FeeCategory::find($value);
            if ($category) {
                $this->amount = (float) $category->default_amount;
            }
        }
    }

    public function generateBillings(): void
    {
        $this->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'classroom_id' => 'required|exists:classrooms,id',
            'fee_category_id' => 'required|exists:fee_categories,id',
            'amount' => 'required|numeric|min:0',
            'month' => 'nullable|string',
        ]);

        $students = User::where('role', 'siswa')
            ->with(['feeDiscounts' => function($q) {
                $q->where(function ($query) {
                    $query->where('fee_category_id', $this->fee_category_id)
                          ->orWhereNull('fee_category_id');
                });
            }])
            ->whereHas('profiles', function ($q) {
                $q->whereHasMorph('profileable', [\App\Models\StudentProfile::class], function ($sq) {
                    $sq->where('classroom_id', $this->classroom_id);
                });
            })->get();

        $count = 0;
        foreach ($students as $student) {
            $existingBilling = StudentBilling::where([
                'student_id' => $student->id,
                'fee_category_id' => $this->fee_category_id,
                'academic_year_id' => $this->academic_year_id,
                'month' => $this->month,
            ])->first();

            if (!$existingBilling || $existingBilling->status === 'unpaid') {
                $finalAmount = (float) $this->amount;
                $notes = '';

                // Apply discounts/scholarships if any
                $discounts = $student->feeDiscounts;
                if ($discounts->isNotEmpty()) {
                    foreach ($discounts as $discount) {
                        if ($discount->discount_type === 'percentage') {
                            $discountValue = $this->amount * ($discount->amount / 100);
                            $finalAmount -= $discountValue;
                        } else {
                            $discountValue = $discount->amount;
                            $finalAmount -= $discountValue;
                        }
                        $notes .= "Potongan/Beasiswa: {$discount->name} ";
                    }
                    if ($finalAmount < 0) {
                        $finalAmount = 0;
                    }
                }

                if ($existingBilling) {
                    $existingBilling->update([
                        'amount' => $finalAmount,
                        'notes' => trim($notes) ?: null,
                    ]);
                } else {
                    StudentBilling::create([
                        'student_id' => $student->id,
                        'fee_category_id' => $this->fee_category_id,
                        'academic_year_id' => $this->academic_year_id,
                        'month' => $this->month,
                        'amount' => $finalAmount,
                        'due_date' => now()->addDays(14),
                        'status' => 'unpaid',
                        'notes' => trim($notes) ?: null,
                    ]);
                }
                $count++;
            }
        }

        session()->flash('success', __(":count Tagihan berhasil di-generate.", ['count' => $count]));
        $this->billingModal = false;
    }

    public function with(): array
    {
        $billings = StudentBilling::with(['student', 'feeCategory'])
            ->when($this->classroom_id, function($q) {
                $q->whereHas('student.profiles', function($pq) {
                    $pq->whereHasMorph('profileable', [\App\Models\StudentProfile::class], function($sq) {
                        $sq->where('classroom_id', $this->classroom_id);
                    });
                });
            })
            ->when($this->search, function($q) {
                $q->whereHas('student', function($sq) {
                    $sq->where('name', 'like', "%{$this->search}%");
                });
            })
            ->latest()
            ->paginate(15);

        $categoriesQuery = FeeCategory::query();
        if ($this->classroom_id) {
            $classroom = Classroom::find($this->classroom_id);
            if ($classroom) {
                $categoriesQuery->where(function($q) use ($classroom) {
                    $q->where('level_id', $classroom->level_id)
                      ->orWhereNull('level_id');
                });
            }
        }

        return [
            'years' => AcademicYear::all(),
            'classrooms' => Classroom::all(),
            'categories' => $categoriesQuery->get(),
            'billings' => $billings,
        ];
    }
}; ?>

<div class="p-6 space-y-6 text-slate-900 dark:text-white pb-24 md:pb-6">
    @if (session('success'))
        <x-ui.alert :title="__('Sukses')" icon="o-check-circle" class="bg-emerald-50 text-emerald-800 border-emerald-100" dismissible>
            {{ session('success') }}
        </x-ui.alert>
    @endif

    <x-ui.header :title="__('Tagihan Siswa')" :subtitle="__('Manajemen penagihan biaya pendidikan siswa secara kolektif.')" separator>
        <x-slot:actions>
            <x-ui.button :label="__('Generate Tagihan Kelas')" icon="o-document-plus" class="btn-primary" wire:click="$set('billingModal', true)" />
        </x-slot:actions>
    </x-ui.header>

    <div class="flex flex-col md:flex-row gap-4">
        <div class="flex-1">
            <x-ui.input 
                wire:model.live.debounce.300ms="search" 
                :placeholder="__('Cari nama siswa...')" 
                icon="o-magnifying-glass" 
            />
        </div>
        <x-ui.select 
            wire:model.live="classroom_id" 
            :placeholder="__('Semua Kelas')" 
            class="w-full md:w-64" 
            :options="$classrooms" 
        />
    </div>

    <x-ui.card shadow padding="false">
        <x-ui.table 
            :headers="[
                ['key' => 'student_name', 'label' => __('Siswa')],
                ['key' => 'category', 'label' => __('Kategori')],
                ['key' => 'month_label', 'label' => __('Bulan')],
                ['key' => 'amount_label', 'label' => __('Nominal'), 'class' => 'text-right'],
                ['key' => 'status_label', 'label' => __('Status'), 'class' => 'text-center']
            ]" 
            :rows="$billings"
        >
            @scope('cell_student_name', $billing)
                <div class="flex flex-col">
                    <span class="font-bold text-slate-900 dark:text-white">{{ $billing->student?->name ?? __('Siswa Dihapus') }}</span>
                    <span class="text-[10px] text-slate-400 font-mono tracking-tighter">{{ $billing->student?->profiles?->first()?->profileable?->name ?? '-' }}</span>
                </div>
            @endscope

            @scope('cell_category', $billing)
                <span class="text-xs text-slate-500 font-medium uppercase tracking-tight">{{ $billing->feeCategory?->name ?? __('Kategori Dihapus') }}</span>
            @endscope

            @scope('cell_month_label', $billing)
                <span class="text-xs font-black text-slate-400 font-mono">{{ $billing->month ?? '-' }}</span>
            @endscope

            @scope('cell_amount_label', $billing)
                <span class="font-mono text-sm font-bold text-slate-700 dark:text-slate-300 italic ring-1 ring-slate-100 dark:ring-slate-800 px-2 py-0.5 rounded-lg bg-slate-50 dark:bg-slate-900/50">
                    Rp {{ number_format($billing->amount, 0, ',', '.') }}
                </span>
            @endscope

            @scope('cell_status_label', $billing)
                @if($billing->status === 'paid')
                    <x-ui.badge :label="strtoupper($billing->status)" class="bg-emerald-100 text-emerald-700 border-none text-[8px] font-black px-2 py-0.5" />
                @elseif($billing->status === 'partial')
                    <x-ui.badge :label="strtoupper($billing->status)" class="bg-amber-100 text-amber-700 border-none text-[8px] font-black px-2 py-0.5" />
                @else
                    <x-ui.badge :label="strtoupper($billing->status)" class="bg-rose-100 text-rose-700 border-none text-[8px] font-black px-2 py-0.5" />
                @endif
            @endscope
        </x-ui.table>

        @if($billings->isEmpty())
            <div class="py-12 text-center text-slate-400 italic text-sm">
                {{ __('Tidak ada data tagihan yang ditemukan.') }}
            </div>
        @endif

        <div class="p-4 border-t border-slate-100 dark:border-slate-800">
            {{ $billings->links() }}
        </div>
    </x-ui.card>

    <x-ui.modal wire:model="billingModal">
        <x-ui.header :title="__('Generate Tagihan Massal')" :subtitle="__('Buat tagihan untuk satu kelas sekaligus.')" separator />

        <div class="space-y-6">
            <x-ui.select wire:model="classroom_id" :label="__('Kelas Target')" :placeholder="__('Pilih Kelas')" :options="$classrooms" required />
            <x-ui.select wire:model.live="fee_category_id" :label="__('Jenis Biaya')" :placeholder="__('Pilih Kategori Biaya')" :options="$categories" required />
            <x-ui.input wire:model="month" type="month" :label="__('Bulan Tagihan')" :placeholder="__('Hanya untuk biaya bulanan/SPP')" />
            <x-ui.input wire:model="amount" type="number" :label="__('Nominal Tagihan (Rp)')" icon="o-banknotes" required />
            
            <x-ui.alert icon="o-information-circle" class="bg-blue-50 text-blue-700 border-blue-100 mt-4 font-medium text-[10px]">
                {{ __('Sistem akan menerapkan potongan/beasiswa secara otomatis berdasarkan profil siswa yang terdaftar.') }}
            </x-ui.alert>
        </div>

        <div class="flex justify-end gap-3 mt-8 pt-6 border-t border-slate-100 dark:border-slate-800">
            <x-ui.button :label="__('Batal')" wire:click="$set('billingModal', false)" />
            <x-ui.button :label="__('Generate Sekarang')" class="btn-primary" wire:click="generateBillings" spinner="generateBillings" />
        </div>
    </x-ui.modal>
</div>
