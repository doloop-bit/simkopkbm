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

use Mary\Traits\Toast;

new #[Layout('components.admin.layouts.app')] class extends Component {
    use Toast;

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

        $this->success('Penilaian kompetensi berhasil disimpan.');
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

<div class="p-6 flex flex-col gap-6">
    <x-header title="Penilaian Capaian Pembelajaran (PAUD)" subtitle="Input penilaian perkembangan anak berbasis Kurikulum Merdeka (BB/MB/BSH/SB)." separator />

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <x-select wire:model.live="academic_year_id" label="Tahun Ajaran" :options="$years" />
        <x-select 
            wire:model.live="semester" 
            label="Semester" 
            :options="[
                ['id' => '1', 'name' => 'Semester 1'],
                ['id' => '2', 'name' => 'Semester 2'],
            ]" 
        />
        <x-select 
            wire:model.live="classroom_id" 
            label="Kelas" 
            placeholder="Pilih Kelas"
            :options="$classrooms"
        />
        <x-select 
            wire:model.live="subject_id" 
            label="Mata Pelajaran" 
            placeholder="Pilih Mata Pelajaran"
            :options="$subjects"
        />
    </div>

    <!-- Competency Level Legend -->
    <div class="flex flex-wrap gap-4 text-xs">
        <div class="flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-error"></span>
            <span class="opacity-70 text-[10px] uppercase tracking-wider">BB - Belum Berkembang</span>
        </div>
        <div class="flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-warning"></span>
            <span class="opacity-70 text-[10px] uppercase tracking-wider">MB - Mulai Berkembang</span>
        </div>
        <div class="flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-primary"></span>
            <span class="opacity-70 text-[10px] uppercase tracking-wider">BSH - Berkembang Sesuai Harapan</span>
        </div>
        <div class="flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-success"></span>
            <span class="opacity-70 text-[10px] uppercase tracking-wider">SB - Sangat Berkembang</span>
        </div>
    </div>

    @if($classroom_id && $subject_id)
        <x-card shadow>
            <x-table :headers="[
                ['key' => 'name', 'label' => 'Nama Siswa'],
                ['key' => 'level', 'label' => 'Capaian', 'class' => 'text-center w-32'],
                ['key' => 'description', 'label' => 'Deskripsi Capaian']
            ]" :rows="$students">
                @scope('cell_name', $student)
                    <span class="font-medium">{{ $student->name }}</span>
                @endscope

                @scope('cell_level', $student)
                    <x-select 
                        wire:model="assessments_data.{{ $student->id }}.level" 
                        sm
                        :options="[
                            ['id' => 'BB', 'name' => 'BB'],
                            ['id' => 'MB', 'name' => 'MB'],
                            ['id' => 'BSH', 'name' => 'BSH'],
                            ['id' => 'SB', 'name' => 'SB'],
                        ]" 
                    />
                @endscope

                @scope('cell_description', $student)
                    <x-textarea 
                        wire:model="assessments_data.{{ $student->id }}.description" 
                        rows="2"
                        placeholder="Tuliskan deskripsi..."
                        sm
                    />
                @endscope
            </x-table>

            <x-slot:actions>
                <x-button label="Simpan Penilaian" icon="o-check" class="btn-primary" wire:click="save" spinner="save" />
            </x-slot:actions>
        </x-card>
    @else
        <div class="flex flex-col items-center justify-center py-12 text-base-content/30 border-2 border-dashed rounded-xl bg-base-200/50">
            <x-icon name="o-pencil-square" class="size-12 mb-2 opacity-20" />
            <p>Silakan pilih kelas dan mata pelajaran untuk memulai penilaian.</p>
        </div>
    @endif
</div>
