<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Classroom;
use App\Models\AcademicYear;
use App\Models\DevelopmentalAspect;
use App\Models\DevelopmentalAssessment;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\DB;

new #[Layout('components.admin.layouts.app')] class extends Component {
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

        \Flux::toast('Penilaian perkembangan anak berhasil disimpan.');
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

        // Group aspects by type
        $aspectsByType = DevelopmentalAspect::all()->groupBy('aspect_type');

        return [
            'years' => AcademicYear::orderBy('name', 'desc')->get(),
            'classrooms' => Classroom::when($this->academic_year_id, fn($q) => $q->where('academic_year_id', $this->academic_year_id))->orderBy('name')->get(),
            'students' => $students,
            'aspectsByType' => $aspectsByType,
            'selectedStudent' => $this->student_id ? User::find($this->student_id) : null,
        ];
    }
}; ?>

<div class="p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl" level="1">Penilaian PAUD</flux:heading>
            <flux:subheading>Input penilaian perkembangan anak (6 aspek perkembangan).</flux:subheading>
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

        <flux:select wire:model.live="student_id" label="Anak">
            <option value="">Pilih Anak</option>
            @foreach($students as $student)
                <option value="{{ $student->id }}">{{ $student->name }}</option>
            @endforeach
        </flux:select>
    </div>

    <!-- Student Info Card -->
    @if($selectedStudent)
        <div class="mb-6 p-4 rounded-lg bg-gradient-to-r from-pink-50 to-purple-50 dark:from-pink-900/20 dark:to-purple-900/20 border border-pink-200 dark:border-pink-800">
            <div class="flex items-center gap-4">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 rounded-full bg-pink-500 flex items-center justify-center text-white font-bold text-lg">
                        {{ substr($selectedStudent->name, 0, 1) }}
                    </div>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $selectedStudent->name }}</h3>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">Laporan Perkembangan Semester {{ $semester }}</p>
                </div>
            </div>
        </div>
    @endif

    @if($student_id)
        @php
            $aspectTypeLabels = [
                'nilai_agama' => ['label' => 'Nilai Agama dan Budi Pekerti', 'color' => 'from-purple-500 to-indigo-500', 'icon' => 'heart'],
                'fisik_motorik' => ['label' => 'Fisik-Motorik', 'color' => 'from-green-500 to-emerald-500', 'icon' => 'hand-raised'],
                'kognitif' => ['label' => 'Kognitif', 'color' => 'from-blue-500 to-cyan-500', 'icon' => 'light-bulb'],
                'bahasa' => ['label' => 'Bahasa', 'color' => 'from-orange-500 to-amber-500', 'icon' => 'chat-bubble-left-right'],
                'sosial_emosional' => ['label' => 'Sosial-Emosional', 'color' => 'from-pink-500 to-rose-500', 'icon' => 'users'],
                'seni' => ['label' => 'Seni', 'color' => 'from-violet-500 to-purple-500', 'icon' => 'paint-brush'],
            ];
        @endphp

        <div class="space-y-6">
            @foreach($aspectsByType as $aspectType => $aspects)
                @php
                    $typeInfo = $aspectTypeLabels[$aspectType] ?? ['label' => $aspectType, 'color' => 'from-gray-500 to-gray-600', 'icon' => 'document'];
                @endphp
                <div class="border rounded-lg bg-white dark:bg-zinc-900 overflow-hidden">
                    <!-- Aspect Type Header -->
                    <div class="p-4 bg-gradient-to-r {{ $typeInfo['color'] }} text-white">
                        <div class="flex items-center gap-3">
                            <flux:icon icon="{{ $typeInfo['icon'] }}" class="w-6 h-6" />
                            <h3 class="text-lg font-semibold">{{ $typeInfo['label'] }}</h3>
                        </div>
                    </div>

                    <!-- Aspects -->
                    <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($aspects as $aspect)
                            <div class="p-4" wire:key="aspect-{{ $aspect->id }}">
                                <label class="block text-sm font-medium text-zinc-900 dark:text-white mb-2">
                                    {{ $aspect->name }}
                                </label>
                                @if($aspect->description)
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2">{{ $aspect->description }}</p>
                                @endif
                                <flux:textarea 
                                    wire:model="assessments_data.{{ $aspect->id }}" 
                                    rows="3"
                                    placeholder="Tuliskan deskripsi perkembangan anak pada aspek ini..."
                                />
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <div class="flex justify-end">
                <flux:button variant="primary" icon="check" wire:click="save">
                    Simpan Penilaian Perkembangan
                </flux:button>
            </div>
        </div>
    @else
        <div class="flex flex-col items-center justify-center py-12 text-zinc-500 border-2 border-dashed rounded-xl">
            <flux:icon icon="face-smile" class="w-12 h-12 mb-2 opacity-20" />
            <p>Silakan pilih kelas dan anak untuk memulai penilaian perkembangan.</p>
        </div>
    @endif
</div>
