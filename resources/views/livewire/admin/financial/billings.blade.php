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
use Mary\Traits\Toast;

new #[Layout('components.admin.layouts.app')] class extends Component {
    use WithPagination, Toast;

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
            $this->amount = (float) $category->default_amount;
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
            ->whereHas('profiles.profileable', function ($q) {
                $q->where('classroom_id', $this->classroom_id);
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

        $this->success("$count Tagihan berhasil di-generate.");
        $this->billingModal = false;
    }

    public function with(): array
    {
        $billings = StudentBilling::with(['student', 'feeCategory'])
            ->when($this->classroom_id, function($q) {
                $q->whereHas('student.profiles.profileable', function($sq) {
                    $sq->where('classroom_id', $this->classroom_id);
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

<div class="p-6">
    <x-header title="Tagihan Siswa" subtitle="Manajemen penagihan biaya pendidikan siswa." separator>
        <x-slot:actions>
            <x-button label="Generate Tagihan Kelas" icon="o-document-plus" class="btn-primary" click="$set('billingModal', true)" />
        </x-slot:actions>
    </x-header>

    <div class="flex flex-col md:flex-row gap-4 mb-6">
        <div class="flex-1">
            <x-input wire:model.live.debounce.300ms="search" placeholder="Cari siswa..." icon="o-magnifying-glass" />
        </div>
        <x-select wire:model.live="classroom_id" placeholder="Semua Kelas" class="w-full md:w-64" :options="$classrooms" />
    </div>

    <div class="overflow-x-auto border rounded-xl border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm">
        <table class="table">
            <thead>
                <tr>
                    <th class="bg-base-200">Siswa</th>
                    <th class="bg-base-200 text-center">Kategori</th>
                    <th class="bg-base-200 text-center">Bulan</th>
                    <th class="bg-base-200 text-right">Nominal</th>
                    <th class="bg-base-200 text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($billings as $billing)
                    <tr class="hover" wire:key="{{ $billing->id }}">
                        <td>
                            <div class="flex flex-col">
                                <span class="font-bold">{{ $billing->student?->name ?? 'Siswa Dihapus' }}</span>
                            </div>
                        </td>
                        <td class="text-center opacity-70">
                            {{ $billing->feeCategory?->name ?? 'Kategori Dihapus' }}
                        </td>
                        <td class="text-center opacity-70 whitespace-nowrap">
                            {{ $billing->month ?? '-' }}
                        </td>
                        <td class="text-right font-mono">
                            Rp {{ number_format($billing->amount, 0, ',', '.') }}
                        </td>
                        <td class="text-center">
                            <x-badge :value="strtoupper($billing->status)" class="{{ $billing->status === 'paid' ? 'badge-success' : ($billing->status === 'partial' ? 'badge-warning' : 'badge-error') }} badge-sm" />
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $billings->links() }}
    </div>

    <x-modal wire:model="billingModal" class="backdrop-blur">
        <x-header title="Generate Tagihan" subtitle="Buat tagihan untuk satu kelas sekaligus." separator />

        <div class="grid grid-cols-1 gap-4">
            <x-select wire:model="classroom_id" label="Kelas" placeholder="Pilih Kelas" :options="$classrooms" />
            <x-select wire:model.live="fee_category_id" label="Jenis Biaya" placeholder="Pilih Biaya" :options="$categories" />
            <x-input wire:model="month" type="month" label="Bulan (Khusus SPP)" />
            <x-input wire:model="amount" type="number" label="Nominal" icon="o-banknotes" />
        </div>

        <x-slot:actions>
            <x-button label="Batal" @click="$set('billingModal', false)" />
            <x-button label="Generate" class="btn-primary" wire:click="generateBillings" spinner="generateBillings" />
        </x-slot:actions>
    </x-modal>
</div>
