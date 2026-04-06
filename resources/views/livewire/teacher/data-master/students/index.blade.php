<?php

use App\Models\StudentProfile;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('components.teacher.layouts.app')] class extends Component {
    public bool $periodicModal = false;
    public float $weight = 0;
    public float $height = 0;
    public float $head_circumference = 0;
    public int $semester = 1;
    public ?int $current_academic_year_id = null;
    public bool $hasExistingPeriodicData = false;
    public ?string $periodicDataLastUpdated = null;
    public ?StudentProfile $editingStudent = null;

    public function mount(): void
    {
        $this->current_academic_year_id = \App\Models\AcademicYear::where('is_active', true)->first()?->id;
    }

    public function openPeriodic(StudentProfile $student): void
    {
        $this->editingStudent = $student;
        $this->loadPeriodicData();
        $this->periodicModal = true;
    }

    public function updatedSemester(): void
    {
        $this->loadPeriodicData();
    }

    protected function loadPeriodicData(): void
    {
        if ($this->editingStudent) {
            $existingRecord = \App\Models\StudentPeriodicRecord::where('student_profile_id', $this->editingStudent->id)
                ->where('academic_year_id', $this->current_academic_year_id)
                ->where('semester', $this->semester)
                ->first();

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

    public function savePeriodic(int $studentProfileId): void
    {
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

        $this->periodicModal = false;
        $this->reset(['weight', 'height', 'head_circumference', 'semester', 'hasExistingPeriodicData', 'periodicDataLastUpdated']);
        session()->flash('success', __('Data periodik berhasil disimpan!'));
    }

    public function with(): array
    {
        $teacher = auth()->user();
        $assignedClassroomIds = $teacher->getAssignedClassroomIds();

        $students = StudentProfile::whereIn('classroom_id', $assignedClassroomIds)
            ->with(['profile.user', 'classroom.level', 'classroom.academicYear'])
            ->orderBy('classroom_id')
            ->get();

        return [
            'students' => $students,
            'assignedClassrooms' => $teacher->assignedClassrooms()->with('level', 'academicYear')->get(),
        ];
    }
}; ?>

<div class="p-6 space-y-6 text-slate-900 dark:text-white pb-24 md:pb-6">
    {{-- Header --}}
    <x-ui.header :title="__('Daftar Siswa')" :subtitle="__('Siswa di kelas yang Anda ampu')" separator />

    @if (session('success'))
        <x-ui.alert :title="__('Berhasil')" icon="o-check-circle" class="bg-emerald-50 text-emerald-800 border-emerald-100" dismissible>
            {{ session('success') }}
        </x-ui.alert>
    @endif

    {{-- Classroom Filter Info --}}
    <x-ui.alert icon="o-information-circle" class="bg-blue-50 text-blue-800 border-blue-100 shadow-sm">
        <div class="flex flex-col gap-2">
            <p class="font-black text-[11px] uppercase tracking-widest">{{ __('Kelas yang Anda Ampu:') }}</p>
            <div class="flex flex-wrap gap-2">
                @foreach($assignedClassrooms as $classroom)
                    <x-ui.badge class="bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 text-[10px] font-black border-none ring-1 ring-blue-200 dark:ring-blue-800">
                        {{ $classroom->name }} ({{ $classroom->academicYear->name }})
                    </x-ui.badge>
                @endforeach
            </div>
        </div>
    </x-ui.alert>

    {{-- Students Table --}}
    <x-ui.card shadow padding="false">
        <x-ui.table 
            :headers="[
                ['key' => 'name', 'label' => __('Nama Siswa')],
                ['key' => 'nis', 'label' => __('NIS / NISN')],
                ['key' => 'classroom_name', 'label' => __('Kelas')],
                ['key' => 'level_name', 'label' => __('Jenjang')],
                ['key' => 'status', 'label' => __('Status')],
                ['key' => 'actions', 'label' => '', 'class' => 'text-right']
            ]" 
            :rows="$students"
        >
            @scope('cell_name', $student)
                <div class="flex items-center gap-3">
                    <x-ui.avatar 
                        :image="($student->photo && Storage::disk('public')->exists($student->photo)) ? '/storage/'.$student->photo : null" 
                        fallback="o-user" 
                        class="!w-10 !h-10 rounded-lg shadow-sm"
                    />
                    <div class="flex flex-col">
                        <span class="font-bold text-slate-900 dark:text-white">{{ $student->profile->user->name }}</span>
                        <span class="text-[10px] text-slate-400 font-mono tracking-tighter">{{ $student->profile->user->email }}</span>
                    </div>
                </div>
            @endscope

            @scope('cell_nis', $student)
                <div class="flex flex-col">
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300 font-mono italic">{{ $student->nis ?? '-' }}</span>
                    <span class="text-[10px] uppercase font-bold text-slate-400 tracking-tight">NISN: {{ $student->nisn ?? '-' }}</span>
                </div>
            @endscope

            @scope('cell_classroom_name', $student)
                <x-ui.badge :label="$student->classroom->name" class="bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 text-[10px] font-black" />
            @endscope

            @scope('cell_level_name', $student)
                <span class="text-xs text-slate-500 font-medium">{{ $student->classroom->level->name }}</span>
            @endscope

            @scope('cell_status', $student)
                @if($student->profile->user->is_active)
                    <x-ui.badge :label="__('Aktif')" class="bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 text-[10px] font-black" />
                @else
                    <x-ui.badge :label="__('Non-Aktif')" class="bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-500 text-[10px] font-black" />
                @endif
            @endscope

            @scope('cell_actions', $student)
                <div class="flex justify-end gap-1">
                    <x-ui.button icon="o-chart-bar" wire:click="openPeriodic({{ $student->id }})" ghost class="hover:text-primary" />
                </div>
            @endscope
        </x-ui.table>

        @if($students->isEmpty())
            <div class="py-12 text-center text-slate-400 italic text-sm">
                {{ __('Belum ada siswa di kelas yang Anda ampu.') }}
            </div>
        @else
            <div class="p-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex justify-end">
                <div class="text-[10px] font-black uppercase text-slate-400 tracking-widest">
                    {{ __('Total:') }} <span class="text-slate-900 dark:text-white">{{ $students->count() }}</span> {{ __('siswa') }}
                </div>
            </div>
        @endif
    </x-ui.card>

    {{-- Periodic Data Modal --}}
    <x-ui.modal wire:model="periodicModal" persistent>
        <x-ui.header :title="__('Data Periodik Siswa')" :subtitle="__('Input data berat badan, tinggi, dan lingkar kepala.')" separator />

        <form wire:submit.prevent="savePeriodic({{ $editingStudent->id ?? 0 }})" class="space-y-6">
            @if($hasExistingPeriodicData)
                <x-ui.alert :title="__('Data sudah ada')" icon="o-information-circle" class="bg-blue-50 text-blue-800 border-blue-100 shadow-sm">
                    {{ __('Terakhir diupdate') }} {{ $periodicDataLastUpdated }}
                </x-ui.alert>
            @else
                <x-ui.alert :title="__('Belum ada data')" icon="o-exclamation-triangle" class="bg-amber-50 text-amber-800 border-amber-100 shadow-sm">
                    {{ __('Belum ada data untuk semester ini.') }}
                </x-ui.alert>
            @endif

            <div class="space-y-4">
                <x-ui.select 
                    wire:model.live="semester" 
                    :label="__('Semester')" 
                    :options="[
                        ['id' => 1, 'name' => __('Ganjil (1)')],
                        ['id' => 2, 'name' => __('Genap (2)')],
                    ]"
                    required
                />

                <x-ui.input type="number" step="0.5" wire:model="weight" :label="__('Berat Badan (kg)')" suffix="kg" required />
                <x-ui.input type="number" step="1" wire:model="height" :label="__('Tinggi Badan (cm)')" suffix="cm" required />
                <x-ui.input type="number" step="0.1" wire:model="head_circumference" :label="__('Lingkar Kepala (cm)')" suffix="cm" required />
            </div>

            <div class="flex justify-end gap-2 pt-4 border-t border-slate-100 dark:border-slate-800">
                <x-ui.button :label="__('Batal')" ghost @click="show = false" />
                <x-ui.button :label="__('Simpan Data')" type="submit" class="btn-primary" spinner="savePeriodic" />
            </div>
        </form>
    </x-ui.modal>
</div>
