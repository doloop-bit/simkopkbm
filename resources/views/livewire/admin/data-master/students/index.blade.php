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

<div class="p-6">
    @if (session('success'))
        <x-alert title="Sukses" icon="o-check-circle" class="alert-success mb-6">
            {{ session('success') }}
        </x-alert>
    @endif

    @if (session('error'))
        <x-alert title="Gagal" icon="o-exclamation-circle" class="alert-error mb-6">
            {{ session('error') }}
        </x-alert>
    @endif

    <x-header title="Manajemen Siswa" subtitle="Kelola data murid, profil, dan penempatan kelas.">
        <x-slot:actions>
            <x-input wire:model.live.debounce.300ms="search" placeholder="Cari siswa..." icon="o-magnifying-glass" class="w-64" clearable />
            <x-button label="Import" icon="o-arrow-up-tray" wire:click="$set('importModal', true)" />
            <x-button label="Tambah Siswa" icon="o-plus" wire:click="createNew" class="btn-primary" />
        </x-slot:actions>
    </x-header>

    <div
        class="border rounded-xl border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm">
        <div class="p-4 border-b border-base-200 flex flex-col md:flex-row gap-4 items-center justify-between bg-base-200/50">
            <div class="flex flex-1 gap-3 w-full md:w-auto">
                <div class="w-full md:w-48">
                    <x-select 
                        wire:model.live="filter_level_id" 
                        placeholder="Semua Tingkat"
                        :options="$levels"
                    />
                </div>
                <div class="w-full md:w-48">
                    <x-select 
                        wire:model.live="filter_classroom_id" 
                        placeholder="Semua Kelas"
                        :options="$filter_classrooms"
                    />
                </div>
            </div>

            <div class="flex gap-2 w-full md:w-auto justify-end">
                <x-button wire:click="export" icon="o-arrow-down-tray" label="Export XLSX" outline />
            </div>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th class="bg-base-200">
                        <x-button label="Siswa" wire:click="sortBy('name')" ghost sm class="!px-0" />
                        @if ($sortField === 'name')
                            <x-icon :name="$sortDirection === 'asc' ? 'o-chevron-up' : 'o-chevron-down'" class="size-3" />
                        @endif
                    </th>
                    <th class="bg-base-200">NIS/NISN</th>
                    <th class="bg-base-200">Kelas</th>
                    <th class="bg-base-200">Orang Tua/Wali</th>
                    <th class="bg-base-200 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($students as $student)
                    @php $profile = $student->latestProfile?->profileable; @endphp
                    <tr wire:key="{{ $student->id }}" class="hover">
                        <td>
                            <div class="flex items-center gap-3 cursor-pointer" wire:click="viewDetails({{ $student->id }})">
                                <x-avatar 
                                    image="{{ ($profile?->photo && Storage::disk('public')->exists($profile->photo)) ? '/storage/'.$profile->photo : null }}" 
                                    fallback="o-user" 
                                    class="!w-10 !h-10"
                                />
                                <div class="flex flex-col">
                                    <span class="font-bold">{{ $student->name }}</span>
                                    <span class="text-xs opacity-60">{{ $student->email ?? '-' }}</span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="flex flex-col">
                                <span class="text-sm font-medium">{{ $profile?->nis ?? '-' }}</span>
                                <span class="text-[10px] uppercase tracking-wider opacity-60">NISN: {{ $profile?->nisn ?? '-' }}</span>
                            </div>
                        </td>
                        <td>
                            @if ($profile?->classroom)
                                <x-badge :label="$profile->classroom->name" class="badge-neutral badge-sm" />
                            @else
                                <span class="text-xs text-error italic">Belum ada kelas</span>
                            @endif
                        </td>
                        <td>
                            <div class="flex flex-col text-sm">
                                <span class="font-medium">{{ $profile?->father_name ?: ($profile?->mother_name ?: ($profile?->guardian_name ?: '-')) }}</span>
                                <span class="text-xs opacity-60">{{ $profile?->guardian_phone ?: ($profile?->phone ?: '-') }}</span>
                            </div>
                        </td>
                        <td class="text-right">
                            <div class="flex justify-end gap-1">
                                <x-button icon="o-chart-bar" wire:click="openPeriodic({{ $student->id }})" ghost sm tooltip="Data Periodik" />
                                <x-button icon="o-pencil-square" wire:click="edit({{ $student->id }})" ghost sm tooltip="Edit Siswa" />
                                <x-button 
                                    icon="o-trash" 
                                    wire:confirm="Yakin ingin menghapus siswa ini?"
                                    wire:click="delete({{ $student->id }})"
                                    ghost sm class="text-error" 
                                    tooltip="Hapus Siswa" 
                                />
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-8 text-center opacity-50 italic">
                            Belum ada data siswa ditemukan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $students->links() }}
    </div>

    <x-modal wire:model="studentModal" class="backdrop-blur">
        <div class="mb-5 flex items-start justify-between">
            <div>
                <x-header :title="$editing ? 'Edit Profil Siswa' : 'Tambah Siswa Baru'" subtitle="Lengkapi data identitas dan akademik siswa." separator />
            </div>

            <div class="flex flex-col items-center gap-2">
                <div class="relative group">
                    <x-file wire:model="photo" accept="image/*" crop-after-change hidden />
                    <div class="cursor-pointer" onclick="document.querySelector('input[type=file]').click()">
                        <x-avatar 
                            image="{{ $photo ? $photo->temporaryUrl() : ($existingPhoto ? '/storage/'.$existingPhoto : null) }}" 
                            fallback="o-camera" 
                            class="!w-24 !h-24 rounded-lg"
                        />
                    </div>
                </div>
                <div class="text-[10px] opacity-60">Foto Profil (Max 1MB)</div>
            </div>
        </div>

        <form wire:submit="save">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 h-[60vh] overflow-y-auto pr-2 custom-scrollbar">
                <div class="space-y-4">
                    <div class="font-bold border-b pb-1 text-sm opacity-70 italic">Identitas Siswa</div>

                    <x-input wire:model="name" label="Nama Lengkap" />
                    <x-input wire:model="email" label="Email" type="email" />

                    <div class="grid grid-cols-2 gap-3">
                        <x-input wire:model="nis" label="NIS" />
                        <x-input wire:model="nisn" label="NISN" />
                    </div>

                    <x-input wire:model="nik" label="NIK Siswa" placeholder="16 digit NIK" />

                    <div class="grid grid-cols-2 gap-3">
                        <x-input wire:model="no_kk" label="No. Kartu Keluarga" />
                        <x-input wire:model="no_akta" label="No. Akta Kelahiran" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <x-input wire:model="pob" label="Tempat Lahir" />
                        <x-input wire:model="dob" label="Tanggal Lahir" type="date" />
                    </div>

                    <x-input wire:model="phone" label="No. Telepon / WA" />

                    <x-select wire:model="classroom_id" label="Kelas" :options="$classrooms" placeholder="Pilih Kelas" />

                    <x-textarea wire:model="address" label="Alamat" rows="3" />

                    <div class="grid grid-cols-2 gap-3">
                        <x-input type="number" wire:model="birth_order" label="Anak Ke-" />
                        <x-input type="number" wire:model="total_siblings" label="Dari ... Bersaudara" />
                    </div>

                    <x-input wire:model="previous_school" label="Asal Sekolah" />

                    <x-select 
                        wire:model="status" 
                        label="Status Siswa" 
                        :options="[
                            ['id' => 'baru', 'name' => 'Baru'],
                            ['id' => 'mutasi', 'name' => 'Mutasi / Pindahan'],
                            ['id' => 'naik_kelas', 'name' => 'Naik Kelas'],
                            ['id' => 'lulus', 'name' => 'Lulus'],
                            ['id' => 'keluar', 'name' => 'Keluar'],
                        ]"
                    />
                </div>

                <div class="space-y-4">
                    <div class="font-bold border-b pb-1 text-sm opacity-70 italic">Data Orang Tua / Wali</div>

                    <x-input wire:model="father_name" label="Nama Ayah" />
                    <x-input wire:model="nik_ayah" label="NIK Ayah" />
                    <x-input wire:model="mother_name" label="Nama Ibu" />
                    <x-input wire:model="nik_ibu" label="NIK Ibu" />

                    <div class="pt-4 space-y-4">
                        <div class="font-bold text-xs opacity-70 uppercase tracking-widest">Kontak Wali (Jika Ada)</div>
                        <x-input wire:model="guardian_name" label="Nama Wali" />
                        <x-input wire:model="guardian_phone" label="No. Telp Wali" />
                    </div>
                </div>
            </div>

            <x-slot:actions>
                <x-button label="Batal" wire:click="$set('studentModal', false)" />
                <x-button label="Simpan" type="submit" class="btn-primary" spinner="save" />
            </x-slot:actions>
        </form>
    </x-modal>

    {{-- Modals --}}
    @include('livewire.admin.data-master.students.partials.import-modal')
    @include('livewire.admin.data-master.students.partials.periodic-modal')
    @include('livewire.admin.data-master.students.partials.detail-modal')
</div>
