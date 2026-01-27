<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Classroom;
use App\Models\AcademicYear;
use App\Models\ReportAttendance;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\DB;

new #[Layout('components.teacher.layouts.app')] class extends Component {
    public ?int $academic_year_id = null;
    public ?int $classroom_id = null;
    public string $semester = '1';

    public array $attendance_data = []; // [student_id => ['sick' => 0, 'permission' => 0, 'absent' => 0]]

    public function mount(): void
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        if ($activeYear) {
            $this->academic_year_id = $activeYear->id;
        }
    }

    public function updatedClassroomId(): void
    {
        $this->loadAttendance();
    }

    public function updatedSemester(): void
    {
        $this->loadAttendance();
    }

    public function loadAttendance(): void
    {
        if (!$this->classroom_id) {
            $this->attendance_data = [];
            return;
        }

        // Verify teacher has access
        $teacher = auth()->user();
        if (!$teacher->hasAccessToClassroom($this->classroom_id)) {
            $this->attendance_data = [];
            \Flux::toast(variant: 'danger', text: 'Anda tidak memiliki akses ke kelas ini.');
            return;
        }

        // Load existing attendance summaries
        $attendances = ReportAttendance::where([
            'classroom_id' => $this->classroom_id,
            'academic_year_id' => $this->academic_year_id,
            'semester' => $this->semester,
        ])->get();

        $this->attendance_data = $attendances->mapWithKeys(function ($att) {
            return [
                $att->student_id => [
                    'sick' => $att->sick,
                    'permission' => $att->permission,
                    'absent' => $att->absent,
                ]
            ];
        })->toArray();
        
        // Ensure all students in classroom have an entry
        $students = User::where('role', 'siswa')
            ->whereHas('profiles.profileable', function ($q) {
                $q->where('classroom_id', $this->classroom_id);
            })->get();

        foreach ($students as $student) {
            if (!isset($this->attendance_data[$student->id])) {
                $this->attendance_data[$student->id] = [
                    'sick' => 0,
                    'permission' => 0,
                    'absent' => 0,
                ];
            }
        }
    }

    public function save(): void
    {
        if (!$this->classroom_id || !$this->academic_year_id) {
            return;
        }

        // Verify teacher has access
        $teacher = auth()->user();
        if (!$teacher->hasAccessToClassroom($this->classroom_id)) {
            \Flux::toast(variant: 'danger', text: 'Anda tidak memiliki akses untuk menyimpan presensi ini.');
            return;
        }

        DB::transaction(function () {
            foreach ($this->attendance_data as $studentId => $data) {
                ReportAttendance::updateOrCreate(
                    [
                        'student_id' => $studentId,
                        'academic_year_id' => $this->academic_year_id,
                        'semester' => $this->semester,
                    ],
                    [
                        'classroom_id' => $this->classroom_id,
                        'sick' => (int)($data['sick'] ?? 0),
                        'permission' => (int)($data['permission'] ?? 0),
                        'absent' => (int)($data['absent'] ?? 0),
                    ]
                );
            }
        });

        \Flux::toast('Data presensi rapor berhasil disimpan.');
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
            'students' => $students,
        ];
    }
}; ?>

<div class="p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl" level="1">Presensi Rapor</flux:heading>
            <flux:subheading>Input rekapitulasi ketidakhadiran siswa untuk ditampilkan di rapor.</flux:subheading>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
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
    </div>

    @if($classroom_id)
        <div class="border rounded-lg bg-white dark:bg-zinc-900 overflow-hidden">
            <table class="w-full text-sm text-left border-collapse">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b">Nama Siswa</th>
                        <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b w-32 text-center">Sakit</th>
                        <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b w-32 text-center">Izin</th>
                        <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b w-32 text-center">Alpha</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach($students as $student)
                        <tr wire:key="{{ $student->id }}">
                            <td class="px-4 py-3 text-zinc-900 dark:text-white font-medium">
                                {{ $student->name }}
                            </td>
                            <td class="px-4 py-3">
                                <flux:input 
                                    wire:model="attendance_data.{{ $student->id }}.sick" 
                                    type="number" 
                                    min="0"
                                    class="text-center"
                                />
                            </td>
                            <td class="px-4 py-3">
                                <flux:input 
                                    wire:model="attendance_data.{{ $student->id }}.permission" 
                                    type="number" 
                                    min="0"
                                    class="text-center"
                                />
                            </td>
                            <td class="px-4 py-3">
                                <flux:input 
                                    wire:model="attendance_data.{{ $student->id }}.absent" 
                                    type="number" 
                                    min="0"
                                    class="text-center"
                                />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="p-4 bg-zinc-50 dark:bg-zinc-800 border-t flex justify-end">
                <flux:button variant="primary" icon="check" wire:click="save">Simpan Presensi</flux:button>
            </div>
        </div>
    @else
        <div class="flex flex-col items-center justify-center py-12 text-zinc-500 border-2 border-dashed rounded-xl">
            <flux:icon icon="calendar-days" class="w-12 h-12 mb-2 opacity-20" />
            <p>Silakan pilih kelas untuk memulai penginputan presensi.</p>
        </div>
    @endif
</div>
