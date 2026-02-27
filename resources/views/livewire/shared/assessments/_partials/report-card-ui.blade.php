<div class="p-6 flex flex-col gap-6">
    {{-- Header --}}
    <x-header title="Generator Rapor" subtitle="Buat dan kelola rapor siswa berdasarkan data penilaian." separator />

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Left Column: Generator Form --}}
        <div class="lg:col-span-2 flex flex-col gap-6">
            <x-card shadow>
                <form wire:submit="generateReportCards" class="flex flex-col gap-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-select 
                            wire:model.live="academicYearId" 
                            label="Tahun Ajaran" 
                            :options="$academicYears" 
                            placeholder="Pilih Tahun Ajaran"
                            required 
                        />

                        <x-select 
                            wire:model.live="classroomId" 
                            label="Kelas" 
                            :options="$classrooms" 
                            placeholder="Pilih Kelas"
                            required 
                        />

                        <x-select 
                            wire:model.live="semester" 
                            label="Semester" 
                            :options="[
                                ['id' => '1', 'name' => 'Semester 1 (Ganjil)'],
                                ['id' => '2', 'name' => 'Semester 2 (Genap)'],
                            ]"
                            required 
                        />

                        <x-input label="Jenis Kurikulum" value="Kurikulum Merdeka" readonly disabled />
                    </div>

                    @if (count($students) > 0)
                        <div class="flex flex-col gap-2">
                            <div class="flex items-center justify-between">
                                <label class="label font-bold text-sm">{{ __('Pilih Siswa') }}</label>
                                <div class="flex gap-2">
                                    <x-button label="Pilih Semua" wire:click="$set('selectedStudents', {{ $students->pluck('id') }})" class="btn-ghost btn-xs text-primary" />
                                    <x-button label="Batal" wire:click="$set('selectedStudents', [])" class="btn-ghost btn-xs" />
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 max-h-48 overflow-y-auto border border-base-300 rounded-xl p-3 bg-base-200/50">
                                @foreach ($students as $student)
                                    <x-checkbox 
                                        wire:model="selectedStudents" 
                                        value="{{ $student->id }}" 
                                        label="{{ $student->profile->user->name ?? 'N/A' }}" 
                                        tight
                                    />
                                @endforeach
                            </div>
                            @error('selectedStudents') <p class="text-xs text-error">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    <div class="grid grid-cols-1 gap-4">
                        <x-textarea wire:model="characterNotes" label="Catatan Karakter" rows="2" />
                        <x-textarea wire:model="teacherNotes" label="Catatan Guru" rows="2" />
                        @if(auth()->user()->isAdmin())
                             <x-textarea wire:model="principalNotes" label="Catatan Kepala Sekolah" rows="2" />
                        @endif
                    </div>

                    <div class="flex items-center gap-3">
                        <x-button type="submit" label="Proses & Buat Rapor" icon="o-sparkles" class="btn-primary shadow-lg" spinner="generateReportCards" />
                        <x-button type="button" label="Reset" icon="o-arrow-path" class="btn-ghost" wire:click="$refresh" />
                    </div>
                </form>
            </x-card>

            {{-- List of Generated Reports --}}
            <x-card title="Daftar Rapor Siswa" separator shadow>
                <x-slot:menu>
                    <x-badge :label="$existingReports->count() . ' Terdata'" class="badge-ghost font-bold" />
                </x-slot:menu>

                @if($existingReports->isNotEmpty())
                    <x-table :headers="[
                        ['key' => 'student.name', 'label' => 'Siswa'],
                        ['key' => 'status', 'label' => 'Status'],
                        ['key' => 'actions', 'label' => '', 'class' => 'text-right']
                    ]" :rows="$existingReports">
                        @scope('cell_student.name', $report)
                            <span class="font-bold">{{ $report->student->name }}</span>
                        @endscope

                        @scope('cell_status', $report)
                            <x-badge 
                                :label="strtoupper($report->status)" 
                                class="{{ $report->status === 'final' ? 'badge-success' : 'badge-ghost' }} badge-sm" 
                            />
                        @endscope

                        @scope('cell_actions', $report)
                            <div class="flex justify-end gap-1">
                                <x-button icon="o-eye" wire:click="previewReportCard({{ $report->id }})" class="btn-ghost btn-sm text-info" spinner />
                                <x-button icon="o-arrow-down-tray" wire:click="exportPdf({{ $report->id }})" class="btn-ghost btn-sm text-primary" spinner />
                                <x-button icon="o-trash" wire:confirm="Hapus rapor ini?" wire:click="deleteReportCard({{ $report->id }})" class="btn-ghost btn-sm text-error" spinner />
                            </div>
                        @endscope
                    </x-table>
                @else
                    <div class="py-12 flex flex-col items-center justify-center opacity-30">
                        <x-icon name="o-document-text" class="size-12 mb-2" />
                        <p>Belum ada rapor yang dibuat untuk kriteria ini.</p>
                    </div>
                @endif
            </x-card>
        </div>

        {{-- Right Column: Info & Legend --}}
        <div class="flex flex-col gap-6">
            <div class="p-6 bg-primary/5 border border-primary/20 rounded-xl">
                <h3 class="text-primary font-black mb-2 flex items-center gap-2">
                    <x-icon name="o-information-circle" class="size-5" />
                    Panduan Penggunaan
                </h3>
                <ul class="text-sm opacity-80 space-y-2 list-disc pl-4 font-medium">
                    <li>Pilih parameter akademik (Tahun, Kelas, Semester).</li>
                    <li>Sistem akan menyaring siswa di kelas yang dipilih.</li>
                    <li>Centang siswa yang ingin dibuatkan rapornya.</li>
                    <li>Klik <strong>Proses & Buat Rapor</strong> untuk menghitung nilai otomatis.</li>
                    <li>Data diambil dari Penilaian, Kompetensi, P5, Ekskul, dan Presensi.</li>
                </ul>
            </div>

            <x-card title="Informasi Kurikulum" shadow sm>
                <div class="flex flex-col gap-4">
                    <div>
                        <p class="text-[10px] font-black uppercase opacity-60 mb-1 tracking-widest">Kurikulum Merdeka</p>
                        <p class="text-xs opacity-80 italic">Satu-satunya kurikulum yang aktif. Menggunakan Deskripsi Capaian Kompetensi (BB, MB, BSH, SB) dan Projek P5.</p>
                    </div>
                </div>
            </x-card>
        </div>
    </div>

    {{-- Preview Modal --}}
    <x-modal wire:model="showPreview" title="Pratinjau Rapor" class="backdrop-blur" separator max-width="max-w-4xl" persistent>
        @if ($previewData)
            <div class="p-8 bg-base-300 rounded-xl shadow-inner overflow-y-auto max-h-[70vh]">
                <div class="max-w-3xl mx-auto bg-white p-12 shadow-2xl min-h-screen text-black">
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
            
            <x-slot:actions>
                <x-button label="Download PDF" icon="o-arrow-down-tray" class="btn-primary" wire:click="exportPdf({{ $previewData['reportCard']->id }})" spinner />
                <x-button label="Tutup" wire:click="closePreview" />
            </x-slot:actions>
        @endif
    </x-modal>
</div>
