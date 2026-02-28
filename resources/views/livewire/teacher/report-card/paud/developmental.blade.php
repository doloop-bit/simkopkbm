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

new #[Layout('components.teacher.layouts.app')] class extends Component {
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
            session()->flash('error', __('Anda tidak memiliki akses untuk menyimpan penilaian ini.'));
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

        session()->flash('success', __('Penilaian perkembangan anak berhasil disimpan.'));
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

<div class="p-6 space-y-8 text-slate-900 dark:text-white pb-24 md:pb-6">
    @if (session('success'))
        <x-ui.alert :title="__('Sukses')" icon="o-check-circle" class="bg-emerald-50 text-emerald-800 border-emerald-100" dismissible>
            {{ session('success') }}
        </x-ui.alert>
    @endif

    @if (session('error'))
        <x-ui.alert :title="__('Kesalahan')" icon="o-x-circle" class="bg-rose-50 text-rose-800 border-rose-100" dismissible>
            {{ session('error') }}
        </x-ui.alert>
    @endif

    <x-ui.header :title="__('Naratif Penilaian PAUD')" :subtitle="__('Input narasi penilaian perkembangan anak untuk 6 aspek perkembangan utama.')" separator />

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
            wire:model.live="student_id" 
            :label="__('Nama Anak')" 
            :placeholder="__('Pilih Anak')"
            :options="$students"
        />
    </div>

    {{-- Student Info Card --}}
    @if($selectedStudent)
        <div class="p-6 rounded-[2rem] bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-800/50 flex items-center gap-6 shadow-sm">
            <div class="size-16 rounded-3xl bg-white dark:bg-indigo-800 flex items-center justify-center font-black text-2xl text-indigo-600 dark:text-indigo-400 shadow-sm ring-1 ring-indigo-100 dark:ring-indigo-700">
                {{ substr($selectedStudent->name, 0, 1) }}
            </div>
            <div class="flex-1 min-w-0">
                <h3 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight leading-none mb-1 capitalize">{{ $selectedStudent->name }}</h3>
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest flex items-center gap-1.5 italic">
                    <span class="size-1.5 bg-indigo-400 rounded-full animate-pulse"></span>
                    {{ __('Laporan Perkembangan Semester') }} {{ $semester }}
                </p>
            </div>
        </div>
    @endif

    @if($student_id)
        @php
            $aspectTypeLabels = [
                'nilai_agama' => ['label' => 'Nilai Agama & Budi Pekerti', 'color' => 'bg-emerald-500', 'text' => 'text-emerald-700', 'bg' => 'bg-emerald-50', 'icon' => 'o-heart'],
                'fisik_motorik' => ['label' => 'Fisik-Motorik', 'color' => 'bg-teal-500', 'text' => 'text-teal-700', 'bg' => 'bg-teal-50', 'icon' => 'o-hand-raised'],
                'kognitif' => ['label' => 'Kognitif', 'color' => 'bg-blue-500', 'text' => 'text-blue-700', 'bg' => 'bg-blue-50', 'icon' => 'o-light-bulb'],
                'bahasa' => ['label' => 'Bahasa', 'color' => 'bg-amber-500', 'text' => 'text-amber-700', 'bg' => 'bg-amber-50', 'icon' => 'o-chat-bubble-left-right'],
                'sosial_emosional' => ['label' => 'Sosial-Emosional', 'color' => 'bg-rose-500', 'text' => 'text-rose-700', 'bg' => 'bg-rose-50', 'icon' => 'o-users'],
                'seni' => ['label' => 'Seni & Kreativitas', 'color' => 'bg-indigo-500', 'text' => 'text-indigo-700', 'bg' => 'bg-indigo-50', 'icon' => 'o-paint-brush'],
            ];
        @endphp

        <div class="space-y-8">
            @foreach($aspectsByType as $aspectType => $aspects)
                @php
                    $typeInfo = $aspectTypeLabels[$aspectType] ?? ['label' => $aspectType, 'color' => 'bg-slate-500', 'text' => 'text-slate-700', 'bg' => 'bg-slate-50', 'icon' => 'o-document-text'];
                @endphp
                <x-ui.card shadow padding="false" class="overflow-hidden border-none ring-1 ring-slate-100 dark:ring-slate-800">
                    {{-- Aspect Type Header --}}
                    <div class="p-6 {{ $typeInfo['bg'] }} dark:bg-slate-800/50 flex items-center justify-between border-b border-slate-100 dark:border-slate-800">
                        <div class="flex items-center gap-4">
                            <div class="size-10 rounded-xl {{ $typeInfo['color'] }} flex items-center justify-center shadow-lg shadow-{{ str_replace('bg-', '', $typeInfo['color']) }}/20">
                                <x-ui.icon name="{{ $typeInfo['icon'] }}" class="size-5 text-white" />
                            </div>
                            <h3 class="text-lg font-black tracking-tight {{ $typeInfo['text'] }} italic">{{ $typeInfo['label'] }}</h3>
                        </div>
                        <x-ui.badge :label="count($aspects) . ' ' . __('Indikator')" class="bg-white/50 text-slate-500 border-none font-bold italic text-[9px]" />
                    </div>

                    {{-- Aspects --}}
                    <div class="p-8 space-y-6">
                        @foreach($aspects as $aspect)
                            <div wire:key="aspect-{{ $aspect->id }}" class="group">
                                <x-ui.textarea 
                                    wire:model="assessments_data.{{ $aspect->id }}" 
                                    :label="$aspect->name" 
                                    rows="4"
                                    :placeholder="__('Tuliskan deskripsi naratif untuk indikator ini...')"
                                    class="border-none bg-slate-50/50 dark:bg-slate-900 group-hover:bg-white dark:group-hover:bg-slate-800 transition-colors shadow-none focus:ring-1 italic text-sm"
                                />
                                @if($aspect->description)
                                    <p class="mt-2 text-[10px] text-slate-400 italic px-1 leading-relaxed">{{ $aspect->description }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </x-ui.card>
            @endforeach

            <div class="sticky bottom-6 flex justify-end">
                <x-ui.button :label="__('Simpan Seluruh Penilaian')" icon="o-check" class="btn-primary shadow-2xl shadow-primary/30 py-4 px-8" wire:click="save" spinner="save" />
            </div>
        </div>
    @else
        <div class="flex flex-col items-center justify-center py-32 text-slate-300 dark:text-slate-700 border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-[32px] bg-slate-50/50 dark:bg-slate-900/50 transition-all">
            <x-ui.icon name="o-face-smile" class="size-20 mb-6 opacity-20" />
            <p class="text-sm font-black uppercase tracking-widest italic animate-pulse">{{ __('Pilih Kelas & Anak Untuk Memulai Penilaian') }}</p>
        </div>
    @endif
</div>
