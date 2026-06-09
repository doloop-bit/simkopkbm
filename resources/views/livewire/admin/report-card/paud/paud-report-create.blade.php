<?php

declare(strict_types=1);

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\PaudCpElement;
use App\Models\PaudReportCard;
use App\Traits\Assessments\HandlesPaudReportCard;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('components.layouts.app')] class extends Component {
    use HandlesPaudReportCard;

    public function mount(): void
    {
        if (!auth()->user()->isAdmin() && !auth()->user()->teachesPaudLevel()) {
            abort(403);
        }

        $this->mountHandlesPaudReportCard();
    }

    public function with(): array
    {
        $students = $this->getStudentsInClassroom();
        $existingReports = $this->getExistingReports();
        $cpElements = PaudCpElement::orderBy('order')->get();

        $previewReport = null;
        if ($this->previewReportId) {
            $previewReport = PaudReportCard::with([
                'student',
                'student.profiles.profileable',
                'classroom.level',
                'academicYear',
            ])->find($this->previewReportId);
        }

        return [
            'years' => AcademicYear::orderBy('name', 'desc')->get(),
            'classrooms' => Classroom::whereHas('level', fn($q) => $q->where('education_level', 'PAUD'))
                ->when($this->academic_year_id, fn($q) => $q->where('academic_year_id', $this->academic_year_id))
                ->orderBy('name')->get(),
            'students' => $students,
            'existingReports' => $existingReports,
            'cpElements' => $cpElements,
            'previewReport' => $previewReport,
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
        <x-ui.alert :title="__('Error')" icon="o-x-circle" class="bg-rose-50 text-rose-800 border-rose-100" dismissible>
            {{ session('error') }}
        </x-ui.alert>
    @endif

    <x-ui.header :title="__('Generator Rapor PAUD')" :subtitle="__('Generate rapor hasil belajar PAUD berbasis Kurikulum Merdeka (Capaian Pembelajaran & SKL).')" separator />

    {{-- Global Filters Card --}}
    <x-ui.card shadow class="p-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">
            <x-ui.select 
                wire:model.live="academic_year_id" 
                :label="__('Tahun Ajaran')" 
                :options="$years" 
                :placeholder="__('Pilih Tahun Ajaran')"
                required 
            />
            <x-ui.select 
                wire:model.live="classroom_id" 
                :label="__('Kelas / Rombel')" 
                :options="$classrooms" 
                :placeholder="__('Pilih Kelas')"
                required 
            />
            <x-ui.select 
                wire:model.live="semester" 
                :label="__('Semester')" 
                :options="[
                    ['id' => '1', 'name' => __('Semester 1 (Ganjil)')],
                    ['id' => '2', 'name' => __('Semester 2 (Genap)')],
                ]"
                required 
            />
            <x-ui.select 
                wire:model.live="display_mode" 
                :label="__('Mode Tampilan Rapor')" 
                :options="[
                    ['id' => 'cp', 'name' => __('Per Elemen CP')],
                    ['id' => 'skl', 'name' => __('Per SKL / STTPA')],
                ]"
                required 
            />
        </div>
    </x-ui.card>

    @if ($classroom_id)
        @php
            $activeStudent = $active_student_id ? $students->firstWhere('id', $active_student_id) : null;
            $activeStudentAssessments = $this->getActiveStudentAssessments();

            // Group TPs by CP Element Name
            $groupedTps = collect($activeStudentAssessments)->groupBy(function($item) {
                return $item['cp_element']['name'] ?? __('Tanpa Elemen');
            });

            // Score mappings
            $scoreMap = ['BB' => 1, 'MB' => 2, 'BSH' => 3, 'BSB' => 4];
            $levelMap = [1 => 'BB', 2 => 'MB', 3 => 'BSH', 4 => 'BSB'];

            // Adjusted badge colors: BSB = biru, BSH = hijau, MB = kuning, BB = orange
            $levelColors = [
                'BB' => 'bg-orange-50 text-orange-700 dark:bg-orange-950/20 dark:text-orange-400',
                'MB' => 'bg-yellow-50 text-yellow-700 dark:bg-yellow-950/20 dark:text-yellow-400',
                'BSH' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/20 dark:text-emerald-400', // green
                'BSB' => 'bg-blue-50 text-blue-700 dark:bg-blue-950/20 dark:text-blue-400', // blue
            ];
        @endphp

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            {{-- Left Column: Student List (1 column) --}}
            <div class="lg:col-span-1 space-y-4">
                <x-ui.card shadow padding="false">
                    <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-900/50 rounded-t-[2rem]">
                        <h3 class="font-black text-xs uppercase tracking-widest text-slate-500">{{ __('Daftar Siswa') }}</h3>
                        <div class="flex gap-2">
                            <x-ui.button :label="__('Pilih Semua')" wire:click="$set('selected_students', {{ $students->pluck('id') }})" class="btn-ghost btn-xs text-primary font-bold !text-[9px]" />
                            <x-ui.button :label="__('Batal')" wire:click="$set('selected_students', [])" class="btn-ghost btn-xs font-bold !text-[9px]" />
                        </div>
                    </div>
                    
                    <div class="divide-y divide-slate-100 dark:divide-slate-800 max-h-[60vh] overflow-y-auto p-2 space-y-1">
                        @foreach ($students as $student)
                            @php
                                $hasReport = $existingReports->firstWhere('student_id', $student->id);
                            @endphp
                            <div class="flex items-center justify-between p-3 transition-all rounded-2xl {{ $active_student_id === $student->id ? 'bg-indigo-50 dark:bg-indigo-950/30 border-l-4 border-indigo-600' : 'hover:bg-slate-50 dark:hover:bg-slate-900/50' }}">
                                <div class="flex items-center gap-3 flex-1 cursor-pointer" wire:click="selectActiveStudent({{ $student->id }})">
                                    <div class="size-8 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center font-bold text-xs text-indigo-600">
                                        {{ substr($student->name, 0, 1) }}
                                    </div>
                                    <div class="min-w-0">
                                        <span class="block text-xs font-bold text-slate-800 dark:text-slate-200 capitalize truncate max-w-[120px]">{{ $student->name }}</span>
                                        <span class="block text-[9px] text-slate-400 font-mono">{{ $student->nis ?? $student->username }}</span>
                                    </div>
                                </div>
                                
                                <div class="flex items-center gap-2">
                                    {{-- Status Badge --}}
                                    @if ($hasReport)
                                        @if ($hasReport->status === 'published')
                                            <span class="size-2 rounded-full bg-emerald-500" title="Published"></span>
                                        @else
                                            <span class="size-2 rounded-full bg-amber-500" title="Draft"></span>
                                        @endif
                                    @else
                                        <span class="size-2 rounded-full bg-slate-300" title="Belum dibuat"></span>
                                    @endif

                                    <input 
                                        type="checkbox" 
                                        wire:model.live="selected_students" 
                                        value="{{ $student->id }}" 
                                        class="checkbox checkbox-xs checkbox-primary" 
                                    />
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-ui.card>
            </div>

            {{-- Right Column: Student Editor (3 columns) --}}
            <div class="lg:col-span-3 space-y-6">
                @if ($activeStudent)
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
                        {{-- Editor Panel (3 cols) --}}
                        <div class="md:col-span-3 space-y-6">
                            <x-ui.card shadow>
                                <div class="flex items-center gap-4 border-b border-slate-100 dark:border-slate-800 pb-4 mb-6">
                                    <div class="size-12 rounded-2xl bg-indigo-600 text-white flex items-center justify-center font-black text-lg">
                                        {{ substr($activeStudent->name, 0, 1) }}
                                    </div>
                                    <div>
                                        <h4 class="font-black text-slate-800 dark:text-white capitalize leading-none mb-1">{{ $activeStudent->name }}</h4>
                                        <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">{{ __('Isian Catatan Rapor') }}</p>
                                    </div>
                                </div>

                                <form wire:submit.prevent="generateSingleReport({{ $activeStudent->id }})" class="space-y-6">
                                    @foreach ($cpElements as $cp)
                                        <div class="space-y-2">
                                            <div class="flex items-center gap-2">
                                                <span class="px-2 py-0.5 bg-primary/10 text-primary text-[10px] font-black rounded-md">{{ strtoupper($cp->code) }}</span>
                                                <span class="text-xs font-bold text-slate-600 dark:text-slate-400">{{ $cp->name }}</span>
                                            </div>
                                            <x-ui.textarea 
                                                wire:model="cp_summaries.{{ $activeStudent->id }}.{{ $cp->id }}" 
                                                rows="4" 
                                                :placeholder="__('Tuliskan narasi perkembangan untuk elemen :cp...', ['cp' => $cp->name])" 
                                            />
                                        </div>
                                    @endforeach

                                    <x-ui.textarea 
                                        wire:model="teacher_notes.{{ $activeStudent->id }}" 
                                        :label="__('Catatan Naratif Keseluruhan')" 
                                        rows="3" 
                                        :placeholder="__('Tuliskan catatan naratif keseluruhan untuk siswa...')" 
                                    />

                                    <div class="flex items-center gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                                        <x-ui.button type="submit" :label="__('Simpan & Generate Rapor')" icon="o-sparkles" class="btn-primary shadow-xl shadow-primary/20" spinner="generateSingleReport" />
                                    </div>
                                </form>
                            </x-ui.card>
                        </div>

                        {{-- Reference Panel (2 cols) --}}
                        <div class="md:col-span-2 space-y-6">
                            <x-ui.card shadow class="bg-slate-50/50 dark:bg-slate-900/20">
                                <div class="space-y-6">
                                    <h5 class="text-xs font-black uppercase tracking-widest text-slate-500 flex items-center gap-2 pb-2 border-b">
                                        <x-ui.icon name="o-presentation-chart-line" class="size-4 text-indigo-600" />
                                        {{ __('Acuan Penilaian TP') }}
                                    </h5>
                                    
                                    @if (!empty($activeStudentAssessments))
                                        <div class="space-y-6 max-h-[70vh] overflow-y-auto pr-2">
                                            @foreach ($groupedTps as $cpName => $tps)
                                                @php
                                                    $totalScore = 0;
                                                    $count = 0;
                                                    foreach ($tps as $tp) {
                                                        $assessment = count($tp['assessments']) > 0 ? $tp['assessments'][0] : null;
                                                        if ($assessment && isset($scoreMap[$assessment['level']])) {
                                                            $totalScore += $scoreMap[$assessment['level']];
                                                            $count++;
                                                        }
                                                    }
                                                    $averageScore = $count > 0 ? $totalScore / $count : 0;
                                                    
                                                    // Map average back to a grade label
                                                    $avgLevel = '';
                                                    if ($averageScore > 0) {
                                                        $rounded = (int) round($averageScore);
                                                        $avgLevel = $levelMap[$rounded] ?? '';
                                                    }
                                                    $avgBadgeColor = $avgLevel ? ($levelColors[$avgLevel] ?? 'bg-slate-100 text-slate-700') : 'bg-slate-100 text-slate-400';
                                                @endphp

                                                <div class="space-y-3">
                                                    {{-- CP Header & Average --}}
                                                    <div class="flex items-start justify-between gap-4 p-3 bg-indigo-50/30 dark:bg-indigo-950/10 rounded-xl border border-indigo-100/50 dark:border-indigo-950/30">
                                                        <div class="min-w-0">
                                                            <span class="block text-[10px] font-black uppercase tracking-wider text-slate-400 leading-none mb-1">{{ __('Capaian Elemen') }}</span>
                                                            <span class="block text-xs font-black text-slate-800 dark:text-slate-200 leading-tight">{{ $cpName }}</span>
                                                        </div>
                                                        @if ($averageScore > 0)
                                                            <div class="text-right shrink-0">
                                                                <x-ui.badge :label="__('Rerata: :lvl (:val)', ['lvl' => $avgLevel, 'val' => number_format($averageScore, 1)])" class="border-none text-[9px] font-black italic {{ $avgBadgeColor }}" />
                                                            </div>
                                                        @else
                                                            <x-ui.badge :label="__('Belum Dinilai')" class="border-none text-[9px] font-black italic bg-slate-100 text-slate-400" />
                                                        @endif
                                                    </div>

                                                    {{-- TPs List under this CP --}}
                                                    <div class="space-y-3 pl-2 border-l border-slate-100 dark:border-slate-800/80">
                                                        @foreach ($tps as $tp)
                                                            @php
                                                                $assessment = count($tp['assessments']) > 0 ? $tp['assessments'][0] : null;
                                                                $badgeColor = $assessment ? ($levelColors[$assessment['level']] ?? 'bg-slate-100 text-slate-700') : 'bg-slate-100 text-slate-400';
                                                            @endphp
                                                            <div class="p-3.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-100/80 dark:border-slate-800/50 shadow-sm space-y-1.5">
                                                                <div class="flex items-start justify-between gap-3">
                                                                    <span class="text-[9px] font-bold text-slate-400 font-mono">{{ $tp['code'] }}</span>
                                                                    <x-ui.badge :label="$assessment ? $assessment['level'] : __('Belum Dinilai')" class="border-none text-[8px] font-black italic {{ $badgeColor }}" />
                                                                </div>
                                                                <p class="text-xs font-medium text-slate-700 dark:text-slate-300 leading-relaxed">{{ $tp['description'] }}</p>
                                                                @if ($assessment && $assessment['notes'])
                                                                    <div class="p-2 rounded-lg bg-slate-50 dark:bg-slate-950/50 text-[10px] text-slate-500 italic">
                                                                        <span class="font-bold text-[8px] uppercase tracking-wider block text-slate-400 mb-0.5">{{ __('Catatan') }}:</span>
                                                                        {{ $assessment['notes'] }}
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <p class="text-xs text-slate-400 italic">{{ __('Belum ada data penilaian TP untuk semester ini.') }}</p>
                                    @endif
                                </div>
                            </x-ui.card>
                        </div>
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center py-32 text-slate-300 dark:text-slate-700 border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-[32px] bg-slate-50/50 dark:bg-slate-900/50 transition-all">
                        <x-ui.icon name="o-user" class="size-20 mb-6 opacity-20" />
                        <p class="text-xs font-black uppercase tracking-widest italic animate-pulse">{{ __('Pilih Siswa di Sebelah Kiri Untuk Mengisi Narasi') }}</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Bulk actions container --}}
        @if (count($selected_students) > 0)
            <x-ui.card shadow class="p-6 flex items-center justify-between border border-dashed border-indigo-100 dark:border-indigo-900">
                <div class="flex items-center gap-3">
                    <x-ui.icon name="o-sparkles" class="size-6 text-indigo-600 animate-pulse" />
                    <div>
                        <span class="block text-sm font-bold text-slate-800 dark:text-slate-200">{{ __('Tindakan Massal (Bulk Action)') }}</span>
                        <span class="block text-xs text-slate-400">{{ __('Generate Rapor draf untuk :count siswa yang terpilih sekaligus.', ['count' => count($selected_students)]) }}</span>
                    </div>
                </div>
                <x-ui.button wire:click="generateReports" :label="__('Generate Massal')" icon="o-document-duplicate" class="btn-indigo shadow-lg shadow-indigo-100 dark:shadow-none" spinner="generateReports" />
            </x-ui.card>
        @endif

        {{-- Existing Reports List --}}
        @if (!$existingReports->isEmpty())
            <x-ui.card shadow padding="false">
                <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-900/50 rounded-t-[2rem]">
                    <h3 class="font-black text-slate-800 dark:text-white flex items-center gap-2 italic uppercase tracking-tight text-sm">
                        <x-ui.icon name="o-document-check" class="size-5 text-emerald-500" />
                        {{ __('Arsip Rapor PAUD') }}
                    </h3>
                    <x-ui.badge :label="$existingReports->count() . ' Siswa'" class="bg-indigo-50 text-indigo-700 border-none font-black italic px-3 py-1 text-[10px]" />
                </div>

                <x-ui.table :headers="[
                    ['key' => 'student_name', 'label' => __('Nama Siswa')],
                    ['key' => 'report_status', 'label' => __('Status'), 'class' => 'text-center'],
                    ['key' => 'portal_link', 'label' => __('Link Portal'), 'class' => 'text-center'],
                    ['key' => 'actions', 'label' => '', 'class' => 'text-right']
                ]" :rows="$existingReports">
                    @scope('cell_student_name', $report)
                        <div class="flex flex-col">
                            <span class="font-bold text-slate-900 dark:text-white">{{ $report->student->name }}</span>
                            <span class="text-[10px] text-slate-400 font-mono">Mode: {{ strtoupper($report->display_mode) }}</span>
                        </div>
                    @endscope

                    @scope('cell_report_status', $report)
                        @if ($report->status === 'published')
                            <x-ui.badge label="PUBLISHED" class="bg-emerald-50 text-emerald-700 border-none text-[8px] font-black italic tracking-widest" />
                        @else
                            <x-ui.badge label="DRAFT" class="bg-slate-100 text-slate-500 border-none text-[8px] font-black italic tracking-widest" />
                        @endif
                    @endscope

                    @scope('cell_portal_link', $report)
                        @if ($report->status === 'published' && $report->access_token)
                            <a href="{{ route('public.paud-report', $report->access_token) }}" target="_blank" class="text-xs text-indigo-600 hover:underline font-mono">
                                {{ Str::limit($report->access_token, 12) }}...
                            </a>
                        @else
                            <span class="text-xs text-slate-400 italic">-</span>
                        @endif
                    @endscope

                    @scope('cell_actions', $report)
                        <div class="flex justify-end gap-2">
                            <x-ui.button icon="o-eye" wire:click="previewReport({{ $report->id }})" class="btn-ghost btn-sm text-sky-500 hover:bg-sky-50 transition-colors" spinner />
                            <x-ui.button icon="o-arrow-down-tray" wire:click="downloadPdf({{ $report->id }})" class="btn-ghost btn-sm text-indigo-600 hover:bg-indigo-50 transition-colors" spinner />
                            @if ($report->status === 'draft')
                                <x-ui.button icon="o-check-badge" wire:click="publishReport({{ $report->id }})" class="btn-ghost btn-sm text-emerald-500 hover:bg-emerald-50 transition-colors" spinner />
                            @else
                                <x-ui.button icon="o-arrow-uturn-left" wire:click="unpublishReport({{ $report->id }})" class="btn-ghost btn-sm text-amber-500 hover:bg-amber-50 transition-colors" spinner />
                            @endif
                            <x-ui.button icon="o-trash" wire:click="deleteReport({{ $report->id }})" wire:confirm="{{ __('Hapus rapor ini secara permanen?') }}" class="btn-ghost btn-sm text-slate-400 hover:text-rose-600 transition-colors" spinner />
                        </div>
                    @endscope
                </x-ui.table>
            </x-ui.card>
        @endif
    @endif

    {{-- Preview Modal --}}
    <x-ui.modal wire:model="showPreview" :title="__('Pratinjau Rapor PAUD')" persistent class="max-w-4xl">
        @if ($previewReport)
            <div class="p-4 bg-slate-100 dark:bg-slate-900 rounded-2xl shadow-inner overflow-y-auto max-h-[70vh]">
                <div class="max-w-3xl mx-auto bg-white p-8 shadow-2xl text-slate-900 rounded-lg">
                    @include('pdf._paud_rapor_content', [
                        'reportCard' => $previewReport,
                        'student' => $previewReport->student,
                        'studentProfile' => $previewReport->student->profiles()->where('profileable_type', \App\Models\StudentProfile::class)->first()?->profileable,
                        'classroom' => $previewReport->classroom,
                        'academicYear' => $previewReport->academicYear,
                    ])
                </div>
            </div>
            <div class="flex justify-end gap-3 pt-6">
                <x-ui.button :label="__('Tutup')" wire:click="closePreview" />
                <x-ui.button :label="__('Download PDF')" icon="o-arrow-down-tray" class="btn-primary shadow-lg shadow-primary/20" wire:click="downloadPdf({{ $previewReport->id }})" spinner />
            </div>
        @endif
    </x-ui.modal>
</div>
