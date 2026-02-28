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

<div class="p-6 space-y-8 text-slate-900 dark:text-white pb-24 md:pb-6">
    @if (session('success'))
        <x-ui.alert :title="__('Sukses')" icon="o-check-circle" class="bg-emerald-50 text-emerald-800 border-emerald-100" dismissible>
            {{ session('success') }}
        </x-ui.alert>
    @endif

    @if (session('error'))
        <x-ui.alert :title="__('Gagal')" icon="o-exclamation-circle" class="bg-rose-50 text-rose-800 border-rose-100" dismissible>
            {{ session('error') }}
        </x-ui.alert>
    @endif

    <x-ui.header :title="__('Pendaftaran Siswa Baru (PPDB)')" :subtitle="__('Pantau dan proses data calon siswa yang mendaftar melalui portal pendaftaran daring.')" separator>
        <x-slot:actions>
            <x-ui.input 
                wire:model.live.debounce.300ms="search" 
                :placeholder="__('Cari nama atau nomor daftar...')" 
                icon="o-magnifying-glass" 
                class="w-80 font-bold" 
            />
        </x-slot:actions>
    </x-ui.header>

    {{-- Status Dashboard & Navigation --}}
    <x-ui.card shadow padding="false" class="border-none ring-1 ring-slate-100 dark:ring-slate-800 overflow-hidden">
        <div class="p-4 bg-slate-50/50 dark:bg-slate-900/50 flex flex-wrap items-center gap-3">
            <x-ui.button 
                wire:click="$set('filterStatus', '')"
                :label="__('Semua')"
                size="sm"
                class="rounded-full px-5 font-black italic tracking-tight {{ !$filterStatus ? 'bg-slate-900 text-white border-none shadow-lg' : 'btn-ghost hover:bg-slate-100 dark:hover:bg-slate-800' }}"
            >
                <x-slot:append>
                    <span class="ml-2 text-[10px] opacity-60">({{ $statusCounts['all'] }})</span>
                </x-slot:append>
            </x-ui.button>
            <x-ui.button 
                wire:click="$set('filterStatus', 'pending')"
                :label="__('Pending')"
                size="sm"
                class="rounded-full px-5 font-black italic tracking-tight {{ $filterStatus === 'pending' ? 'bg-amber-500 text-white border-none shadow-lg' : 'btn-ghost text-amber-600 hover:bg-amber-50' }}"
            >
                <x-slot:append>
                    <span class="ml-2 text-[10px] opacity-60">({{ $statusCounts['pending'] }})</span>
                </x-slot:append>
            </x-ui.button>
            <x-ui.button 
                wire:click="$set('filterStatus', 'accepted')"
                :label="__('Diterima')"
                size="sm"
                class="rounded-full px-5 font-black italic tracking-tight {{ $filterStatus === 'accepted' ? 'bg-sky-500 text-white border-none shadow-lg' : 'btn-ghost text-sky-600 hover:bg-sky-50' }}"
            >
                <x-slot:append>
                    <span class="ml-2 text-[10px] opacity-60">({{ $statusCounts['accepted'] }})</span>
                </x-slot:append>
            </x-ui.button>
            <x-ui.button 
                wire:click="$set('filterStatus', 'enrolled')"
                :label="__('Telah Enroll')"
                size="sm"
                class="rounded-full px-5 font-black italic tracking-tight {{ $filterStatus === 'enrolled' ? 'bg-emerald-500 text-white border-none shadow-lg' : 'btn-ghost text-emerald-600 hover:bg-emerald-50' }}"
            >
                <x-slot:append>
                    <span class="ml-2 text-[10px] opacity-60">({{ $statusCounts['enrolled'] }})</span>
                </x-slot:append>
            </x-ui.button>
            <x-ui.button 
                wire:click="$set('filterStatus', 'rejected')"
                :label="__('Ditolak')"
                size="sm"
                class="rounded-full px-5 font-black italic tracking-tight {{ $filterStatus === 'rejected' ? 'bg-rose-500 text-white border-none shadow-lg' : 'btn-ghost text-rose-600 hover:bg-rose-50' }}"
            >
                <x-slot:append>
                    <span class="ml-2 text-[10px] opacity-60">({{ $statusCounts['rejected'] }})</span>
                </x-slot:append>
            </x-ui.button>
        </div>

        {{-- Table View --}}
        <x-ui.table :rows="$registrations" :headers="[
            ['key' => 'id_reg', 'label' => __('No. Registrasi')],
            ['key' => 'registrant', 'label' => __('Pendaftar')],
            ['key' => 'preferred_level.name', 'label' => __('Jenjang'), 'class' => 'text-center'],
            ['key' => 'reg_date', 'label' => __('Waktu Daftar'), 'class' => 'text-right'],
            ['key' => 'reg_status', 'label' => __('Status'), 'class' => 'text-center'],
            ['key' => 'actions', 'label' => '']
        ]">
            @scope('cell_id_reg', $reg)
                <div class="flex items-center gap-2">
                    <span class="font-mono text-[10px] bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-800 px-3 py-1 rounded-full font-black text-slate-500 tracking-tighter">{{ $reg->registration_number }}</span>
                </div>
            @endscope

            @scope('cell_registrant', $reg)
                <div class="flex flex-col min-w-0 cursor-pointer group" wire:click="viewDetails({{ $reg->id }})">
                    <div class="font-black text-slate-800 dark:text-white group-hover:text-primary transition-colors italic uppercase tracking-tighter">{{ $reg->name }}</div>
                    <div class="text-[10px] font-bold text-slate-400 font-mono tracking-tighter truncate">{{ $reg->phone ?? $reg->email ?? '-' }}</div>
                </div>
            @endscope

            @scope('cell_reg_date', $reg)
                <div class="text-right">
                    <span class="text-[10px] font-bold text-slate-500 font-mono tracking-tighter italic uppercase">
                        {{ $reg->created_at->format('d/M/Y') }}
                    </span>
                </div>
            @endscope

            @scope('cell_reg_status', $reg)
                <div class="flex justify-center">
                    @php
                        $statusClasses = [
                            'pending' => 'bg-amber-50 text-amber-600 ring-amber-100',
                            'accepted' => 'bg-sky-50 text-sky-600 ring-sky-100',
                            'enrolled' => 'bg-emerald-50 text-emerald-600 ring-emerald-100',
                            'rejected' => 'bg-rose-50 text-rose-600 ring-rose-100',
                        ];
                    @endphp
                    <x-ui.badge 
                        :label="strtoupper($reg->status_label)" 
                        class="border-none font-black italic text-[9px] px-3 py-1 ring-1 shadow-sm {{ $statusClasses[$reg->status] ?? 'bg-slate-50 text-slate-400 ring-slate-100' }}" 
                    />
                </div>
            @endscope

            @scope('cell_actions', $reg)
                <div class="flex items-center justify-end gap-1">
                    <x-ui.button icon="o-eye" wire:click="viewDetails({{ $reg->id }})" class="btn-ghost btn-xs text-slate-400 hover:text-indigo-500" />

                    @if($reg->status === 'pending')
                        <x-ui.button icon="o-check" class="btn-ghost btn-xs text-slate-400 hover:text-emerald-500" wire:click="accept({{ $reg->id }})" />
                        <x-ui.button icon="o-x-mark" class="btn-ghost btn-xs text-slate-300 hover:text-rose-500" wire:confirm="{{ __('Yakin ingin menolak pendaftaran ini?') }}" wire:click="reject({{ $reg->id }})" />
                    @endif

                    @if($reg->status === 'accepted')
                        <x-ui.button icon="o-academic-cap" class="btn-ghost btn-xs text-sky-600 hover:bg-sky-50 animate-pulse font-black italic py-0 h-7" :label="__('ENROLL')" wire:click="openEnroll({{ $reg->id }})" />
                    @endif

                    @if($reg->status !== 'enrolled')
                        <x-ui.button icon="o-trash" class="btn-ghost btn-xs text-slate-300 hover:text-rose-500" wire:confirm="{{ __('Hapus data pendaftaran secara permanen?') }}" wire:click="delete({{ $reg->id }})" />
                    @endif
                </div>
            @endscope
        </x-ui.table>

        <div class="p-6 border-t border-slate-50 dark:border-slate-800">
            {{ $registrations->links() }}
        </div>
    </x-ui.card>

    {{-- Detail Modal --}}
    <x-ui.modal wire:model="detailModal" class="backdrop-blur-sm">
        @if($viewing)
            <div class="p-2">
                <div class="flex items-center gap-4 mb-8">
                    <div class="size-16 rounded-[2rem] bg-indigo-500/10 flex items-center justify-center border border-indigo-100 dark:border-indigo-900 shadow-inner">
                        <x-ui.icon name="o-user" class="size-8 text-indigo-500" />
                    </div>
                    <div>
                        <h2 class="font-black text-2xl text-slate-900 dark:text-white italic uppercase tracking-tighter">{{ $viewing->name }}</h2>
                        <span class="font-mono text-[11px] font-bold text-slate-400 uppercase tracking-widest">{{ $viewing->registration_number }}</span>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-10 max-h-[60vh] overflow-y-auto pr-4 custom-scrollbar">
                    {{-- Personal Information --}}
                    <div class="space-y-6">
                        <div class="flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-2">
                            <x-ui.icon name="o-identification" class="size-4 text-primary" />
                            <h4 class="font-black text-[11px] text-slate-500 uppercase tracking-widest italic">{{ __('Identitas Pendaftar') }}</h4>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label class="text-[10px] font-black italic text-slate-400 uppercase tracking-widest mb-1 block">{{ __('NIK / Nomor Identitas') }}</label>
                                <p class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ $viewing->nik ?: '---' }}</p>
                            </div>
                            <div>
                                <label class="text-[10px] font-black italic text-slate-400 uppercase tracking-widest mb-1 block">{{ __('Tempat, Tgl Lahir') }}</label>
                                <p class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ $viewing->pob ?: '---' }}, {{ $viewing->dob?->format('d/m/Y') ?: '---' }}</p>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="text-[10px] font-black italic text-slate-400 uppercase tracking-widest mb-1 block">{{ __('Telepon / WA') }}</label>
                                    <p class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ $viewing->phone ?: '---' }}</p>
                                </div>
                                <div>
                                    <label class="text-[10px] font-black italic text-slate-400 uppercase tracking-widest mb-1 block">{{ __('Email') }}</label>
                                    <p class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ $viewing->email ?: '---' }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-2 pt-4">
                            <x-ui.icon name="o-map-pin" class="size-4 text-primary" />
                            <h4 class="font-black text-[11px] text-slate-500 uppercase tracking-widest italic">{{ __('Informasi Domisili') }}</h4>
                        </div>
                        <div class="space-y-3">
                            <p class="text-sm font-medium text-slate-600 dark:text-slate-400 bg-slate-50 dark:bg-slate-800/50 p-4 rounded-2xl italic leading-relaxed border border-slate-100 dark:border-slate-700 shadow-inner">
                                {{ $viewing->address ?: __('Alamat tidak terdata') }}
                            </p>
                            <div class="grid grid-cols-2 gap-4 text-[11px] font-bold">
                                <div class="bg-indigo-50/50 dark:bg-indigo-900/10 px-4 py-2 rounded-xl text-indigo-600 border border-indigo-100/50 italic text-center uppercase tracking-tighter">{{ $viewing->village_name ?: '---' }}</div>
                                <div class="bg-emerald-50/50 dark:bg-emerald-900/10 px-4 py-2 rounded-xl text-emerald-600 border border-emerald-100/50 italic text-center uppercase tracking-tighter">{{ $viewing->district_name ?: '---' }}</div>
                            </div>
                        </div>
                    </div>

                    {{-- Academic & Parents --}}
                    <div class="space-y-6">
                        <div class="flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-2">
                            <x-ui.icon name="o-academic-cap" class="size-4 text-primary" />
                            <h4 class="font-black text-[11px] text-slate-500 uppercase tracking-widest italic">{{ __('Data Akademik') }}</h4>
                        </div>
                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="text-[10px] font-black italic text-slate-400 uppercase tracking-widest mb-1 block">{{ __('Nomor NISN') }}</label>
                                    <p class="text-sm font-mono font-black text-slate-900 dark:text-white px-3 py-1 bg-slate-100 dark:bg-slate-800 rounded-lg inline-block">{{ $viewing->nisn ?: '---' }}</p>
                                </div>
                                <div>
                                    <label class="text-[10px] font-black italic text-slate-400 uppercase tracking-widest mb-1 block">{{ __('Target Jenjang') }}</label>
                                    <x-ui.badge :label="$viewing->preferredLevel?->name ?? '---'" class="bg-indigo-50 text-indigo-600 border-none font-black italic text-[10px] tracking-tighter" />
                                </div>
                            </div>
                            <div>
                                <label class="text-[10px] font-black italic text-slate-400 uppercase tracking-widest mb-1 block">{{ __('Asal Satuan Pendidikan') }}</label>
                                <p class="text-sm font-bold text-slate-700 dark:text-slate-300 italic">{{ $viewing->previous_school ?: '---' }}</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-2 pt-4">
                            <x-ui.icon name="o-users" class="size-4 text-primary" />
                            <h4 class="font-black text-[11px] text-slate-500 uppercase tracking-widest italic">{{ __('Orang Tua / Wali') }}</h4>
                        </div>
                        <div class="space-y-4">
                            <div class="p-4 bg-white dark:bg-slate-800 border-l-4 border-slate-200 dark:border-slate-700 rounded-r-2xl shadow-sm space-y-3">
                                <div class="flex justify-between items-center text-xs">
                                    <span class="text-slate-400 font-bold italic uppercase tracking-widest text-[9px]">{{ __('Nama Ayah Kandung') }}</span>
                                    <span class="font-black text-slate-700 dark:text-slate-200">{{ $viewing->father_name ?: '---' }}</span>
                                </div>
                                <div class="flex justify-between items-center text-xs">
                                    <span class="text-slate-400 font-bold italic uppercase tracking-widest text-[9px]">{{ __('Nama Ibu Kandung') }}</span>
                                    <span class="font-black text-slate-700 dark:text-slate-200">{{ $viewing->mother_name ?: '---' }}</span>
                                </div>
                                <div class="h-px bg-slate-50 dark:bg-slate-700 my-2"></div>
                                <div class="flex justify-between items-center text-xs">
                                    <span class="text-slate-400 font-bold italic uppercase tracking-widest text-[9px]">{{ __('Nomor Kartu Keluarga') }}</span>
                                    <span class="font-mono font-bold text-indigo-500">{{ $viewing->no_kk ?: '---' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 mt-10 pt-6 border-t border-slate-50 dark:border-slate-800">
                    <x-ui.button :label="__('Tutup')" @click="$set('detailModal', false)" class="btn-ghost font-bold py-0" />
                    @if($viewing->status === 'pending')
                        <x-ui.button :label="__('Tolak Pendaftaran')" icon="o-x-mark" class="btn-ghost text-rose-500 font-bold italic" wire:click="reject({{ $viewing->id }})" wire:confirm="{{ __('Yakin ingin menolak pendaftaran ini?') }}" @click="$set('detailModal', false)" />
                        <x-ui.button :label="__('Terima & Acc')" icon="o-check" class="btn-primary shadow-xl shadow-primary/20" wire:click="accept({{ $viewing->id }})" @click="$set('detailModal', false)" />
                    @endif
                    @if($viewing->status === 'accepted')
                        <x-ui.button :label="__('Lanjutkan ke Enroll')" icon="o-academic-cap" class="btn-primary shadow-xl shadow-primary/20" wire:click="openEnroll({{ $viewing->id }})" @click="$set('detailModal', false)" />
                    @endif
                </div>
            </div>
        @endif
    </x-ui.modal>

    {{-- Enroll Modal --}}
    <x-ui.modal wire:model="enrollModal" class="backdrop-blur-sm">
        @if($enrolling)
            <div class="p-2">
                <div class="flex items-center gap-4 mb-8">
                    <div class="size-16 rounded-[2rem] bg-emerald-500/10 flex items-center justify-center border border-emerald-100 dark:border-emerald-900 shadow-inner">
                        <x-ui.icon name="o-academic-cap" class="size-8 text-emerald-500" />
                    </div>
                    <div>
                        <h2 class="font-black text-2xl text-slate-900 dark:text-white italic uppercase tracking-tighter">{{ __('Enrollment Siswa') }}</h2>
                        <span class="font-bold text-slate-400 italic text-xs uppercase tracking-widest">{{ $enrolling->name }}</span>
                    </div>
                </div>
                
                <form wire:submit="enroll" class="space-y-8">
                    <div class="p-6 rounded-[2rem] bg-indigo-50 dark:bg-indigo-900/10 border border-indigo-100 dark:border-indigo-800/50 flex gap-4 shadow-inner">
                        <x-ui.icon name="o-information-circle" class="size-6 text-indigo-500 shrink-0" />
                        <div class="space-y-1">
                            <p class="text-[11px] font-black text-indigo-600 uppercase tracking-widest italic">{{ __('Prosedur Sistem Otomatis') }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium leading-relaxed italic">
                                {{ __('Sistem akan secara otomatis membuat akun login (User) dan mengaitkannya dengan Profil Siswa yang akan dihubungkan ke kelas terpilih berikut ini.') }}
                            </p>
                        </div>
                    </div>

                    <x-ui.select 
                        wire:model="enrollClassroomId" 
                        :label="__('Target Penempatan Kelas')" 
                        :options="$classrooms" 
                        :placeholder="__('-- Pilih Kelas Definitif --')"
                        class="font-black italic uppercase tracking-tighter"
                    />

                    <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-50 dark:border-slate-800">
                        <x-ui.button :label="__('Batalkan')" @click="$set('enrollModal', false)" class="btn-ghost font-bold" />
                        <x-ui.button :label="__('Konfirmasi & Enroll')" type="submit" class="btn-primary shadow-xl shadow-primary/20 px-10" spinner="enroll" />
                    </div>
                </form>
            </div>
        @endif
    </x-ui.modal>
</div>
