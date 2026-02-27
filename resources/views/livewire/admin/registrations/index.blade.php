<?php

declare(strict_types=1);

use App\Models\{Registration, Classroom, Level, AcademicYear, User, StudentProfile, Profile};
use Illuminate\Support\Facades\{DB, Hash};
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('components.admin.layouts.app')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $filterStatus = '';
    public ?int $filterLevelId = null;

    // Modals
    public bool $detailModal = false;
    public bool $enrollModal = false;

    // Viewing details
    public ?Registration $viewing = null;

    // Enrollment
    public ?Registration $enrolling = null;
    public ?int $enrollClassroomId = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatedFilterLevelId(): void
    {
        $this->resetPage();
    }

    public function viewDetails(Registration $registration): void
    {
        $this->viewing = $registration->load(['preferredLevel', 'academicYear', 'enrolledByUser']);
        $this->detailModal = true;
    }

    public function accept(Registration $registration): void
    {
        $registration->update(['status' => 'accepted']);
        session()->flash('success', 'Pendaftaran berhasil diterima.');
    }

    public function reject(Registration $registration): void
    {
        $registration->update(['status' => 'rejected']);
        session()->flash('success', 'Pendaftaran ditolak.');
    }

    public function openEnroll(Registration $registration): void
    {
        $this->enrolling = $registration;
        $this->enrollClassroomId = null;
        $this->enrollModal = true;
    }

    public function enroll(): void
    {
        $this->validate([
            'enrollClassroomId' => 'required|exists:classrooms,id',
        ], [
            'enrollClassroomId.required' => 'Pilih kelas untuk siswa.',
        ]);

        if (!$this->enrolling || $this->enrolling->status === 'enrolled') {
            session()->flash('error', 'Pendaftaran tidak valid untuk di-enroll.');
            return;
        }

        DB::transaction(function () {
            $reg = $this->enrolling;

            // 1. Create User
            $user = User::create([
                'name' => $reg->name,
                'email' => $reg->email ?: strtolower(str_replace(' ', '.', $reg->name)) . '@siswa.pkbm',
                'password' => Hash::make('password'),
                'role' => 'siswa',
                'phone' => $reg->phone,
                'is_active' => true,
            ]);

            // 2. Create StudentProfile
            $studentProfile = StudentProfile::create([
                'nik' => $reg->nik,
                'nisn' => $reg->nisn,
                'pob' => $reg->pob,
                'dob' => $reg->dob,
                'phone' => $reg->phone,
                'address' => $reg->address,
                'province_name' => $reg->province_name,
                'regency_name' => $reg->regency_name,
                'district_name' => $reg->district_name,
                'village_name' => $reg->village_name,
                'father_name' => $reg->father_name,
                'mother_name' => $reg->mother_name,
                'guardian_name' => $reg->guardian_name,
                'guardian_phone' => $reg->guardian_phone,
                'nik_ayah' => $reg->nik_ayah,
                'nik_ibu' => $reg->nik_ibu,
                'no_kk' => $reg->no_kk,
                'no_akta' => $reg->no_akta,
                'birth_order' => $reg->birth_order,
                'total_siblings' => $reg->total_siblings,
                'previous_school' => $reg->previous_school,
                'classroom_id' => $this->enrollClassroomId,
                'status' => 'baru',
            ]);

            // 3. Link User ↔ StudentProfile via Profile (polymorphic)
            $user->profiles()->create([
                'profileable_id' => $studentProfile->id,
                'profileable_type' => StudentProfile::class,
            ]);

            // 4. Update registration status
            $reg->update([
                'status' => 'enrolled',
                'enrolled_at' => now(),
                'enrolled_by' => auth()->id(),
            ]);
        });

        $this->reset(['enrolling', 'enrollClassroomId']);
        $this->enrollModal = false;
        session()->flash('success', 'Siswa berhasil di-enroll ke kelas!');
        $this->dispatch('enrollment-saved');
    }

    public function delete(Registration $registration): void
    {
        $registration->delete();
        session()->flash('success', 'Pendaftaran berhasil dihapus.');
    }

    public function with(): array
    {
        return [
            'registrations' => Registration::with(['preferredLevel', 'academicYear'])
                ->when($this->search, fn($q) => $q->where(function($sub) {
                    $sub->where('name', 'like', "%{$this->search}%")
                        ->orWhere('registration_number', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%")
                        ->orWhere('phone', 'like', "%{$this->search}%");
                }))
                ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
                ->when($this->filterLevelId, fn($q) => $q->where('preferred_level_id', $this->filterLevelId))
                ->orderByDesc('created_at')
                ->paginate(15),
            'levels' => Level::orderBy('name')->get(),
            'classrooms' => Classroom::with(['academicYear', 'level'])->orderBy('name')->get(),
            'statusCounts' => [
                'all' => Registration::count(),
                'pending' => Registration::pending()->count(),
                'accepted' => Registration::accepted()->count(),
                'rejected' => Registration::rejected()->count(),
                'enrolled' => Registration::enrolled()->count(),
            ],
        ];
    }
}; ?>

<div class="p-6">
    @if (session('success'))
        <x-alert title="Berhasil" icon="o-check-circle" class="alert-success mb-6" dismissible>
            {{ session('success') }}
        </x-alert>
    @endif

    @if (session('error'))
        <x-alert title="Gagal" icon="o-exclamation-circle" class="alert-error mb-6" dismissible>
            {{ session('error') }}
        </x-alert>
    @endif

    <!-- Header -->
    <x-header title="Manajemen Pendaftaran" subtitle="Kelola pendaftaran siswa baru." separator>
        <x-slot:actions>
            <x-input wire:model.live.debounce.300ms="search" placeholder="Cari pendaftar..." icon="o-magnifying-glass" class="w-64" />
        </x-slot:actions>
    </x-header>

    <!-- Status Tabs -->
    <div class="flex flex-wrap gap-2 mb-6">
        <x-button wire:click="$set('filterStatus', '')"
            label="Semua ({{ $statusCounts['all'] }})"
            class="{{ !$filterStatus ? 'btn-neutral' : 'btn-ghost bg-base-200' }} btn-sm"
        />
        <x-button wire:click="$set('filterStatus', 'pending')"
            label="Menunggu ({{ $statusCounts['pending'] }})"
            class="{{ $filterStatus === 'pending' ? 'bg-yellow-500 text-white border-none' : 'btn-ghost bg-yellow-50 text-yellow-700' }} btn-sm"
        />
        <x-button wire:click="$set('filterStatus', 'accepted')"
            label="Diterima ({{ $statusCounts['accepted'] }})"
            class="{{ $filterStatus === 'accepted' ? 'bg-blue-500 text-white border-none' : 'btn-ghost bg-blue-50 text-blue-700' }} btn-sm"
        />
        <x-button wire:click="$set('filterStatus', 'rejected')"
            label="Ditolak ({{ $statusCounts['rejected'] }})"
            class="{{ $filterStatus === 'rejected' ? 'bg-red-500 text-white border-none' : 'btn-ghost bg-red-50 text-red-700' }} btn-sm"
        />
        <x-button wire:click="$set('filterStatus', 'enrolled')"
            label="Terdaftar ({{ $statusCounts['enrolled'] }})"
            class="{{ $filterStatus === 'enrolled' ? 'bg-green-500 text-white border-none' : 'btn-ghost bg-green-50 text-green-700' }} btn-sm"
        />
    </div>

    <!-- Table -->
    <div class="overflow-hidden border rounded-xl border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm">
        <table class="table">
            <thead>
                <tr>
                    <th class="bg-base-200">No. Daftar</th>
                    <th class="bg-base-200">Pendaftar</th>
                    <th class="bg-base-200">Jenjang</th>
                    <th class="bg-base-200">Tanggal</th>
                    <th class="bg-base-200">Status</th>
                    <th class="bg-base-200 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($registrations as $reg)
                    <tr wire:key="reg-{{ $reg->id }}" class="hover">
                        <td>
                            <span class="font-mono text-xs bg-base-200 px-2 py-1 rounded">{{ $reg->registration_number }}</span>
                        </td>
                        <td>
                            <div class="flex flex-col cursor-pointer" wire:click="viewDetails({{ $reg->id }})">
                                <div class="font-bold text-zinc-900 dark:text-white">{{ $reg->name }}</div>
                                <div class="text-xs opacity-60">{{ $reg->phone ?? $reg->email ?? '-' }}</div>
                            </div>
                        </td>
                        <td class="opacity-70">
                            {{ $reg->preferredLevel?->name ?? '-' }}
                        </td>
                        <td class="opacity-60 text-xs">
                            {{ $reg->created_at->format('d M Y') }}
                        </td>
                        <td>
                            <x-badge 
                                :label="$reg->status_label" 
                                class="{{ $reg->status === 'pending' ? 'badge-warning' : ($reg->status === 'accepted' ? 'badge-info' : ($reg->status === 'enrolled' ? 'badge-success' : 'badge-error')) }} badge-sm" 
                            />
                        </td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-1">
                                <x-button icon="o-eye" wire:click="viewDetails({{ $reg->id }})" ghost sm tooltip="Lihat Detail" />

                                @if($reg->status === 'pending')
                                    <x-button icon="o-check" class="text-success" wire:click="accept({{ $reg->id }})" ghost sm tooltip="Terima" />
                                    <x-button icon="o-x-mark" class="text-error" wire:confirm="Yakin ingin menolak pendaftaran ini?" wire:click="reject({{ $reg->id }})" ghost sm tooltip="Tolak" />
                                @endif

                                @if($reg->status === 'accepted')
                                    <x-button icon="o-academic-cap" class="btn-primary btn-sm" wire:click="openEnroll({{ $reg->id }})" tooltip="Enroll ke Kelas" />
                                @endif

                                @if($reg->status !== 'enrolled')
                                    <x-button icon="o-trash" class="text-error" wire:confirm="Yakin ingin menghapus pendaftaran ini?" wire:click="delete({{ $reg->id }})" ghost sm tooltip="Hapus" />
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center opacity-50 italic">
                            Belum ada data pendaftaran.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $registrations->links() }}
    </div>

    <!-- Detail Modal -->
    <x-modal wire:model="detailModal" class="backdrop-blur">
        @if($viewing)
            <x-header title="Detail Pendaftaran" :subtitle="$viewing->registration_number" separator />
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 h-[50vh] overflow-y-auto pr-2 custom-scrollbar">
                <!-- Data Pribadi -->
                <div class="space-y-4">
                    <div class="font-bold border-b pb-1 text-xs opacity-50 uppercase tracking-widest">Data Pribadi</div>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between"><span class="opacity-60">Nama</span> <span class="font-bold">{{ $viewing->name }}</span></div>
                        <div class="flex justify-between"><span class="opacity-60">NIK</span> <span>{{ $viewing->nik ?? '-' }}</span></div>
                        <div class="flex justify-between"><span class="opacity-60">TTL</span> <span>{{ $viewing->pob ?? '-' }}, {{ $viewing->dob?->format('d/m/Y') ?? '-' }}</span></div>
                        <div class="flex justify-between"><span class="opacity-60">HP</span> <span>{{ $viewing->phone ?? '-' }}</span></div>
                        <div class="flex justify-between"><span class="opacity-60">Email</span> <span>{{ $viewing->email ?? '-' }}</span></div>
                    </div>

                    <div class="font-bold border-b pb-1 pt-4 text-xs opacity-50 uppercase tracking-widest">Alamat</div>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between"><span class="opacity-60">Alamat</span> <span>{{ $viewing->address ?? '-' }}</span></div>
                        <div class="flex justify-between"><span class="opacity-60">Desa</span> <span>{{ $viewing->village_name ?? '-' }}</span></div>
                        <div class="flex justify-between"><span class="opacity-60">Kecamatan</span> <span>{{ $viewing->district_name ?? '-' }}</span></div>
                    </div>
                </div>

                <!-- Akademik & Ortu -->
                <div class="space-y-4">
                    <div class="font-bold border-b pb-1 text-xs opacity-50 uppercase tracking-widest">Akademik</div>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between"><span class="opacity-60">NISN</span> <span>{{ $viewing->nisn ?? '-' }}</span></div>
                        <div class="flex justify-between"><span class="opacity-60">Jenjang</span> <span>{{ $viewing->preferredLevel?->name ?? '-' }}</span></div>
                        <div class="flex justify-between"><span class="opacity-60">Asal Sekolah</span> <span>{{ $viewing->previous_school ?? '-' }}</span></div>
                    </div>

                    <div class="font-bold border-b pb-1 pt-4 text-xs opacity-50 uppercase tracking-widest">Orang Tua</div>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between"><span class="opacity-60">Ayah</span> <span>{{ $viewing->father_name ?? '-' }}</span></div>
                        <div class="flex justify-between"><span class="opacity-60">Ibu</span> <span>{{ $viewing->mother_name ?? '-' }}</span></div>
                        <div class="flex justify-between"><span class="opacity-60">No. KK</span> <span>{{ $viewing->no_kk ?? '-' }}</span></div>
                    </div>
                </div>
            </div>

            <x-slot:actions>
                @if($viewing->status === 'pending')
                    <x-button label="Terima" icon="o-check" class="btn-primary" wire:click="accept({{ $viewing->id }})" @click="$set('detailModal', false)" />
                    <x-button label="Tolak" icon="o-x-mark" class="btn-error btn-outline" wire:click="reject({{ $viewing->id }})" wire:confirm="Yakin ingin menolak?" @click="$set('detailModal', false)" />
                @endif
                @if($viewing->status === 'accepted')
                    <x-button label="Enroll ke Kelas" icon="o-academic-cap" class="btn-primary" wire:click="openEnroll({{ $viewing->id }})" @click="$set('detailModal', false)" />
                @endif
                <x-button label="Tutup" @click="$set('detailModal', false)" />
            </x-slot:actions>
        @endif
    </x-modal>

    <!-- Enroll Modal -->
    <x-modal wire:model="enrollModal" class="backdrop-blur">
        @if($enrolling)
            <x-header title="Enroll Siswa ke Kelas" :subtitle="$enrolling->name" separator />
            
            <form wire:submit="enroll" class="space-y-6">
                <div class="alert alert-info text-xs">
                    <div>
                        <x-icon name="o-information-circle" class="size-6" />
                        <div>Proses ini akan membuat akun User dan profil siswa, serta menempatkan mereka di kelas.</div>
                    </div>
                </div>

                <x-select wire:model="enrollClassroomId" label="Pilih Kelas" :options="$classrooms" placeholder="-- Pilih Kelas --" />

                <x-slot:actions>
                    <x-button label="Batal" @click="$set('enrollModal', false)" />
                    <x-button label="Enroll Siswa" type="submit" class="btn-primary" spinner="enroll" />
                </x-slot:actions>
            </form>
        @endif
    </x-modal>
</div>
