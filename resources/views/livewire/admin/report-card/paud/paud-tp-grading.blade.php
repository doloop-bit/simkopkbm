<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Classroom;
use App\Models\AcademicYear;
use App\Models\PaudTp;
use App\Models\PaudTpAssessment;
use App\Models\PaudCpElement;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;
use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

new #[Layout('components.layouts.app')] class extends Component {
    use WithFileUploads;

    public ?int $academic_year_id = null;
    public ?int $classroom_id = null;
    public ?int $paud_cp_element_id = null;
    public ?int $paud_tp_id = null;
    public string $semester = '1';

    public array $grades = []; // [student_id => ['level' => 'BSH', 'notes' => '']]
    public array $tempPhotos = []; // [student_id => [uploaded_files]]
    public array $existingPhotos = []; // [student_id => [paths]]

    public function mount(): void
    {
        if (!auth()->user()->isAdmin() && !auth()->user()->teachesPaudLevel()) {
            abort(403);
        }

        $activeYear = AcademicYear::where('is_active', true)->first();
        if ($activeYear) {
            $this->academic_year_id = $activeYear->id;
        }
    }

    public function updatedClassroomId(): void
    {
        $this->paud_cp_element_id = null;
        $this->paud_tp_id = null;
        $this->loadGrades();
    }

    public function updatedAcademicYearId(): void
    {
        $this->classroom_id = null;
        $this->paud_cp_element_id = null;
        $this->paud_tp_id = null;
        $this->loadGrades();
    }

    public function updatedSemester(): void
    {
        $this->paud_cp_element_id = null;
        $this->paud_tp_id = null;
        $this->loadGrades();
    }

    public function updatedPaudCpElementId(): void
    {
        $this->paud_tp_id = null;
        $this->loadGrades();
    }

    public function updatedPaudTpId(): void
    {
        $this->loadGrades();
    }

    public function loadGrades(): void
    {
        $this->grades = [];
        $this->tempPhotos = [];
        $this->existingPhotos = [];

        if (!$this->classroom_id || !$this->paud_tp_id) {
            return;
        }

        $students = $this->getStudents();

        $assessments = PaudTpAssessment::where('paud_tp_id', $this->paud_tp_id)
            ->whereIn('student_id', $students->pluck('id'))
            ->get()
            ->keyBy('student_id');

        foreach ($students as $student) {
            $assessment = $assessments->get($student->id);
            $this->grades[$student->id] = [
                'level' => $assessment ? $assessment->level : 'BSH',
                'notes' => $assessment ? $assessment->notes : '',
            ];
            $this->existingPhotos[$student->id] = $assessment ? ($assessment->photos ?? []) : [];
            $this->tempPhotos[$student->id] = [];
        }
    }

    public function removeExistingPhoto(int $studentId, int $index): void
    {
        if (isset($this->existingPhotos[$studentId][$index])) {
            $photoPath = $this->existingPhotos[$studentId][$index];
            Storage::disk('public')->delete($photoPath);
            unset($this->existingPhotos[$studentId][$index]);
            $this->existingPhotos[$studentId] = array_values($this->existingPhotos[$studentId]);

            // Save immediately for this student if the assessment exists
            $assessment = PaudTpAssessment::where([
                'paud_tp_id' => $this->paud_tp_id,
                'student_id' => $studentId,
            ])->first();

            if ($assessment) {
                $assessment->update(['photos' => $this->existingPhotos[$studentId]]);
            }
        }
    }

    public function save(): void
    {
        if (!$this->classroom_id || !$this->paud_tp_id) {
            return;
        }

        DB::transaction(function () {
            foreach ($this->grades as $studentId => $data) {
                $savedPhotos = $this->existingPhotos[$studentId] ?? [];

                // Process and compress new photo uploads if any
                if (!empty($this->tempPhotos[$studentId])) {
                    foreach ($this->tempPhotos[$studentId] as $photo) {
                        $savedPhotos[] = $this->compressAndSavePhoto($photo);
                    }
                }

                PaudTpAssessment::updateOrCreate(
                    [
                        'paud_tp_id' => $this->paud_tp_id,
                        'student_id' => $studentId,
                    ],
                    [
                        'level' => $data['level'],
                        'notes' => $data['notes'],
                        'photos' => $savedPhotos,
                        'assessed_by' => auth()->id(),
                    ]
                );
            }
        });

        $this->loadGrades();
        session()->flash('success', __('Penilaian Tujuan Pembelajaran PAUD berhasil disimpan.'));
    }

    protected function compressAndSavePhoto($photo): string
    {
        $filename = 'paud_assessments/' . uniqid() . '.webp';
        
        // Ensure directory exists
        Storage::disk('public')->makeDirectory('paud_assessments');

        // Compression using GD / Intervention Image if available, otherwise fallback to standard upload
        try {
            $manager = new ImageManager(new Driver());
            $image = $manager->read($photo->getRealPath());
            
            // Limit max width to 1200px
            if ($image->width() > 1200) {
                $image->scale(width: 1200);
            }
            
            $encoded = $image->toWebp(80);
            Storage::disk('public')->put($filename, (string) $encoded);
        } catch (\Throwable $e) {
            // Fallback: save raw
            $path = $photo->store('paud_assessments', 'public');
            return $path;
        }

        return $filename;
    }

    protected function getStudents()
    {
        if (!$this->classroom_id) {
            return collect();
        }

        return User::where('role', 'siswa')
            ->whereHas('profiles.profileable', function ($q) {
                $q->where('classroom_id', $this->classroom_id);
            })
            ->orderBy('name')
            ->get();
    }

    public function with(): array
    {
        $tps = [];
        if ($this->classroom_id && $this->academic_year_id) {
            $tps = PaudTp::where([
                'classroom_id' => $this->classroom_id,
                'academic_year_id' => $this->academic_year_id,
                'semester' => $this->semester,
            ])
            ->when($this->paud_cp_element_id, fn($q) => $q->where('paud_cp_element_id', $this->paud_cp_element_id))
            ->orderBy('order')
            ->get()
            ->map(function ($tp) {
                return [
                    'id' => $tp->id,
                    'name' => '[' . $tp->code . '] ' . Str::limit($tp->description, 100),
                ];
            });
        }

        return [
            'years' => AcademicYear::orderBy('name', 'desc')->get(),
            'classrooms' => Classroom::whereHas('level', fn($q) => $q->where('education_level', 'PAUD'))
                ->when($this->academic_year_id, fn($q) => $q->where('academic_year_id', $this->academic_year_id))
                ->orderBy('name')->get(),
            'cpElements' => PaudCpElement::orderBy('order')->get(),
            'tps' => $tps,
            'students' => $this->getStudents(),
        ];
    }
}; ?>

<div class="p-6 space-y-8 text-slate-900 dark:text-white pb-24 md:pb-6">
    @if (session('success'))
        <x-ui.alert :title="__('Sukses')" icon="o-check-circle" class="bg-emerald-50 text-emerald-800 border-emerald-100" dismissible>
            {{ session('success') }}
        </x-ui.alert>
    @endif

    <x-ui.header :title="__('Penilaian Tujuan Pembelajaran (PAUD)')" :subtitle="__('Input capaian perkembangan siswa per Tujuan Pembelajaran.')" separator>
        @if($classroom_id && $paud_tp_id)
            <x-slot:actions>
                <x-ui.button :label="__('Simpan Semua Nilai')" icon="o-check" class="btn-primary shadow-lg shadow-primary/20" wire:click="save" spinner="save" />
            </x-slot:actions>
        @endif
    </x-ui.header>

    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-6">
        <x-ui.select wire:model.live="academic_year_id" :label="__('Tahun Ajaran')" :options="$years" />
        <x-ui.select 
            wire:model.live="semester" 
            :label="__('Semester')" 
            :options="[
                ['id' => '1', 'name' => __('Semester 1')],
                ['id' => '2', 'name' => __('Semester 2')],
            ]" 
        />
        <x-ui.select 
            wire:model.live="classroom_id" 
            :label="__('Kelas / Rombel')" 
            :placeholder="__('Pilih Kelas')"
            :options="$classrooms"
        />
        <x-ui.select 
            wire:model.live="paud_cp_element_id" 
            :label="__('Elemen CP')" 
            :placeholder="__('Semua Elemen')"
            :options="$cpElements"
        />
        <x-ui.select 
            wire:model.live="paud_tp_id" 
            :label="__('Tujuan Pembelajaran (TP)')" 
            :placeholder="__('Pilih Tujuan Pembelajaran')"
            :options="$tps"
        />
    </div>

    {{-- Legend --}}
    <div class="flex flex-wrap gap-x-6 gap-y-3 p-4 bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-100 dark:border-slate-800">
        <div class="flex items-center gap-3">
            <span class="size-2 rounded-full bg-orange-500 shadow-sm shadow-orange-500/30"></span>
            <span class="text-[10px] font-black text-orange-700/70 uppercase tracking-widest italic leading-none">{{ __('BB - Belum Berkembang') }}</span>
        </div>
        <div class="flex items-center gap-3">
            <span class="size-2 rounded-full bg-yellow-400 shadow-sm shadow-yellow-400/30"></span>
            <span class="text-[10px] font-black text-yellow-700/70 uppercase tracking-widest italic leading-none">{{ __('MB - Mulai Berkembang') }}</span>
        </div>
        <div class="flex items-center gap-3">
            <span class="size-2 rounded-full bg-emerald-500 shadow-sm shadow-emerald-500/30"></span>
            <span class="text-[10px] font-black text-emerald-700/70 uppercase tracking-widest italic leading-none">{{ __('BSH - Berkembang Sesuai Harapan') }}</span>
        </div>
        <div class="flex items-center gap-3">
            <span class="size-2 rounded-full bg-blue-500 shadow-sm shadow-blue-500/30"></span>
            <span class="text-[10px] font-black text-blue-700/70 uppercase tracking-widest italic leading-none">{{ __('BSB - Berkembang Sangat Baik') }}</span>
        </div>
    </div>

    @if($classroom_id && $paud_tp_id)
        <x-ui.card shadow padding="false">
            <x-ui.table :headers="[
                ['key' => 'student_name', 'label' => __('Nama Siswa')],
                ['key' => 'assessment_level', 'label' => __('Capaian'), 'class' => 'text-center w-32'],
                ['key' => 'assessment_notes', 'label' => __('Catatan Guru / Deskripsi')],
                ['key' => 'assessment_photos', 'label' => __('Dokumentasi / Foto'), 'class' => 'w-64']
            ]" :rows="$students">
                @scope('cell_student_name', $student)
                    <div class="flex flex-col">
                        <span class="font-bold text-slate-900 dark:text-white">{{ $student->name }}</span>
                        <span class="text-[10px] text-slate-400 font-mono tracking-tighter">{{ $student->nis ?? $student->username }}</span>
                    </div>
                @endscope

                @scope('cell_assessment_level', $student)
                    <div class="flex justify-center">
                        <x-ui.select 
                            wire:model="grades.{{ $student->id }}.level" 
                            :options="[
                                ['id' => 'BB', 'name' => 'BB'],
                                ['id' => 'MB', 'name' => 'MB'],
                                ['id' => 'BSH', 'name' => 'BSH'],
                                ['id' => 'BSB', 'name' => 'BSB'],
                            ]" 
                            class="!py-1 font-black text-xs !w-24 text-center"
                        />
                    </div>
                @endscope

                @scope('cell_assessment_notes', $student)
                    <x-ui.textarea 
                        wire:model="grades.{{ $student->id }}.notes" 
                        rows="2"
                        :placeholder="__('Tuliskan catatan perkembangan anak untuk TP ini...')"
                        class="border-none bg-slate-50/50 shadow-none focus:ring-1 italic text-sm"
                    />
                @endscope

                @scope('cell_assessment_photos', $student)
                    <div class="space-y-3">
                        {{-- Existing Photos --}}
                        @if (!empty($existingPhotos[$student->id]))
                            <div class="grid grid-cols-3 gap-2">
                                @foreach ($existingPhotos[$student->id] as $index => $photoPath)
                                    <div class="relative group size-12 rounded-lg overflow-hidden border border-slate-100">
                                        <img src="{{ Storage::url($photoPath) }}" class="size-full object-cover" />
                                        <button 
                                            type="button" 
                                            wire:click="removeExistingPhoto({{ $student->id }}, {{ $index }})" 
                                            class="absolute inset-0 bg-rose-600/80 text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                            <x-ui.icon name="o-trash" class="size-4" />
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        {{-- Upload Input --}}
                        <div>
                            <input 
                                type="file" 
                                wire:model="tempPhotos.{{ $student->id }}" 
                                multiple 
                                class="file-input file-input-bordered file-input-xs w-full max-w-xs" 
                                accept="image/*"
                            />
                        </div>
                    </div>
                @endscope
            </x-ui.table>

            @if($students->isEmpty())
                <div class="py-12 text-center text-slate-400 italic text-sm">
                    {{ __('Belum ada siswa terdaftar di kelas PAUD ini.') }}
                </div>
            @endif
        </x-ui.card>
    @else
        <div class="flex flex-col items-center justify-center py-32 text-slate-300 dark:text-slate-700 border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-[32px] bg-slate-50/50 dark:bg-slate-900/50 transition-all">
            <x-ui.icon name="o-pencil-square" class="size-20 mb-6 opacity-20" />
            <p class="text-sm font-black uppercase tracking-widest italic animate-pulse">{{ __('Tentukan Parameter Akademik & Tujuan Pembelajaran Terlebih Dahulu') }}</p>
        </div>
    @endif
</div>
