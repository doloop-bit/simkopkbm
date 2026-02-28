<div class="p-6 space-y-8 text-slate-900 dark:text-white pb-24 md:pb-6">
    <x-ui.header :title="__('Generator Rapor')" :subtitle="__('Generate dan kelola lembar rapor siswa secara otomatis berdasarkan data penilaian kompetensi.')" separator />

    <div class="grid gap-8 lg:grid-cols-3">
        {{-- Left Column: Generator Form --}}
        <div class="lg:col-span-2 space-y-8">
            <x-ui.card shadow>
                <form wire:submit="generateReportCards" class="space-y-8 p-2">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <x-ui.select 
                            wire:model.live="academicYearId" 
                            :label="__('Tahun Ajaran')" 
                            :options="$academicYears" 
                            :placeholder="__('Pilih Tahun Ajaran')"
                            required 
                        />

                        <x-ui.select 
                            wire:model.live="classroomId" 
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

                        <x-ui.input :label="__('Jenis Kurikulum')" value="Kurikulum Merdeka" readonly disabled class="bg-slate-50 border-none italic font-bold text-slate-400" />
                    </div>

                    @if (count($students) > 0)
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <label class="text-xs font-black uppercase tracking-widest text-slate-500">{{ __('Pilih Siswa Target') }}</label>
                                <div class="flex gap-2">
                                    <x-ui.button :label="__('Pilih Semua')" wire:click="$set('selectedStudents', {{ $students->pluck('id') }})" class="btn-ghost btn-xs text-primary font-bold" />
                                    <x-ui.button :label="__('Batal')" wire:click="$set('selectedStudents', [])" class="btn-ghost btn-xs font-bold" />
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 max-h-64 overflow-y-auto p-4 bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-inner">
                                @foreach ($students as $student)
                                    <div class="flex items-center gap-3 p-2 bg-white dark:bg-slate-800 rounded-xl shadow-sm ring-1 ring-slate-100 dark:ring-slate-700">
                                        <x-ui.checkbox 
                                            wire:model="selectedStudents" 
                                            value="{{ $student->id }}" 
                                            :label="$student->profile->user->name ?? 'N/A'" 
                                        />
                                    </div>
                                @endforeach
                            </div>
                            @error('selectedStudents') <p class="text-[10px] font-bold text-rose-500 italic">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    <div class="grid grid-cols-1 gap-6">
                        <x-ui.textarea wire:model="characterNotes" :label="__('Catatan Pertumbuhan Karakter')" rows="2" :placeholder="__('Contoh: Menunjukkan kemandirian yang sangat baik dalam kegiatan sekolah...')" />
                        <x-ui.textarea wire:model="teacherNotes" :label="__('Catatan Wali Kelas / Guru')" rows="2" :placeholder="__('Contoh: Tingkatkan fokus saat pembelajaran di kelas...')" />
                        @if(auth()->user()->isAdmin())
                             <x-ui.textarea wire:model="principalNotes" :label="__('Catatan / Pesan Kepala Sekolah')" rows="2" />
                        @endif
                    </div>

                    <div class="flex items-center gap-4 pt-4">
                        <x-ui.button type="submit" :label="__('Proses & Generate Rapor')" icon="o-sparkles" class="btn-primary shadow-xl shadow-primary/20" spinner="generateReportCards" />
                        <x-ui.button type="button" :label="__('Reset Form')" icon="o-arrow-path" class="btn-ghost" wire:click="$refresh" />
                    </div>
                </form>
            </x-ui.card>

            {{-- List of Generated Reports --}}
            <x-ui.card shadow padding="false">
                <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <h3 class="font-black text-slate-800 dark:text-white flex items-center gap-2 italic uppercase tracking-tight text-sm">
                        <x-ui.icon name="o-document-check" class="size-5 text-emerald-500" />
                        {{ __('Arsip Rapor Terbit') }}
                    </h3>
                    <x-ui.badge :label="$existingReports->count() . ' File'" class="bg-indigo-50 text-indigo-700 border-none font-black italic px-3 py-1 text-[10px]" />
                </div>

                <x-ui.table :headers="[
                    ['key' => 'student_name', 'label' => __('Nama Siswa')],
                    ['key' => 'report_status', 'label' => __('Status'), 'class' => 'text-center'],
                    ['key' => 'actions', 'label' => '', 'class' => 'text-right']
                ]" :rows="$existingReports">
                    @scope('cell_student_name', $report)
                        <div class="flex flex-col">
                            <span class="font-bold text-slate-900 dark:text-white">{{ $report->student->name }}</span>
                            <span class="text-[10px] text-slate-400 font-mono tracking-tighter">{{ $report->student->nis ?? $report->student->username }}</span>
                        </div>
                    @endscope

                    @scope('cell_report_status', $report)
                        <x-ui.badge 
                            :label="strtoupper($report->status)" 
                            class="{{ $report->status === 'final' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }} border-none text-[8px] font-black italic tracking-widest px-2 py-0.5" 
                        />
                    @endscope

                    @scope('cell_actions', $report)
                        <div class="flex justify-end gap-2">
                            <x-ui.button icon="o-eye" wire:click="previewReportCard({{ $report->id }})" class="btn-ghost btn-sm text-sky-500 hover:bg-sky-50 transition-colors" spinner />
                            <x-ui.button icon="o-arrow-down-tray" wire:click="exportPdf({{ $report->id }})" class="btn-ghost btn-sm text-indigo-600 hover:bg-indigo-50 transition-colors" spinner />
                            <x-ui.button icon="o-trash" wire:click="deleteReportCard({{ $report->id }})" wire:confirm="{{ __('Hapus berkas rapor ini secara permanen?') }}" class="btn-ghost btn-sm text-slate-400 hover:text-rose-600 transition-colors" spinner />
                        </div>
                    @endscope
                </x-ui.table>

                @if($existingReports->isEmpty())
                    <div class="py-24 flex flex-col items-center justify-center opacity-40">
                        <x-ui.icon name="o-document-text" class="size-16 mb-4 text-slate-400" />
                        <p class="text-sm italic font-medium uppercase tracking-widest text-slate-500">{{ __('Belum ada arsip rapor') }}</p>
                    </div>
                @endif
            </x-ui.card>
        </div>

        {{-- Right Column: Info & Legend --}}
        <div class="space-y-8">
            <div class="p-8 bg-indigo-600 text-white rounded-[2.5rem] shadow-xl shadow-indigo-200 dark:shadow-none relative overflow-hidden group">
                <div class="absolute -right-12 -top-12 size-48 bg-white/10 rounded-full blur-3xl group-hover:scale-110 transition-transform duration-700"></div>
                <h3 class="font-black text-xl mb-6 flex items-center gap-3 italic">
                    <x-ui.icon name="o-information-circle" class="size-6 text-indigo-200" />
                    {{ __('Panduan') }}
                </h3>
                <ol class="space-y-4 text-sm font-medium text-indigo-50">
                    <li class="flex gap-3">
                        <span class="flex-none size-5 rounded-full bg-white/20 flex items-center justify-center text-[10px] font-black italic">1</span>
                        <span>{{ __('Pilih parameter akademik (Tahun, Kelas, Semester).') }}</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="flex-none size-5 rounded-full bg-white/20 flex items-center justify-center text-[10px] font-black italic">2</span>
                        <span>{{ __('Sistem akan memvalidasi kelengkapan data siswa.') }}</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="flex-none size-5 rounded-full bg-white/20 flex items-center justify-center text-[10px] font-black italic">3</span>
                        <span>{{ __('Pilih siswa yang ingin dicetak laporannya.') }}</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="flex-none size-5 rounded-full bg-white/20 flex items-center justify-center text-[10px] font-black italic">4</span>
                        <span>{{ __('Klik Generate untuk sinkronisasi nilai otomatis.') }}</span>
                    </li>
                </ol>
                <div class="mt-8 pt-6 border-t border-white/10">
                    <p class="text-[10px] font-black uppercase tracking-tighter text-indigo-200 leading-tight italic">
                        {{ __('Data diambil dari: Penilaian, Capaian Kompetensi, Projek P5, Ekstrakurikuler, & Presensi.') }}
                    </p>
                </div>
            </div>

            <x-ui.card shadow padding="false">
                <div class="p-6 space-y-4">
                    <h4 class="text-xs font-black uppercase tracking-widest text-slate-500 italic">{{ __('Info Kurikulum') }}</h4>
                    <div class="p-4 bg-slate-50 dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800">
                        <p class="text-[10px] font-black text-indigo-600 uppercase mb-2 tracking-tight">{{ __('Kurikulum Merdeka') }}</p>
                        <p class="text-xs text-slate-600 dark:text-slate-400 italic leading-relaxed">
                            {{ __('Menggunakan Deskripsi Capaian Kompetensi (BB, MB, BSH, SB) dan Penilaian Projek Penguatan Profil Pelajar Pancasila (P5).') }}
                        </p>
                    </div>
                </div>
            </x-ui.card>
        </div>
    </div>

    {{-- Preview Modal --}}
    <x-ui.modal wire:model="showPreview" :title="__('Pratinjau Lembar Rapor')" persistent>
        @if ($previewData)
            <div class="p-4 md:p-8 bg-slate-100 dark:bg-slate-900 rounded-3xl shadow-inner overflow-y-auto max-h-[70vh]">
                <div class="max-w-3xl mx-auto bg-white p-8 md:p-12 shadow-2xl min-h-screen text-slate-900 rounded-lg">
                    @include('pdf._rapor_content', [
                        'reportCard' => $previewData['reportCard'],
                        'student' => $previewData['student'],
                        'studentProfile' => $previewData['studentProfile'],
                        'classroom' => $previewData['classroom'],
                        'academicYear' => $previewData['academicYear'],
                        'teacher' => $previewData['teacher']
                    ])
                </div>
            </div>
            
            <div class="flex justify-end gap-3 pt-6">
                <x-ui.button :label="__('Tutup')" wire:click="closePreview" />
                <x-ui.button :label="__('Download PDF')" icon="o-arrow-down-tray" class="btn-primary shadow-lg shadow-primary/20" wire:click="exportPdf({{ $previewData['reportCard']->id }})" spinner />
            </div>
        @endif
    </x-ui.modal>
</div>
