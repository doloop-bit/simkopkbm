<?php

declare(strict_types=1);

use App\Models\{Registration, Classroom, Level, AcademicYear, User, StudentProfile, Profile};
use Illuminate\Support\Facades\{DB, Hash};
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.admin.layouts.app')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $filterStatus = '';
    public ?int $filterLevelId = null;

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
        $this->dispatch('open-modal', 'detail-modal');
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
        $this->dispatch('open-modal', 'enroll-modal');
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
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
            class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
            <div class="flex items-center gap-3">
                <flux:icon icon="check-circle" class="w-5 h-5 text-green-600 dark:text-green-400" />
                <span class="text-green-800 dark:text-green-200">{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if (session('error'))
        <div x-data="{ show: true }" x-show="show"
            class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
            <div class="flex items-center gap-3">
                <flux:icon icon="exclamation-circle" class="w-5 h-5 text-red-600 dark:text-red-400" />
                <span class="text-red-800 dark:text-red-200">{{ session('error') }}</span>
            </div>
        </div>
    @endif

    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl" level="1">Manajemen Pendaftaran</flux:heading>
            <flux:subheading>Kelola pendaftaran siswa baru.</flux:subheading>
        </div>
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Cari pendaftar..." icon="magnifying-glass" class="w-64" />
    </div>

    <!-- Status Tabs -->
    <div class="flex flex-wrap gap-2 mb-6">
        <button wire:click="$set('filterStatus', '')"
            class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ !$filterStatus ? 'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900' : 'bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-400' }}">
            Semua ({{ $statusCounts['all'] }})
        </button>
        <button wire:click="$set('filterStatus', 'pending')"
            class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $filterStatus === 'pending' ? 'bg-yellow-500 text-white' : 'bg-yellow-50 text-yellow-700 hover:bg-yellow-100 dark:bg-yellow-900/30 dark:text-yellow-400' }}">
            Menunggu ({{ $statusCounts['pending'] }})
        </button>
        <button wire:click="$set('filterStatus', 'accepted')"
            class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $filterStatus === 'accepted' ? 'bg-blue-500 text-white' : 'bg-blue-50 text-blue-700 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400' }}">
            Diterima ({{ $statusCounts['accepted'] }})
        </button>
        <button wire:click="$set('filterStatus', 'rejected')"
            class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $filterStatus === 'rejected' ? 'bg-red-500 text-white' : 'bg-red-50 text-red-700 hover:bg-red-100 dark:bg-red-900/30 dark:text-red-400' }}">
            Ditolak ({{ $statusCounts['rejected'] }})
        </button>
        <button wire:click="$set('filterStatus', 'enrolled')"
            class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $filterStatus === 'enrolled' ? 'bg-green-500 text-white' : 'bg-green-50 text-green-700 hover:bg-green-100 dark:bg-green-900/30 dark:text-green-400' }}">
            Terdaftar ({{ $statusCounts['enrolled'] }})
        </button>
    </div>

    <!-- Table -->
    <div class="overflow-hidden border rounded-xl border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm">
        <table class="w-full text-sm text-left border-collapse">
            <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                <tr>
                    <th class="px-4 py-3 font-semibold text-zinc-900 dark:text-zinc-100 border-b border-zinc-200 dark:border-zinc-700">No. Daftar</th>
                    <th class="px-4 py-3 font-semibold text-zinc-900 dark:text-zinc-100 border-b border-zinc-200 dark:border-zinc-700">Pendaftar</th>
                    <th class="px-4 py-3 font-semibold text-zinc-900 dark:text-zinc-100 border-b border-zinc-200 dark:border-zinc-700">Jenjang</th>
                    <th class="px-4 py-3 font-semibold text-zinc-900 dark:text-zinc-100 border-b border-zinc-200 dark:border-zinc-700">Tanggal</th>
                    <th class="px-4 py-3 font-semibold text-zinc-900 dark:text-zinc-100 border-b border-zinc-200 dark:border-zinc-700">Status</th>
                    <th class="px-4 py-3 font-semibold text-zinc-900 dark:text-zinc-100 border-b border-zinc-200 dark:border-zinc-700 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse ($registrations as $reg)
                    <tr wire:key="reg-{{ $reg->id }}" class="hover:bg-zinc-50/80 dark:hover:bg-zinc-800/30 transition-colors group">
                        <td class="px-4 py-4">
                            <span class="font-mono text-xs bg-zinc-100 dark:bg-zinc-800 px-2 py-1 rounded">{{ $reg->registration_number }}</span>
                        </td>
                        <td class="px-4 py-4">
                            <button type="button" wire:click="viewDetails({{ $reg->id }})" 
                                x-on:click="$flux.modal('detail-modal').show()"
                                class="text-left cursor-pointer focus:outline-none">
                                <div class="font-semibold text-zinc-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">{{ $reg->name }}</div>
                                <div class="text-xs text-zinc-500">{{ $reg->phone ?? $reg->email ?? '-' }}</div>
                            </button>
                        </td>
                        <td class="px-4 py-4 text-zinc-600 dark:text-zinc-400">
                            {{ $reg->preferredLevel?->name ?? '-' }}
                        </td>
                        <td class="px-4 py-4 text-zinc-500 dark:text-zinc-400 text-xs">
                            {{ $reg->created_at->format('d M Y') }}
                        </td>
                        <td class="px-4 py-4">
                            <flux:badge size="sm"
                                color="{{ $reg->status_color }}">
                                {{ $reg->status_label }}
                            </flux:badge>
                        </td>
                        <td class="px-4 py-4 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <flux:button size="sm" variant="ghost" icon="eye"
                                    wire:click="viewDetails({{ $reg->id }})" 
                                    x-on:click="$flux.modal('detail-modal').show()"
                                    tooltip="Lihat Detail" />

                                @if($reg->status === 'pending')
                                    <flux:button size="sm" variant="ghost" icon="check"
                                        class="text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20"
                                        wire:click="accept({{ $reg->id }})" tooltip="Terima" />
                                    <flux:button size="sm" variant="ghost" icon="x-mark"
                                        class="text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20"
                                        wire:confirm="Yakin ingin menolak pendaftaran ini?"
                                        wire:click="reject({{ $reg->id }})" tooltip="Tolak" />
                                @endif

                                @if($reg->status === 'accepted')
                                    <flux:button size="sm" variant="primary" icon="academic-cap"
                                        wire:click="openEnroll({{ $reg->id }})" 
                                        x-on:click="$flux.modal('enroll-modal').show()"
                                        tooltip="Enroll ke Kelas" />
                                @endif

                                @if($reg->status !== 'enrolled')
                                    <flux:button size="sm" variant="ghost" icon="trash"
                                        class="text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20"
                                        wire:confirm="Yakin ingin menghapus pendaftaran ini?"
                                        wire:click="delete({{ $reg->id }})" tooltip="Hapus" />
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center text-zinc-500 dark:text-zinc-400">
                            <div class="flex flex-col items-center gap-2">
                                <flux:icon icon="inbox" class="w-10 h-10 text-zinc-300 dark:text-zinc-600" />
                                <span class="italic">Belum ada data pendaftaran.</span>
                            </div>
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
    <flux:modal name="detail-modal" class="max-w-2xl">
        @if($viewing)
            <div class="space-y-6">
                <div class="flex items-start justify-between">
                    <div>
                        <flux:heading size="lg">Detail Pendaftaran</flux:heading>
                        <flux:subheading>{{ $viewing->registration_number }}</flux:subheading>
                    </div>
                    <flux:badge size="sm" color="{{ $viewing->status_color }}">{{ $viewing->status_label }}</flux:badge>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Data Pribadi -->
                    <div class="space-y-3">
                        <flux:heading size="sm" class="border-b pb-1">Data Pribadi</flux:heading>
                        <div class="space-y-2 text-sm">
                            <div><span class="text-zinc-500 w-24 inline-block">Nama</span> <span class="font-medium">{{ $viewing->name }}</span></div>
                            <div><span class="text-zinc-500 w-24 inline-block">NIK</span> <span class="font-medium">{{ $viewing->nik ?? '-' }}</span></div>
                            <div><span class="text-zinc-500 w-24 inline-block">TTL</span> <span class="font-medium">{{ $viewing->pob ?? '-' }}, {{ $viewing->dob?->format('d/m/Y') ?? '-' }}</span></div>
                            <div><span class="text-zinc-500 w-24 inline-block">JK</span> <span class="font-medium">{{ $viewing->gender === 'L' ? 'Laki-laki' : ($viewing->gender === 'P' ? 'Perempuan' : '-') }}</span></div>
                            <div><span class="text-zinc-500 w-24 inline-block">HP</span> <span class="font-medium">{{ $viewing->phone ?? '-' }}</span></div>
                            <div><span class="text-zinc-500 w-24 inline-block">Email</span> <span class="font-medium">{{ $viewing->email ?? '-' }}</span></div>
                        </div>
                    </div>

                    <!-- Alamat -->
                    <div class="space-y-3">
                        <flux:heading size="sm" class="border-b pb-1">Alamat</flux:heading>
                        <div class="space-y-2 text-sm">
                            <div><span class="text-zinc-500 w-24 inline-block">Alamat</span> <span class="font-medium">{{ $viewing->address ?? '-' }}</span></div>
                            <div><span class="text-zinc-500 w-24 inline-block">Desa</span> <span class="font-medium">{{ $viewing->village_name ?? '-' }}</span></div>
                            <div><span class="text-zinc-500 w-24 inline-block">Kecamatan</span> <span class="font-medium">{{ $viewing->district_name ?? '-' }}</span></div>
                            <div><span class="text-zinc-500 w-24 inline-block">Kab/Kota</span> <span class="font-medium">{{ $viewing->regency_name ?? '-' }}</span></div>
                            <div><span class="text-zinc-500 w-24 inline-block">Provinsi</span> <span class="font-medium">{{ $viewing->province_name ?? '-' }}</span></div>
                        </div>
                    </div>

                    <!-- Orang Tua -->
                    <div class="space-y-3">
                        <flux:heading size="sm" class="border-b pb-1">Orang Tua/Wali</flux:heading>
                        <div class="space-y-2 text-sm">
                            <div><span class="text-zinc-500 w-24 inline-block">Ayah</span> <span class="font-medium">{{ $viewing->father_name ?? '-' }}</span></div>
                            <div><span class="text-zinc-500 w-24 inline-block">NIK Ayah</span> <span class="font-medium">{{ $viewing->nik_ayah ?? '-' }}</span></div>
                            <div><span class="text-zinc-500 w-24 inline-block">Ibu</span> <span class="font-medium">{{ $viewing->mother_name ?? '-' }}</span></div>
                            <div><span class="text-zinc-500 w-24 inline-block">NIK Ibu</span> <span class="font-medium">{{ $viewing->nik_ibu ?? '-' }}</span></div>
                            <div><span class="text-zinc-500 w-24 inline-block">Wali</span> <span class="font-medium">{{ $viewing->guardian_name ?? '-' }}</span></div>
                            <div><span class="text-zinc-500 w-24 inline-block">HP Wali</span> <span class="font-medium">{{ $viewing->guardian_phone ?? '-' }}</span></div>
                            <div><span class="text-zinc-500 w-24 inline-block">No. KK</span> <span class="font-medium">{{ $viewing->no_kk ?? '-' }}</span></div>
                            <div><span class="text-zinc-500 w-24 inline-block">No. Akta</span> <span class="font-medium">{{ $viewing->no_akta ?? '-' }}</span></div>
                        </div>
                    </div>

                    <!-- Akademik -->
                    <div class="space-y-3">
                        <flux:heading size="sm" class="border-b pb-1">Data Akademik</flux:heading>
                        <div class="space-y-2 text-sm">
                            <div><span class="text-zinc-500 w-24 inline-block">NISN</span> <span class="font-medium">{{ $viewing->nisn ?? '-' }}</span></div>
                            <div><span class="text-zinc-500 w-24 inline-block">Asal Sekolah</span> <span class="font-medium">{{ $viewing->previous_school ?? '-' }}</span></div>
                            <div><span class="text-zinc-500 w-24 inline-block">Jenjang</span> <span class="font-medium">{{ $viewing->preferredLevel?->name ?? '-' }}</span></div>
                            <div><span class="text-zinc-500 w-24 inline-block">TA</span> <span class="font-medium">{{ $viewing->academicYear?->name ?? '-' }}</span></div>
                            <div><span class="text-zinc-500 w-24 inline-block">Anak ke-</span> <span class="font-medium">{{ $viewing->birth_order ?? '-' }} dari {{ $viewing->total_siblings ?? '-' }}</span></div>
                        </div>
                    </div>
                </div>

                @if($viewing->notes)
                    <div class="p-3 bg-zinc-50 dark:bg-zinc-800 rounded-lg text-sm">
                        <span class="font-semibold">Catatan:</span> {{ $viewing->notes }}
                    </div>
                @endif

                <div class="flex justify-end gap-2 pt-4 border-t">
                    @if($viewing->status === 'pending')
                        <flux:button variant="primary" icon="check" wire:click="accept({{ $viewing->id }})"
                            x-on:click="$flux.modal('detail-modal').close()">Terima</flux:button>
                        <flux:button variant="danger" icon="x-mark"
                            wire:click="reject({{ $viewing->id }})"
                            wire:confirm="Yakin ingin menolak?"
                            x-on:click="$flux.modal('detail-modal').close()">Tolak</flux:button>
                    @endif
                    @if($viewing->status === 'accepted')
                        <flux:button variant="primary" icon="academic-cap"
                            wire:click="openEnroll({{ $viewing->id }})"
                            x-on:click="$flux.modal('detail-modal').close(); $flux.modal('enroll-modal').show()">Enroll ke Kelas</flux:button>
                    @endif
                    <flux:modal.close>
                        <flux:button variant="ghost">Tutup</flux:button>
                    </flux:modal.close>
                </div>
            </div>
        @endif
    </flux:modal>

    <!-- Enroll Modal -->
    <flux:modal name="enroll-modal" class="max-w-md" x-on:enrollment-saved.window="$flux.modal('enroll-modal').close()">
        @if($enrolling)
            <form wire:submit="enroll" class="space-y-6">
                <div>
                    <flux:heading size="lg">Enroll Siswa ke Kelas</flux:heading>
                    <flux:subheading>{{ $enrolling->name }} ({{ $enrolling->registration_number }})</flux:subheading>
                </div>

                <div class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg text-sm space-y-1">
                    <p class="font-semibold text-amber-800 dark:text-amber-200">Proses enrollment akan:</p>
                    <ul class="text-amber-700 dark:text-amber-300 list-disc ml-4 space-y-0.5">
                        <li>Membuat akun User (role: siswa, password: <code class="bg-amber-100 dark:bg-amber-900 px-1 rounded">password</code>)</li>
                        <li>Membuat profil siswa lengkap</li>
                        <li>Menempatkan siswa di kelas yang dipilih</li>
                    </ul>
                </div>

                <flux:select wire:model="enrollClassroomId" label="Pilih Kelas" required>
                    <option value="">-- Pilih Kelas --</option>
                    @foreach($classrooms as $room)
                        <option value="{{ $room->id }}">{{ $room->name }} ({{ $room->level?->name }} - {{ $room->academicYear?->name ?? '' }})</option>
                    @endforeach
                </flux:select>

                @error('enrollClassroomId')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror

                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="ghost">Batal</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary" icon="academic-cap" wire:loading.attr="disabled">
                        <span wire:loading.remove>Enroll Siswa</span>
                        <span wire:loading>Memproses...</span>
                    </flux:button>
                </div>
            </form>
        @endif
    </flux:modal>
</div>
