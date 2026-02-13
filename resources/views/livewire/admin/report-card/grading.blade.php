<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\SubjectGrade;
use App\Models\SubjectTp;
use App\Models\Classroom;
use App\Models\Subject;
use App\Models\AcademicYear;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

new #[Layout('components.admin.layouts.app')] class extends Component {
    public ?int $academic_year_id = null;
    public ?int $classroom_id = null;
    public ?int $subject_id = null;

    // Data containers
    public array $grades_data = []; // [student_id => ['grade' => float, 'best_tp_id' => int, 'improvement_tp_id' => int]]
    
    public function mount(): void
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        if ($activeYear) {
            $this->academic_year_id = $activeYear->id;
        }
    }

    public function updatedClassroomId(): void
    {
        $this->loadGrades();
    }

    public function updatedSubjectId(): void
    {
        $this->loadGrades();
    }

    public function loadGrades(): void
    {
        if (!$this->classroom_id || !$this->subject_id || !$this->academic_year_id) {
            $this->grades_data = [];
            return;
        }

        $grades = SubjectGrade::where([
            'classroom_id' => $this->classroom_id,
            'subject_id' => $this->subject_id,
            'academic_year_id' => $this->academic_year_id,
        ])->get();

        $scores = [];
        foreach ($grades as $grade) {
            $scores[$grade->student_id] = [
                'grade' => $grade->grade,
                'best_tp_id' => $grade->best_tp_id,
                'improvement_tp_id' => $grade->improvement_tp_id,
            ];
        }
        
        // Ensure all students in classroom have an entry
        $students = User::where('role', 'siswa')
            ->whereHas('profiles.profileable', function ($q) {
                $q->where('classroom_id', $this->classroom_id);
            })->get();

        foreach ($students as $student) {
            if (!isset($scores[$student->id])) {
                $scores[$student->id] = [
                    'grade' => null,
                    'best_tp_id' => null,
                    'improvement_tp_id' => null,
                ];
            }
        }
        
        $this->grades_data = $scores;
    }

    public function save(): void
    {
        if (!$this->classroom_id || !$this->subject_id || !$this->academic_year_id) {
            return;
        }

        // Validate duplicates locally
        foreach ($this->grades_data as $studentId => $data) {
            if (!empty($data['best_tp_id']) && !empty($data['improvement_tp_id'])) {
                if ($data['best_tp_id'] == $data['improvement_tp_id']) {
                    $studentName = User::find($studentId)?->name ?? 'Siswa';
                    \Flux::toast(variant: 'danger', text: "TP Terbaik dan TP Perlu Peningkatan tidak boleh sama untuk $studentName.");
                    return;
                }
            }
        }

        DB::transaction(function () {
            foreach ($this->grades_data as $studentId => $data) {
                // Skip if entirely empty (no grade, no TPs) to avoid cluttering DB with empty rows
                // However, if user explicitly inputs 0 or selects a TP, we should save.
                // Grade 0 is a valid grade, so check strictly for null/empty string if not numeric.
                $hasGrade = isset($data['grade']) && $data['grade'] !== '';
                $hasBestTp = !empty($data['best_tp_id']);
                $hasImpTp = !empty($data['improvement_tp_id']);

                if (!$hasGrade && !$hasBestTp && !$hasImpTp) continue;

                SubjectGrade::updateOrCreate(
                    [
                        'student_id' => $studentId,
                        'subject_id' => $this->subject_id,
                        'classroom_id' => $this->classroom_id,
                        'academic_year_id' => $this->academic_year_id,
                    ],
                    [
                        'grade' => $hasGrade ? (float) $data['grade'] : 0,
                        'best_tp_id' => $data['best_tp_id'] ?: null,
                        'improvement_tp_id' => $data['improvement_tp_id'] ?: null,
                    ]
                );
            }
        });

        \Flux::toast('Data penilaian rapor berhasil disimpan.');
    }

    public function with(): array
    {
        $students = [];
        $tps = [];

        if ($this->classroom_id) {
            $students = User::where('role', 'siswa')
                ->whereHas('profiles.profileable', function ($q) {
                    $q->where('classroom_id', $this->classroom_id);
                })
                ->orderBy('name')
                ->get();
        }

        if ($this->subject_id) {
            $tps = SubjectTp::where('subject_id', $this->subject_id)->orderBy('code')->get();
        }

        return [
            'years' => AcademicYear::all(),
            'classrooms' => Classroom::when($this->academic_year_id, fn($q) => $q->where('academic_year_id', $this->academic_year_id))->get(),
            'subjects' => Subject::orderBy('name')->get(),
            'students' => $students,
            'tps' => $tps,
        ];
    }
}; ?>

<div class="p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl" level="1">Nilai Rapor Subjek & TP</flux:heading>
            <flux:subheading>Input nilai akhir dan deskripsi TP untuk rapor.</flux:subheading>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <flux:select wire:model.live="academic_year_id" label="Tahun Ajaran">
            @foreach($years as $year)
                <option value="{{ $year->id }}">{{ $year->name }}</option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="classroom_id" label="Kelas">
            <option value="">Pilih Kelas</option>
            @foreach($classrooms as $room)
                <option value="{{ $room->id }}">{{ $room->name }}</option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="subject_id" label="Mata Pelajaran">
            <option value="">Pilih Mata Pelajaran</option>
            @foreach($subjects as $subject)
                <option value="{{ $subject->id }}">{{ $subject->name }}</option>
            @endforeach
        </flux:select>
    </div>

    @if($classroom_id && $subject_id)
        <div class="border rounded-lg bg-white dark:bg-zinc-900 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left border-collapse min-w-[800px]">
                    <thead class="bg-zinc-50 dark:bg-zinc-800">
                        <tr>
                            <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b w-64">Nama Siswa</th>
                            <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b w-32 text-center">Nilai Final (0-100)</th>
                            <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b">TP Tertinggi (Kompeten)</th>
                            <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b">TP Terendah (Perlu Bimbingan)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($students as $student)
                            <tr wire:key="{{ $student->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <td class="px-4 py-3 text-zinc-900 dark:text-white font-medium align-top">
                                    {{ $student->name }}
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <flux:input 
                                        wire:model="grades_data.{{ $student->id }}.grade" 
                                        type="number" 
                                        min="0" 
                                        max="100" 
                                        step="0.01" 
                                        class="text-center" 
                                    />
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <div class="w-full space-y-1">
                                        <select wire:model="grades_data.{{ $student->id }}.best_tp_id" class="block w-full rounded-md border-0 py-1.5 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm sm:leading-6 dark:bg-zinc-900 dark:text-white dark:ring-zinc-700">
                                            <option value="">-- Pilih TP Terbaik --</option>
                                            @foreach($tps as $tp)
                                                <option value="{{ $tp->id }}">
                                                    {{ $tp->code ? $tp->code . ' - ' : '' }}{{ str($tp->description)->limit(60) }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @if(!empty($grades_data[$student->id]['best_tp_id']))
                                            <p class="text-xs text-green-600 dark:text-green-400 italic">
                                                {{ $tps->find($grades_data[$student->id]['best_tp_id'])?->description }}
                                            </p>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <div class="w-full space-y-1">
                                        <select wire:model="grades_data.{{ $student->id }}.improvement_tp_id" class="block w-full rounded-md border-0 py-1.5 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm sm:leading-6 dark:bg-zinc-900 dark:text-white dark:ring-zinc-700">
                                            <option value="">-- Pilih TP Perlu Bimbingan --</option>
                                            @foreach($tps as $tp)
                                                <option value="{{ $tp->id }}">
                                                    {{ $tp->code ? $tp->code . ' - ' : '' }}{{ str($tp->description)->limit(60) }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @if(!empty($grades_data[$student->id]['improvement_tp_id']))
                                            <p class="text-xs text-orange-600 dark:text-orange-400 italic">
                                                {{ $tps->find($grades_data[$student->id]['improvement_tp_id'])?->description }}
                                            </p>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="p-4 bg-zinc-50 dark:bg-zinc-800 border-t flex justify-end sticky bottom-0 z-10">
                <flux:button variant="primary" icon="check" wire:click="save">Simpan Penilaian Rapor</flux:button>
            </div>
        </div>
    @else
        <div class="flex flex-col items-center justify-center py-12 text-zinc-500 border-2 border-dashed rounded-xl">
            <svg class="w-12 h-12 mb-2 opacity-20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            <p>Silakan pilih kelas dan mata pelajaran untuk memulai penilaian rapor.</p>
        </div>
    @endif
</div>
