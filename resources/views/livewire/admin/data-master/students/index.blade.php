<?php

declare(strict_types=1);

use App\Models\Classroom;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new #[Layout('components.admin.layouts.app')] class extends Component {
    use WithFileUploads, WithPagination;

    public string $search = '';
    public bool $studentModal = false;
    public bool $importModal = false;
    public bool $periodicModal = false;
    public bool $detailModal = false;
    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';
    public ?int $filter_classroom_id = null;
    public ?int $filter_level_id = null;

    // Form fields
    public string $name = '';

    public string $email = '';

    public string $nis = '';

    public string $nisn = '';

    public string $phone = '';

    public string $address = '';

    public string $dob = '';

    public string $pob = '';

    public ?int $classroom_id = null;

    // New fields
    public $photo;

    public string $father_name = '';

    public string $mother_name = '';

    public string $guardian_name = '';

    public string $guardian_phone = '';

    public ?int $birth_order = null;

    public ?int $total_siblings = null;

    public string $previous_school = '';

    public string $status = 'baru';

    public string $nik = '';

    public string $nik_ayah = '';

    public string $nik_ibu = '';

    public string $no_kk = '';

    public string $no_akta = '';

    // Periodic Data fields
    public float $weight = 0;

    public float $height = 0;

    public float $head_circumference = 0;

    public int $semester = 1;

    public ?int $current_academic_year_id = null;

    public ?User $editing = null;

    public ?User $viewing = null;

    public ?string $existingPhoto = null;

    public bool $hasExistingPeriodicData = false;

    public ?string $periodicDataLastUpdated = null;

    public $importFile;
    public $importErrors = [];

    public function rules(): array
    {
        $profileId = $this->editing?->latestProfile?->profileable_id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,' . ($this->editing->id ?? 'NULL')],
            'nis' => ['nullable', 'string', $this->nis ? 'unique:student_profiles,nis,' . $profileId : ''],
            'nisn' => ['nullable', 'string', $this->nisn ? 'unique:student_profiles,nisn,' . $profileId : ''],
            'phone' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'dob' => ['required', 'date'],
            'pob' => ['required', 'string'],
            'classroom_id' => ['nullable', 'exists:classrooms,id'],
            'photo' => ['nullable', 'image', 'max:1024'], // 1MB Max
            'father_name' => ['nullable', 'string', 'max:255'],
            'mother_name' => ['nullable', 'string', 'max:255'],
            'guardian_name' => ['nullable', 'string', 'max:255'],
            'guardian_phone' => ['nullable', 'string', 'max:20'],
            'birth_order' => ['nullable', 'integer', 'min:1'],
            'total_siblings' => ['nullable', 'integer', 'min:1'],
            'previous_school' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:baru,mutasi,naik_kelas,lulus,keluar'],
            'nik' => ['required', 'string', 'max:16', 'unique:student_profiles,nik,' . $profileId],
            'nik_ayah' => ['required', 'string', 'max:16'],
            'nik_ibu' => ['required', 'string', 'max:16'],
            'no_kk' => ['required', 'string', 'max:16'],
            'no_akta' => ['required', 'string', 'max:255'],
        ];
    }

    public function mount(): void
    {
        $this->current_academic_year_id = \App\Models\AcademicYear::where('is_active', true)->first()?->id;
    }

    public function save(): void
    {
        $this->validate();

        DB::transaction(function () {
            $photoPath = $this->existingPhoto;
            if ($this->photo) {
                // Delete old photo if exists
                if ($this->existingPhoto) {
                    Storage::disk('public')->delete($this->existingPhoto);
                }
                $photoPath = $this->photo->store('photos', 'public');
            }

            $profileData = [
                'nis' => $this->nis ?: null,
                'nisn' => $this->nisn ?: null,
                'phone' => $this->phone ?: null,
                'address' => $this->address ?: null,
                'dob' => $this->dob ?: null,
                'pob' => $this->pob,
                'photo' => $photoPath,
                'father_name' => $this->father_name ?: null,
                'mother_name' => $this->mother_name ?: null,
                'guardian_name' => $this->guardian_name ?: null,
                'guardian_phone' => $this->guardian_phone ?: null,
                'classroom_id' => $this->classroom_id,
                'birth_order' => $this->birth_order,
                'total_siblings' => $this->total_siblings,
                'previous_school' => $this->previous_school ?: null,
                'status' => $this->status,
                'nik' => $this->nik,
                'nik_ayah' => $this->nik_ayah,
                'nik_ibu' => $this->nik_ibu,
                'no_kk' => $this->no_kk,
                'no_akta' => $this->no_akta,
            ];

            if ($this->editing) {
                $this->editing->update([
                    'name' => $this->name,
                    'email' => $this->email,
                ]);

                $profile = $this->editing->latestProfile?->profileable;

                if ($profile) {
                    $profile->update($profileData);
                } else {
                    $studentProfile = StudentProfile::create($profileData);
                    $this->editing->profiles()->create([
                        'profileable_id' => $studentProfile->id,
                        'profileable_type' => StudentProfile::class,
                    ]);
                }
            } else {
                $user = User::create([
                    'name' => $this->name,
                    'email' => $this->email,
                    'password' => Hash::make('password'),
                    'role' => 'siswa',
                    'is_active' => true,
                ]);

                $studentProfile = StudentProfile::create($profileData);

                $user->profiles()->create([
                    'profileable_id' => $studentProfile->id,
                    'profileable_type' => StudentProfile::class,
                ]);
            }
        });

        $this->reset(['name', 'email', 'nis', 'nisn', 'phone', 'address', 'dob', 'pob', 'classroom_id', 'photo', 'father_name', 'mother_name', 'guardian_name', 'guardian_phone', 'birth_order', 'total_siblings', 'previous_school', 'status', 'nik', 'nik_ayah', 'nik_ibu', 'no_kk', 'no_akta', 'editing', 'viewing', 'existingPhoto']);

        session()->flash('success', 'Data siswa berhasil disimpan!');
        $this->dispatch('student-saved');
        $this->studentModal = false;
    }

    public function edit(User $user): void
    {
        $this->editing = $user;
        $this->name = $user->name;
        $this->email = $user->email;

        $profile = $user->latestProfile?->profileable;
        $this->nis = $profile?->nis ?? '';
        $this->nisn = $profile?->nisn ?? '';
        $this->phone = $profile?->phone ?? '';
        $this->address = $profile?->address ?? '';
        $this->dob = $profile?->dob ? $profile->dob->format('Y-m-d') : '';
        $this->pob = $profile?->pob ?? '';
        $this->existingPhoto = $profile?->photo;
        $this->father_name = $profile?->father_name ?? '';
        $this->mother_name = $profile?->mother_name ?? '';
        $this->guardian_name = $profile?->guardian_name ?? '';
        $this->guardian_phone = $profile?->guardian_phone ?? '';
        $this->classroom_id = $profile?->classroom_id;
        $this->birth_order = $profile?->birth_order;
        $this->total_siblings = $profile?->total_siblings;
        $this->previous_school = $profile?->previous_school ?? '';
        $this->status = $profile?->status ?? 'baru';
        $this->nik = $profile?->nik ?? '';
        $this->nik_ayah = $profile?->nik_ayah ?? '';
        $this->nik_ibu = $profile?->nik_ibu ?? '';
        $this->no_kk = $profile?->no_kk ?? '';
        $this->no_akta = $profile?->no_akta ?? '';

        $this->studentModal = true;
    }

    public function viewDetails(User $user): void
    {
        $this->viewing = $user;
        $this->detailModal = true;
    }

    public function openPeriodic(User $user): void
    {
        $this->editing = $user;

        // Preload existing periodic data for current academic year and semester
        $profile = $user->latestProfile?->profileable;
        if ($profile) {
            $existingRecord = \App\Models\StudentPeriodicRecord::where('student_profile_id', $profile->id)->where('academic_year_id', $this->current_academic_year_id)->where('semester', $this->semester)->first();

            if ($existingRecord) {
                $this->weight = $existingRecord->weight;
                $this->height = $existingRecord->height;
                $this->head_circumference = $existingRecord->head_circumference;
                $this->hasExistingPeriodicData = true;
                $this->periodicDataLastUpdated = $existingRecord->updated_at->diffForHumans();
            } else {
                // Reset to default if no existing record
                $this->weight = 0;
                $this->height = 0;
                $this->head_circumference = 0;
                $this->hasExistingPeriodicData = false;
                $this->periodicDataLastUpdated = null;
            }
        }

        $this->periodicModal = true;
    }

    public function updatedSemester(): void
    {
        if ($this->editing) {
            $profile = $this->editing->latestProfile?->profileable;
            if ($profile) {
                $existingRecord = \App\Models\StudentPeriodicRecord::where('student_profile_id', $profile->id)->where('academic_year_id', $this->current_academic_year_id)->where('semester', $this->semester)->first();

                if ($existingRecord) {
                    $this->weight = $existingRecord->weight;
                    $this->height = $existingRecord->height;
                    $this->head_circumference = $existingRecord->head_circumference;
                    $this->hasExistingPeriodicData = true;
                    $this->periodicDataLastUpdated = $existingRecord->updated_at->diffForHumans();
                } else {
                    $this->weight = 0;
                    $this->height = 0;
                    $this->head_circumference = 0;
                    $this->hasExistingPeriodicData = false;
                    $this->periodicDataLastUpdated = null;
                }
            }
        }
    }

    public function updatedFilterLevelId(): void
    {
        $this->filter_classroom_id = null;
        $this->resetPage();
    }

    public function updatedFilterClassroomId(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function createNew(): void
    {
        $this->reset(['name', 'email', 'nis', 'nisn', 'phone', 'address', 'dob', 'pob', 'classroom_id', 'photo', 'father_name', 'mother_name', 'guardian_name', 'guardian_phone', 'birth_order', 'total_siblings', 'previous_school', 'status', 'nik', 'nik_ayah', 'nik_ibu', 'no_kk', 'no_akta', 'editing', 'existingPhoto']);
        $this->resetValidation();
        $this->studentModal = true;
    }

    public function savePeriodic(int $studentProfileId): void
    {
        if (!$studentProfileId) {
            session()->flash('error', 'Data profil siswa tidak ditemukan. Silakan edit siswa terlebih dahulu untuk membuat profil.');
            return;
        }

        $this->validate([
            'weight' => 'required|numeric|min:0',
            'height' => 'required|numeric|min:0',
            'head_circumference' => 'required|numeric|min:0',
            'semester' => 'required|integer|in:1,2',
        ]);

        \App\Models\StudentPeriodicRecord::updateOrCreate(
            [
                'student_profile_id' => $studentProfileId,
                'academic_year_id' => $this->current_academic_year_id,
                'semester' => $this->semester,
            ],
            [
                'weight' => $this->weight,
                'height' => $this->height,
                'head_circumference' => $this->head_circumference,
                'recorded_by' => auth()->id(),
            ],
        );

        $this->reset(['weight', 'height', 'head_circumference', 'semester', 'hasExistingPeriodicData', 'periodicDataLastUpdated']);

        session()->flash('success', 'Data periodik berhasil disimpan!');
        $this->dispatch('periodic-saved');
    }

    public function delete(User $user): void
    {
        if ($user->latestProfile) {
            $profile = $user->latestProfile->profileable;
            if ($profile && $profile->photo) {
                Storage::disk('public')->delete($profile->photo);
            }

            if ($profile) {
                $profile->delete();
            }

            $user->latestProfile->delete();
        }
        $user->delete();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function export(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\StudentsExport($this->search, $this->filter_classroom_id, $this->filter_level_id), 'data_siswa_' . now()->format('Y-m-d_H-i-s') . '.xlsx');
    }

    public function downloadTemplate(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\StudentsTemplateExport(), 'template_import_siswa.xlsx');
    }

    public function import(): void
    {
        $this->validate([
            'importFile' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        $this->importErrors = [];

        try {
            \Maatwebsite\Excel\Facades\Excel::import(new \App\Imports\StudentsImport(), $this->importFile);

            $this->dispatch('close-modal', 'import-modal');
            $this->reset(['importFile', 'importErrors']);
            session()->flash('success', 'Data siswa berhasil diimport!');
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            foreach ($failures as $failure) {
                $this->importErrors[] = [
                    'row' => $failure->row(),
                    'errors' => $failure->errors(),
                ];
            }
        } catch (\Illuminate\Database\QueryException $e) {
            // Parse database constraint violations
            $errorMessage = $e->getMessage();

            if (str_contains($errorMessage, 'UNIQUE constraint failed: student_profiles.nisn')) {
                $this->importErrors[] = [
                    'row' => 'Database',
                    'errors' => ['NISN sudah terdaftar di sistem. Periksa data duplikat di file Excel Anda.'],
                ];
            } elseif (str_contains($errorMessage, 'UNIQUE constraint failed: student_profiles.nik')) {
                $this->importErrors[] = [
                    'row' => 'Database',
                    'errors' => ['NIK sudah terdaftar di sistem. Periksa data duplikat di file Excel Anda.'],
                ];
            } elseif (str_contains($errorMessage, 'UNIQUE constraint failed: student_profiles.nis')) {
                $this->importErrors[] = [
                    'row' => 'Database',
                    'errors' => ['NIS sudah terdaftar di sistem. Periksa data duplikat di file Excel Anda.'],
                ];
            } elseif (str_contains($errorMessage, 'UNIQUE constraint failed: users.email')) {
                $this->importErrors[] = [
                    'row' => 'Database',
                    'errors' => ['Email sudah terdaftar di sistem. Periksa data duplikat di file Excel Anda.'],
                ];
            } else {
                $this->importErrors[] = [
                    'row' => 'Database',
                    'errors' => ['Terjadi kesalahan database: Data duplikat atau tidak valid.'],
                ];
            }
        } catch (\Exception $e) {
            $this->importErrors[] = [
                'row' => 'Sistem',
                'errors' => [$e->getMessage()],
            ];
        }
    }

    public function clearImport(): void
    {
        $this->reset(['importFile', 'importErrors']);
    }

    public function with(): array
    {
        return [
            'students' => User::where('role', 'siswa')
                ->with(['latestProfile.profileable.classroom.level'])
                ->when(
                    $this->search,
                    fn($q) => $q
                        ->where('name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%")
                        ->orWhereHas('latestProfile', fn($pq) => $pq->whereHasMorph('profileable', [StudentProfile::class], fn($sq) => $sq->where('nis', 'like', "%{$this->search}%")->orWhere('nisn', 'like', "%{$this->search}%"))),
                )
                ->when($this->filter_classroom_id, fn($q) => $q->whereHas('latestProfile', fn($pq) => $pq->whereHasMorph('profileable', [StudentProfile::class], fn($sq) => $sq->where('classroom_id', $this->filter_classroom_id))))
                ->when($this->filter_level_id, fn($q) => $q->whereHas('latestProfile', fn($pq) => $pq->whereHasMorph('profileable', [StudentProfile::class], fn($sq) => $sq->whereHas('classroom', fn($cq) => $cq->where('level_id', $this->filter_level_id)))))
                ->orderBy($this->sortField === 'name' ? 'name' : ($this->sortField === 'email' ? 'email' : 'created_at'), $this->sortDirection)
                ->paginate(15),
            'classrooms' => Classroom::with(['academicYear', 'level'])->get(),
            'filter_classrooms' => Classroom::with(['academicYear', 'level'])
                ->when($this->filter_level_id, fn($q) => $q->where('level_id', $this->filter_level_id))
                ->get(),
            'levels' => \App\Models\Level::all(),
        ];
    }
}; ?>

<div class="p-6 space-y-6 text-slate-900 dark:text-white">
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

    <x-ui.header :title="__('Manajemen Siswa')" :subtitle="__('Kelola data murid, profil, dan penempatan kelas.')">
        <x-slot:actions>
            <div class="flex items-center gap-3">
                <x-ui.input wire:model.live.debounce.300ms="search" :placeholder="__('Cari siswa...')" icon="o-magnifying-glass" sm class="w-64" clearable />
                <x-ui.button :label="__('Import')" icon="o-arrow-up-tray" wire:click="$set('importModal', true)" ghost sm />
                <x-ui.button :label="__('Tambah Siswa')" icon="o-plus" wire:click="createNew" class="btn-primary" />
            </div>
        </x-slot:actions>
    </x-ui.header>

    <x-ui.card shadow padding="false">
        <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex flex-col md:flex-row gap-4 items-center justify-between bg-slate-50/50 dark:bg-slate-900/50">
            <div class="flex flex-1 gap-3 w-full md:w-auto">
                <div class="w-full md:w-48">
                    <x-ui.select 
                        wire:model.live="filter_level_id" 
                        :placeholder="__('Semua Tingkat')"
                        :options="$levels"
                        sm
                    />
                </div>
                <div class="w-full md:w-48">
                    <x-ui.select 
                        wire:model.live="filter_classroom_id" 
                        :placeholder="__('Semua Kelas')"
                        :options="$filter_classrooms"
                        sm
                    />
                </div>
            </div>

            <div class="flex gap-2 w-full md:w-auto justify-end">
                <x-ui.button wire:click="export" icon="o-arrow-down-tray" :label="__('Export XLSX')" ghost sm />
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left border-collapse">
                <thead class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider border-b border-slate-100 dark:border-slate-800 bg-slate-50/30 dark:bg-slate-900/30">
                    <tr>
                        <th class="px-6 py-4 font-black">{{ __('Siswa') }}</th>
                        <th class="px-6 py-4 font-black">{{ __('NIS/NISN') }}</th>
                        <th class="px-6 py-4 font-black">{{ __('Kelas') }}</th>
                        <th class="px-6 py-4 font-black">{{ __('Orang Tua/Wali') }}</th>
                        <th class="px-6 py-4 font-black text-right">{{ __('Aksi') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($students as $student)
                        @php $profile = $student->latestProfile?->profileable; @endphp
                        <tr class="group hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition-all duration-200">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-4 cursor-pointer" wire:click="viewDetails({{ $student->id }})">
                                    <div class="relative">
                                        <x-ui.avatar 
                                            :image="($profile?->photo && Storage::disk('public')->exists($profile->photo)) ? '/storage/'.$profile->photo : null" 
                                            fallback="o-user" 
                                            class="!w-11 !h-11 rounded-xl group-hover:ring-2 group-hover:ring-primary/30 transition-all shadow-sm border border-slate-100 dark:border-slate-800"
                                        />
                                        @if($student->is_active)
                                            <div class="absolute -bottom-1 -right-1 w-3.5 h-3.5 bg-emerald-500 border-2 border-white dark:border-slate-900 rounded-full"></div>
                                        @endif
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="font-bold text-slate-900 dark:text-white group-hover:text-primary transition-colors">{{ $student->name }}</span>
                                        <span class="text-[10px] text-slate-400 font-mono tracking-tighter">{{ $student->email ?? '-' }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="text-sm font-semibold text-slate-700 dark:text-slate-300 font-mono tracking-tight italic">{{ $profile?->nis ?? '-' }}</span>
                                    <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">NISN: {{ $profile?->nisn ?? '-' }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                @if ($profile?->classroom)
                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-primary/5 text-primary border border-primary/10">
                                        <span class="text-[10px] font-black tracking-widest uppercase">{{ $profile->classroom->name }}</span>
                                    </div>
                                @else
                                    <span class="text-[10px] text-rose-500 font-black uppercase italic tracking-widest bg-rose-50 dark:bg-rose-950/30 px-2 py-0.5 rounded">{{ __('Belum ada kelas') }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ $profile?->father_name ?: ($profile?->mother_name ?: ($profile?->guardian_name ?: '-')) }}</span>
                                    <div class="flex items-center gap-1 mt-0.5">
                                        <x-ui.icon name="o-phone" class="!w-2.5 !h-2.5 text-slate-400" />
                                        <span class="text-[10px] text-slate-400 font-mono italic">{{ $profile?->guardian_phone ?: ($profile?->phone ?: '-') }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                    <x-ui.button icon="o-chart-bar" wire:click="openPeriodic({{ $student->id }})" ghost sm class="hover:text-primary" />
                                    <x-ui.button icon="o-pencil-square" wire:click="edit({{ $student->id }})" ghost sm class="hover:text-indigo-600" />
                                    <x-ui.button 
                                        icon="o-trash" 
                                        class="text-red-500 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/10" 
                                        wire:confirm="{{ __('Yakin ingin menghapus siswa ini?') }}"
                                        wire:click="delete({{ $student->id }})"
                                        ghost sm 
                                    />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-20 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="p-4 rounded-full bg-slate-50 dark:bg-slate-900">
                                        <x-ui.icon name="o-user-group" class="!w-10 !h-10 text-slate-300" />
                                    </div>
                                    <div class="text-slate-400 font-medium italic text-sm">
                                        {{ __('Belum ada data siswa yang ditemukan.') }}
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>

    <div class="mt-4">
        {{ $students->links() }}
    </div>

    <x-ui.modal wire:model="studentModal" persistent maxWidth="max-w-4xl">
        <div>
            <div class="mb-8 flex items-start justify-between">
            <div>
                <x-ui.header :title="$editing ? __('Edit Profil Siswa') : __('Tambah Siswa Baru')" :subtitle="__('Lengkapi data identitas dan akademik siswa.')" separator />
            </div>

            <div class="flex flex-col items-center gap-3">
                <div class="relative group">
                    <x-ui.file wire:model="photo" accept="image/*" hidden />
                    <div class="cursor-pointer" onclick="document.querySelector('input[type=file]').click()">
                        <x-ui.avatar 
                            :image="$photo ? $photo->temporaryUrl() : ($existingPhoto ? '/storage/'.$existingPhoto : null)" 
                            fallback="o-camera" 
                            class="!w-24 !h-24 rounded-2xl ring-4 ring-primary/5 shadow-xl transition-all hover:scale-105 active:scale-95"
                        />
                    </div>
                </div>
                <div class="text-[10px] font-black uppercase text-slate-400 tracking-widest">{{ __('Foto Profil (Max 1MB)') }}</div>
            </div>
        </div>

        <form wire:submit="save" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="space-y-5">
                    <div class="text-[11px] font-black uppercase text-slate-400 tracking-widest border-b border-slate-100 dark:border-slate-800 pb-2">{{ __('Identitas Siswa') }}</div>

                    <div class="grid grid-cols-2 gap-4">
                        <x-ui.input wire:model="name" :label="__('Nama Lengkap')" required />
                        <x-ui.input wire:model="email" :label="__('Email')" type="email" required />
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <x-ui.input wire:model="nis" :label="__('NIS')" />
                        <x-ui.input wire:model="nisn" :label="__('NISN')" />
                        <x-ui.input wire:model="nik" :label="__('NIK Siswa')" :placeholder="__('16 digit NIK')" required />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <x-ui.input wire:model="no_kk" :label="__('No. Kartu Keluarga')" required />
                        <x-ui.input wire:model="no_akta" :label="__('No. Akta Kelahiran')" required />
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <x-ui.input wire:model="pob" :label="__('Tempat Lahir')" required />
                        <x-ui.input wire:model="dob" :label="__('Tanggal Lahir')" type="date" required />
                        <x-ui.input wire:model="phone" :label="__('No. Telepon / WA')" />
                    </div>

                    <x-ui.textarea wire:model="address" :label="__('Alamat')" rows="2" />

                    <div class="grid grid-cols-2 gap-4">
                        <x-ui.input type="number" wire:model="birth_order" :label="__('Anak Ke-')" />
                        <x-ui.input type="number" wire:model="total_siblings" :label="__('Dari ... Bersaudara')" />
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <x-ui.select wire:model="classroom_id" :label="__('Kelas')" :options="$classrooms" :placeholder="__('Pilih Kelas')" />
                        <x-ui.select 
                            wire:model="status" 
                            :label="__('Status Siswa')" 
                            :options="[
                                ['id' => 'baru', 'name' => __('Baru')],
                                ['id' => 'mutasi', 'name' => __('Mutasi / Pindahan')],
                                ['id' => 'naik_kelas', 'name' => __('Naik Kelas')],
                                ['id' => 'lulus', 'name' => __('Lulus')],
                                ['id' => 'keluar', 'name' => __('Keluar')],
                            ]"
                            required
                        />
                        <x-ui.input wire:model="previous_school" :label="__('Asal Sekolah')" />
                    </div>
                </div>

                <div class="space-y-5">
                    <div class="text-[11px] font-black uppercase text-slate-400 tracking-widest border-b border-slate-100 dark:border-slate-800 pb-2">{{ __('Data Orang Tua / Wali') }}</div>

                    <div class="grid grid-cols-2 gap-4">
                        <x-ui.input wire:model="father_name" :label="__('Nama Ayah')" required />
                        <x-ui.input wire:model="nik_ayah" :label="__('NIK Ayah')" required />
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <x-ui.input wire:model="mother_name" :label="__('Nama Ibu')" required />
                        <x-ui.input wire:model="nik_ibu" :label="__('NIK Ibu')" required />
                    </div>

                    <div class="pt-6 space-y-4 bg-slate-50/50 dark:bg-slate-900/50 p-4 rounded-xl border border-slate-100 dark:border-slate-800">
                        <div class="text-[10px] font-black uppercase text-slate-400 tracking-widest">{{ __('Kontak Wali (Jika Ada)') }}</div>
                        <div class="grid grid-cols-2 gap-4">
                            <x-ui.input wire:model="guardian_name" :label="__('Nama Wali')" />
                            <x-ui.input wire:model="guardian_phone" :label="__('No. Telp Wali')" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-4 border-t border-slate-100 dark:border-slate-800">
                <x-ui.button :label="__('Batal')" ghost @click="show = false" />
                <x-ui.button :label="__('Simpan')" type="submit" class="btn-primary" spinner="save" />
            </div>
        </form>
        </div>
    </x-ui.modal>

    {{-- Modals --}}
    @include('livewire.admin.data-master.students.partials.import-modal')
    @include('livewire.admin.data-master.students.partials.periodic-modal')
    @include('livewire.admin.data-master.students.partials.detail-modal')
</div>
