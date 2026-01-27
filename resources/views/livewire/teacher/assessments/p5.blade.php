<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Classroom;
use App\Models\AcademicYear;
use App\Models\P5Project;
use App\Models\P5Assessment;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\DB;

new #[Layout('components.teacher.layouts.app')] class extends Component {
    public ?int $academic_year_id = null;
    public ?int $classroom_id = null;
    public ?int $p5_project_id = null;
    public string $semester = '1';

    public array $assessments_data = []; // [student_id => ['level' => '', 'description' => '']]

    public function mount(): void
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        if ($activeYear) {
            $this->academic_year_id = $activeYear->id;
        }
    }

    public function updatedClassroomId(): void
    {
        $this->loadAssessments();
    }

    public function updatedP5ProjectId(): void
    {
        $this->loadAssessments();
    }

    public function updatedSemester(): void
    {
        $this->loadAssessments();
    }

    public function loadAssessments(): void
    {
        if (!$this->classroom_id || !$this->p5_project_id) {
            $this->assessments_data = [];
            return;
        }

        // Verify teacher has access
        $teacher = auth()->user();
        if (!$teacher->hasAccessToClassroom($this->classroom_id)) {
            $this->assessments_data = [];
            \Flux::toast(variant: 'danger', text: 'Anda tidak memiliki akses ke kelas ini.');
            return;
        }

        // Load existing assessments
        $assessments = P5Assessment::where([
            'classroom_id' => $this->classroom_id,
            'p5_project_id' => $this->p5_project_id,
            'academic_year_id' => $this->academic_year_id,
            'semester' => $this->semester,
        ])->get();

        $this->assessments_data = $assessments->mapWithKeys(function ($assessment) {
            return [
                $assessment->student_id => [
                    'level' => $assessment->achievement_level,
                    'description' => $assessment->description,
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
        if (!$this->classroom_id || !$this->p5_project_id || !$this->academic_year_id) {
            return;
        }

        // Verify teacher has access
        $teacher = auth()->user();
        if (!$teacher->hasAccessToClassroom($this->classroom_id)) {
            \Flux::toast(variant: 'danger', text: 'Anda tidak memiliki akses untuk menyimpan penilaian ini.');
            return;
        }

        DB::transaction(function () {
            foreach ($this->assessments_data as $studentId => $data) {
                if (empty($data['description'])) continue;

                P5Assessment::updateOrCreate(
                    [
                        'student_id' => $studentId,
                        'p5_project_id' => $this->p5_project_id,
                    ],
                    [
                        'academic_year_id' => $this->academic_year_id,
                        'classroom_id' => $this->classroom_id,
                        'semester' => $this->semester,
                        'achievement_level' => $data['level'],
                        'description' => $data['description'],
                    ]
                );
            }
        });

        \Flux::toast('Penilaian P5 berhasil disimpan.');
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

        return [
            'years' => AcademicYear::orderBy('name', 'desc')->get(),
            'classrooms' => Classroom::whereIn('id', $assignedClassroomIds)
                ->when($this->academic_year_id, fn($q) => $q->where('academic_year_id', $this->academic_year_id))
                ->orderBy('name')
                ->get(),
            'projects' => P5Project::when($this->academic_year_id, fn($q) => $q->where('academic_year_id', $this->academic_year_id))
                ->when($this->semester, fn($q) => $q->where('semester', $this->semester))
                ->orderBy('name')
                ->get(),
            'students' => $students,
            'selectedProject' => $this->p5_project_id ? P5Project::find($this->p5_project_id) : null,
        ];
    }
}; ?>

<div class="p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl" level="1">Penilaian Projek P5</flux:heading>
            <flux:subheading>Input penilaian Projek Penguatan Profil Pelajar Pancasila.</flux:subheading>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <flux:select wire:model.live="academic_year_id" label="Tahun Ajaran">
            @foreach($years as $year)
                <option value="{{ $year->id }}">{{ $year->name }}</option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="semester" label="Semester">
            <option value="1">Semester 1</option>
            <option value="2">Semester 2</option>
        </flux:select>

        <flux:select wire:model.live="classroom_id" label="Kelas">
            <option value="">Pilih Kelas</option>
            @foreach($classrooms as $room)
                <option value="{{ $room->id }}">{{ $room->name }}</option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="p5_project_id" label="Projek P5">
            <option value="">Pilih Projek</option>
            @foreach($projects as $project)
                <option value="{{ $project->id }}">{{ $project->name }}</option>
            @endforeach
        </flux:select>
    </div>

    <!-- Project Info Card -->
    @if($selectedProject)
        <div class="mb-6 p-4 rounded-lg bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border border-blue-200 dark:border-blue-800">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0">
                    @php
                        $dimensionColors = [
                            'beriman' => 'bg-purple-500',
                            'berkebinekaan' => 'bg-orange-500',
                            'gotong_royong' => 'bg-green-500',
                            'mandiri' => 'bg-blue-500',
                            'bernalar_kritis' => 'bg-red-500',
                            'kreatif' => 'bg-yellow-500',
                        ];
                        $dimensionLabels = [
                            'beriman' => 'Beriman & Bertakwa',
                            'berkebinekaan' => 'Berkebinekaan Global',
                            'gotong_royong' => 'Gotong Royong',
                            'mandiri' => 'Mandiri',
                            'bernalar_kritis' => 'Bernalar Kritis',
                            'kreatif' => 'Kreatif',
                        ];
                    @endphp
                    
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium text-white {{ $dimensionColors[$selectedProject->dimension] ?? 'bg-gray-500' }}">
                        {{ $dimensionLabels[$selectedProject->dimension] ?? $selectedProject->dimension }}
                    </span>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $selectedProject->name }}</h3>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">{{ $selectedProject->description }}</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Competency Level Legend -->
    <div class="flex flex-wrap gap-4 mb-4 text-sm">
        <div class="flex items-center gap-2">
            <span class="w-3 h-3 rounded-full bg-red-500"></span>
            <span class="text-zinc-600 dark:text-zinc-400">BB - Belum Berkembang</span>
        </div>
        <div class="flex items-center gap-2">
            <span class="w-3 h-3 rounded-full bg-yellow-500"></span>
            <span class="text-zinc-600 dark:text-zinc-400">MB - Mulai Berkembang</span>
        </div>
        <div class="flex items-center gap-2">
            <span class="w-3 h-3 rounded-full bg-blue-500"></span>
            <span class="text-zinc-600 dark:text-zinc-400">BSH - Berkembang Sesuai Harapan</span>
        </div>
        <div class="flex items-center gap-2">
            <span class="w-3 h-3 rounded-full bg-green-500"></span>
            <span class="text-zinc-600 dark:text-zinc-400">SB - Sangat Berkembang</span>
        </div>
    </div>

    @if($classroom_id && $p5_project_id)
        <div class="border rounded-lg bg-white dark:bg-zinc-900 overflow-hidden">
            <table class="w-full text-sm text-left border-collapse">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b w-48">Nama Siswa</th>
                        <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b w-32 text-center">Capaian</th>
                        <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b">Deskripsi Capaian</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach($students as $student)
                        <tr wire:key="{{ $student->id }}">
                            <td class="px-4 py-3 text-zinc-900 dark:text-white font-medium">
                                {{ $student->name }}
                            </td>
                            <td class="px-4 py-3">
                                <flux:select wire:model="assessments_data.{{ $student->id }}.level" class="text-center">
                                    <option value="BB">BB</option>
                                    <option value="MB">MB</option>
                                    <option value="BSH">BSH</option>
                                    <option value="SB">SB</option>
                                </flux:select>
                            </td>
                            <td class="px-4 py-3">
                                <flux:textarea 
                                    wire:model="assessments_data.{{ $student->id }}.description" 
                                    rows="2"
                                    placeholder="Tuliskan deskripsi capaian siswa dalam projek P5..."
                                />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="p-4 bg-zinc-50 dark:bg-zinc-800 border-t flex justify-end">
                <flux:button variant="primary" icon="check" wire:click="save">Simpan Penilaian</flux:button>
            </div>
        </div>
    @else
        <div class="flex flex-col items-center justify-center py-12 text-zinc-500 border-2 border-dashed rounded-xl">
            <flux:icon icon="clipboard-document-list" class="w-12 h-12 mb-2 opacity-20" />
            <p>Silakan pilih kelas dan projek P5 untuk memulai penilaian.</p>
        </div>
    @endif
</div>
