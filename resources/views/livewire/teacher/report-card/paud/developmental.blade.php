<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Classroom;
use App\Models\AcademicYear;
use App\Models\DevelopmentalAspect;
use App\Models\DevelopmentalAssessment;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

use Mary\Traits\Toast;

new #[Layout('components.teacher.layouts.app')] class extends Component {
    use Toast;

    public ?int $academic_year_id = null;
    public ?int $classroom_id = null;
    public ?int $student_id = null;
    public string $semester = '1';

    public array $assessments_data = []; // [aspect_id => description]

    public function mount(): void
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        if ($activeYear) {
            $this->academic_year_id = $activeYear->id;
        }
    }

    public function updatedClassroomId(): void
    {
        $this->student_id = null;
        $this->assessments_data = [];
    }

    public function updatedStudentId(): void
    {
        $this->loadAssessments();
    }

    public function updatedSemester(): void
    {
        $this->loadAssessments();
    }

    public function loadAssessments(): void
    {
        if (!$this->student_id) {
            $this->assessments_data = [];
            return;
        }

        // Verify teacher has access to this student's classroom
        $teacher = auth()->user();
        $student = User::find($this->student_id);
        if (!$student || !$teacher->hasAccessToClassroom($this->classroom_id)) {
             $this->assessments_data = [];
             return;
        }

        // Load existing assessments
        $assessments = DevelopmentalAssessment::where([
            'student_id' => $this->student_id,
            'academic_year_id' => $this->academic_year_id,
            'semester' => $this->semester,
        ])->get();

        $this->assessments_data = $assessments->pluck('description', 'developmental_aspect_id')->toArray();
        
        // Ensure all aspects have an entry
        $aspects = DevelopmentalAspect::all();
        foreach ($aspects as $aspect) {
            if (!isset($this->assessments_data[$aspect->id])) {
                $this->assessments_data[$aspect->id] = '';
            }
        }
    }

    public function save(): void
    {
        if (!$this->student_id || !$this->classroom_id || !$this->academic_year_id) {
            return;
        }

        // Verify teacher has access
        $teacher = auth()->user();
        if (!$teacher->hasAccessToClassroom($this->classroom_id)) {
            $this->error('Anda tidak memiliki akses untuk menyimpan penilaian ini.');
            return;
        }

        DB::transaction(function () {
            foreach ($this->assessments_data as $aspectId => $description) {
                if (empty($description)) continue;

                DevelopmentalAssessment::updateOrCreate(
                    [
                        'student_id' => $this->student_id,
                        'developmental_aspect_id' => $aspectId,
                        'academic_year_id' => $this->academic_year_id,
                        'semester' => $this->semester,
                    ],
                    [
                        'classroom_id' => $this->classroom_id,
                        'description' => $description,
                    ]
                );
            }
        });

        $this->success('Penilaian perkembangan anak berhasil disimpan.');
    }

    public function with(): array
    {
        $teacher = auth()->user();
        $assignedClassroomIds = $teacher->getAssignedClassroomIds();

        $students = [];
        if ($this->classroom_id) {
            $students = User::where('role', 'siswa')
                ->whereHas('profiles.profileable', function ($q) {
                    $q->where('classroom_id', $this->classroom_id);
                })
                ->orderBy('name')
                ->get();
        }

        // Group aspects by type
        $aspectsByType = DevelopmentalAspect::all()->groupBy('aspect_type');

        return [
            'years' => AcademicYear::orderBy('name', 'desc')->get(),
            'classrooms' => Classroom::whereIn('id', $assignedClassroomIds)
                ->whereHas('level', function($q) {
                    $q->where('education_level', 'PAUD');
                })
                ->when($this->academic_year_id, fn($q) => $q->where('academic_year_id', $this->academic_year_id))
                ->orderBy('name')
                ->get(),
            'students' => $students,
            'aspectsByType' => $aspectsByType,
            'selectedStudent' => $this->student_id ? User::find($this->student_id) : null,
        ];
    }
}; ?>

<div class="p-6 flex flex-col gap-6">
    <x-header title="Penilaian PAUD" subtitle="Input penilaian perkembangan anak (6 aspek perkembangan)." separator />

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
            wire:model.live="student_id" 
            label="Anak" 
            placeholder="Pilih Anak"
            :options="$students"
        />
    </div>

    <!-- Student Info Card -->
    @if($selectedStudent)
        <div class="p-4 rounded-xl bg-primary/5 border border-primary/20 flex items-center gap-4">
            <div class="avatar placeholder">
                <div class="bg-primary text-primary-content rounded-full w-12">
                    <span class="text-xl font-bold">{{ substr($selectedStudent->name, 0, 1) }}</span>
                </div>
            </div>
            <div class="flex flex-col">
                <h3 class="text-lg font-black tracking-tight">{{ $selectedStudent->name }}</h3>
                <p class="text-xs opacity-60 font-medium uppercase tracking-widest">Laporan Perkembangan Semester {{ $semester }}</p>
            </div>
        </div>
    @endif

    @if($student_id)
        @php
            $aspectTypeLabels = [
                'nilai_agama' => ['label' => 'Nilai Agama dan Budi Pekerti', 'color' => 'bg-purple-500', 'icon' => 'o-heart'],
                'fisik_motorik' => ['label' => 'Fisik-Motorik', 'color' => 'bg-green-500', 'icon' => 'o-hand-raised'],
                'kognitif' => ['label' => 'Kognitif', 'color' => 'bg-blue-500', 'icon' => 'o-light-bulb'],
                'bahasa' => ['label' => 'Bahasa', 'color' => 'bg-orange-500', 'icon' => 'o-chat-bubble-left-right'],
                'sosial_emosional' => ['label' => 'Sosial-Emosional', 'color' => 'bg-pink-500', 'icon' => 'o-users'],
                'seni' => ['label' => 'Seni', 'color' => 'bg-indigo-500', 'icon' => 'o-paint-brush'],
            ];
        @endphp

        <div class="space-y-6">
            @foreach($aspectsByType as $aspectType => $aspects)
                @php
                    $typeInfo = $aspectTypeLabels[$aspectType] ?? ['label' => $aspectType, 'color' => 'bg-gray-500', 'icon' => 'o-document-text'];
                @endphp
                <x-card shadow class="overflow-hidden border-0 p-0!">
                    <!-- Aspect Type Header -->
                    <div class="p-4 {{ $typeInfo['color'] }} text-white flex items-center gap-3">
                        <x-icon name="{{ $typeInfo['icon'] }}" class="size-6" />
                        <h3 class="text-lg font-bold">{{ $typeInfo['label'] }}</h3>
                    </div>

                    <!-- Aspects -->
                    <div class="p-4 space-y-4">
                        @foreach($aspects as $aspect)
                            <div wire:key="aspect-{{ $aspect->id }}">
                                <x-textarea 
                                    wire:model="assessments_data.{{ $aspect->id }}" 
                                    label="{{ $aspect->name }}" 
                                    hint="{{ $aspect->description }}"
                                    rows="4"
                                    placeholder="Tuliskan deskripsi perkembangan anak pada aspek ini..."
                                />
                            </div>
                        @endforeach
                    </div>
                </x-card>
            @endforeach

            <div class="flex justify-end sticky bottom-6 z-10">
                <x-button label="Simpan Penilaian Perkembangan" icon="o-check" class="btn-primary shadow-xl" wire:click="save" spinner="save" />
            </div>
        </div>
    @else
        <div class="flex flex-col items-center justify-center py-20 text-base-content/30 border-2 border-dashed rounded-xl bg-base-200/50">
            <x-icon name="o-face-smile" class="size-16 mb-2 opacity-20" />
            <p>Silakan pilih kelas dan anak untuk memulai penilaian perkembangan.</p>
        </div>
    @endif
</div>
