<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Classroom;
use App\Models\Subject;
use App\Models\AcademicYear;
use App\Models\CompetencyAssessment;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

new #[Layout('components.admin.layouts.app')] class extends Component {
    public ?int $academic_year_id = null;
    public ?int $classroom_id = null;
    public ?int $subject_id = null;
    public string $semester = '1';

    public array $assessments_data = []; // [student_id => ['level' => '', 'description' => '']]

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
        $this->loadAssessments();
    }

    public function updatedSubjectId(): void
    {
        $this->loadAssessments();
    }

    public function updatedSemester(): void
    {
        $this->loadAssessments();
    }

    public function loadAssessments(): void
    {
        if (!$this->classroom_id || !$this->subject_id) {
            $this->assessments_data = [];
            return;
        }

        // Load existing assessments
        $assessments = CompetencyAssessment::where([
            'classroom_id' => $this->classroom_id,
            'subject_id' => $this->subject_id,
            'academic_year_id' => $this->academic_year_id,
            'semester' => $this->semester,
        ])->get();

        $this->assessments_data = $assessments->mapWithKeys(function ($assessment) {
            return [
                $assessment->student_id => [
                    'level' => $assessment->competency_level,
                    'description' => $assessment->achievement_description,
                ]
            ];
        })->toArray();
        
        // Ensure all students in classroom have an entry
        $students = User::where('role', 'siswa')
            ->whereHas('profiles.profileable', function ($q) {
                $q->where('classroom_id', $this->classroom_id);
            })->get();

        foreach ($students as $student) {
            if (!isset($this->assessments_data[$student->id])) {
                $this->assessments_data[$student->id] = [
                    'level' => 'BSH',
                    'description' => '',
                ];
            }
        }
    }

    public function save(): void
    {
        if (!$this->classroom_id || !$this->subject_id || !$this->academic_year_id) {
            return;
        }

        DB::transaction(function () {
            foreach ($this->assessments_data as $studentId => $data) {
                if (empty($data['description'])) continue;

                CompetencyAssessment::updateOrCreate(
                    [
                        'student_id' => $studentId,
                        'subject_id' => $this->subject_id,
                        'academic_year_id' => $this->academic_year_id,
                        'semester' => $this->semester,
                    ],
                    [
                        'classroom_id' => $this->classroom_id,
                        'competency_level' => $data['level'],
                        'achievement_description' => $data['description'],
                    ]
                );
            }
        });

        session()->flash('success', __('Penilaian kompetensi PAUD berhasil disimpan.'));
    }

    public function with(): array
    {
        $students = [];
        if ($this->classroom_id) {
            $students = User::where('role', 'siswa')
                ->whereHas('profiles.profileable', function ($q) {
                    $q->where('classroom_id', $this->classroom_id);
                })
                ->orderBy('name')
                ->get();
        }

        return [
            'years' => AcademicYear::orderBy('name', 'desc')->get(),
            'classrooms' => Classroom::whereHas('level', fn($q) => $q->where('education_level', 'PAUD'))
                ->when($this->academic_year_id, fn($q) => $q->where('academic_year_id', $this->academic_year_id))
                ->orderBy('name')->get(),
            'subjects' => Subject::whereHas('level', fn($q) => $q->where('education_level', 'PAUD'))->orderBy('name')->get(),
            'students' => $students,
        ];
    }
}; ?>

<div class="p-6 space-y-8 text-slate-900 dark:text-white pb-24 md:pb-6">
    @if (session('success'))
        <x-ui.alert :title="__('Sukses')" icon="o-check-circle" class="bg-emerald-50 text-emerald-800 border-emerald-100" dismissible>
            {{ session('success') }}
        </x-ui.alert>
    @endif

    <x-ui.header :title="__('Penilaian Capaian (PAUD)')" :subtitle="__('Input penilaian perkembangan anak berbasis Kurikulum Merdeka (BB / MB / BSH / SB) untuk semester terpilih.')" separator>
        @if($classroom_id && $subject_id)
            <x-slot:actions>
                <x-ui.button :label="__('Simpan Semua Nilai')" icon="o-check" class="btn-primary shadow-lg shadow-primary/20" wire:click="save" spinner="save" />
            </x-slot:actions>
        @endif
    </x-ui.header>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
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
            wire:model.live="subject_id" 
            :label="__('Aspek Perkembangan / Mapel')" 
            :placeholder="__('Pilih Mata Pelajaran')"
            :options="$subjects"
        />
    </div>

    {{-- Competency Level Legend --}}
    <div class="flex flex-wrap gap-x-6 gap-y-3 p-4 bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-100 dark:border-slate-800">
        <div class="flex items-center gap-3">
            <span class="size-2 rounded-full bg-rose-500 shadow-sm shadow-rose-500/30"></span>
            <span class="text-[10px] font-black text-rose-700/70 uppercase tracking-widest italic leading-none">{{ __('BB - Belum Berkembang') }}</span>
        </div>
        <div class="flex items-center gap-3">
            <span class="size-2 rounded-full bg-amber-400 shadow-sm shadow-amber-400/30"></span>
            <span class="text-[10px] font-black text-amber-700/70 uppercase tracking-widest italic leading-none">{{ __('MB - Mulai Berkembang') }}</span>
        </div>
        <div class="flex items-center gap-3">
            <span class="size-2 rounded-full bg-indigo-500 shadow-sm shadow-indigo-500/30"></span>
            <span class="text-[10px] font-black text-indigo-700/70 uppercase tracking-widest italic leading-none">{{ __('BSH - Berkembang Sesuai Harapan') }}</span>
        </div>
        <div class="flex items-center gap-3">
            <span class="size-2 rounded-full bg-emerald-500 shadow-sm shadow-emerald-500/30"></span>
            <span class="text-[10px] font-black text-emerald-700/70 uppercase tracking-widest italic leading-none">{{ __('SB - Sangat Berkembang') }}</span>
        </div>
    </div>

    @if($classroom_id && $subject_id)
        <x-ui.card shadow padding="false">
            <x-ui.table :headers="[
                ['key' => 'student_name', 'label' => __('Nama Siswa')],
                ['key' => 'assessment_level', 'label' => __('Capaian'), 'class' => 'text-center w-32'],
                ['key' => 'assessment_description', 'label' => __('Deskripsi Capaian / Narasi Perkembangan')]
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
                            wire:model="assessments_data.{{ $student->id }}.level" 
                            :options="[
                                ['id' => 'BB', 'name' => 'BB'],
                                ['id' => 'MB', 'name' => 'MB'],
                                ['id' => 'BSH', 'name' => 'BSH'],
                                ['id' => 'SB', 'name' => 'SB'],
                            ]" 
                            class="!py-1 font-black text-xs !w-24 text-center"
                        />
                    </div>
                @endscope

                @scope('cell_assessment_description', $student)
                    <x-ui.textarea 
                        wire:model="assessments_data.{{ $student->id }}.description" 
                        rows="2"
                        :placeholder="__('Tuliskan narasi perkembangan anak di sini...')"
                        class="border-none bg-slate-50/50 shadow-none focus:ring-1 italic text-sm"
                    />
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
            <p class="text-sm font-black uppercase tracking-widest italic animate-pulse">{{ __('Tentukan Kelas & Aspek Perkembangan Terlebih Dahulu') }}</p>
        </div>
    @endif
</div>
