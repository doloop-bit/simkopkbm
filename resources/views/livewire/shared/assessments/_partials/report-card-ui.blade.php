<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">{{ __('Generator Rapor') }}</flux:heading>
            <flux:subheading>{{ __('Buat dan kelola rapor siswa berdasarkan data penilaian.') }}</flux:subheading>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <!-- Left Column: Generator Form -->
        <div class="lg:col-span-2 space-y-6">
            <div class="p-6 bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm">
                <form wire:submit="generateReportCards" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <flux:select wire:model.live="academicYearId" label="Tahun Ajaran" required>
                            <option value="">Pilih Tahun Ajaran</option>
                            @foreach ($academicYears as $year)
                                <option value="{{ $year->id }}">{{ $year->name }}</option>
                            @endforeach
                        </flux:select>

                        <flux:select wire:model.live="classroomId" label="Kelas" required>
                            <option value="">Pilih Kelas</option>
                            @foreach ($classrooms as $classroom)
                                <option value="{{ $classroom->id }}">{{ $classroom->name }}</option>
                            @endforeach
                        </flux:select>

                        <flux:select wire:model.live="semester" label="Semester" required>
                            <option value="1">Semester 1 (Ganjil)</option>
                            <option value="2">Semester 2 (Genap)</option>
                        </flux:select>

                        <flux:input label="Jenis Kurikulum" value="Kurikulum Merdeka" readonly disabled />
                    </div>

                    @if (count($students) > 0)
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <label class="block text-sm font-medium">{{ __('Pilih Siswa') }}</label>
                                <div class="flex gap-2 text-xs">
                                    <button type="button" wire:click="$set('selectedStudents', {{ $students->pluck('id') }})" class="text-blue-600 hover:underline">Pilih Semua</button>
                                    <span class="text-zinc-300">|</span>
                                    <button type="button" wire:click="$set('selectedStudents', [])" class="text-zinc-500 hover:underline">Batal</button>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 max-h-48 overflow-y-auto border border-zinc-200 dark:border-zinc-700 rounded-lg p-3 bg-zinc-50 dark:bg-zinc-900/50">
                                @foreach ($students as $student)
                                    <label class="flex items-center gap-3 cursor-pointer hover:bg-white dark:hover:bg-zinc-800 p-2 rounded transition-colors border border-transparent hover:border-zinc-200 dark:hover:border-zinc-700">
                                        <input type="checkbox" wire:model="selectedStudents" value="{{ $student->id }}" class="rounded text-blue-600">
                                        <span class="text-sm">{{ $student->profile->user->name ?? 'N/A' }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @error('selectedStudents') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    <div class="grid grid-cols-1 gap-4">
                        <flux:textarea wire:model="characterNotes" label="Catatan Karakter" rows="2" />
                        <flux:textarea wire:model="teacherNotes" label="Catatan Guru" rows="2" />
                        @if(auth()->user()->isAdmin())
                             <flux:textarea wire:model="principalNotes" label="Catatan Kepala Sekolah" rows="2" />
                        @endif
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <flux:button type="submit" variant="primary" icon="sparkles" wire:loading.attr="disabled">
                            <span wire:loading.remove>{{ __('Proses & Buat Rapor') }}</span>
                            <span wire:loading>{{ __('Memproses...') }}</span>
                        </flux:button>
                        <flux:button type="button" variant="ghost" wire:click="$refresh" icon="arrow-path">Reset</flux:button>
                    </div>
                </form>
            </div>

            <!-- List of Generated Reports -->
            <div class="p-6 bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <flux:heading level="2" size="lg">{{ __('Daftar Rapor Siswa') }}</flux:heading>
                    <flux:badge color="zinc">{{ $existingReports->count() }} Terdata</flux:badge>
                </div>

                @if($existingReports->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left border-collapse">
                            <thead>
                                <tr class="text-zinc-500 border-b border-zinc-100 dark:border-zinc-700">
                                    <th class="py-3 px-2 font-medium">Siswa</th>
                                    <th class="py-3 px-2 font-medium">Status</th>
                                    <th class="py-3 px-2 font-medium text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                                @foreach($existingReports as $report)
                                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50 transition-colors">
                                        <td class="py-3 px-2 font-medium">{{ $report->student->name }}</td>
                                        <td class="py-3 px-2">
                                            <flux:badge size="sm" :color="$report->status === 'final' ? 'green' : 'zinc'">
                                                {{ strtoupper($report->status) }}
                                            </flux:badge>
                                        </td>
                                        <td class="py-3 px-2 text-right space-x-1">
                                            <flux:button variant="ghost" size="sm" icon="eye" wire:click="previewReportCard({{ $report->id }})" />
                                            <flux:button variant="ghost" size="sm" icon="arrow-down-tray" wire:click="exportPdf({{ $report->id }})" />
                                            <button type="button" wire:confirm="Hapus rapor ini?" wire:click="deleteReportCard({{ $report->id }})" class="p-1 text-zinc-400 hover:text-red-500">
                                                <flux:icon icon="trash" class="w-4 h-4" />
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="py-12 flex flex-col items-center justify-center text-zinc-400 border-2 border-dashed border-zinc-100 dark:border-zinc-800 rounded-xl">
                        <flux:icon icon="document-text" class="w-12 h-12 mb-2 opacity-20" />
                        <p>Belum ada rapor yang dibuat untuk kriteria ini.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Right Column: Info & Legend -->
        <div class="space-y-6">
            <div class="p-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-900 rounded-xl">
                <flux:heading size="lg" class="text-blue-900 dark:text-blue-200 mb-2">Panduan Penggunaan</flux:heading>
                <ul class="text-sm text-blue-800 dark:text-blue-300 space-y-2 list-disc pl-4">
                    <li>Pilih parameter akademik (Tahun, Kelas, Semester).</li>
                    <li>Sistem akan menyaring siswa di kelas yang dipilih.</li>
                    <li>Centang siswa yang ingin dibuatkan rapornya.</li>
                    <li>Klik <strong>Proses & Buat Rapor</strong> untuk menghitung nilai otomatis.</li>
                    <li>Data diambil dari Penilaian (Nilai), Kompetensi, P5, Ekskul, dan Presensi.</li>
                </ul>
            </div>

            <div class="p-6 bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm">
                <flux:heading size="md" class="mb-4">Informasi Kurikulum</flux:heading>
                <div class="space-y-4">
                    <div>
                        <p class="text-xs font-bold text-zinc-500 uppercase tracking-wider mb-1">Kurikulum Merdeka</p>
                        <p class="text-xs text-zinc-600 dark:text-zinc-400">Satu-satunya kurikulum yang aktif. Menggunakan Deskripsi Capaian Kompetensi (BB, MB, BSH, SB) dan Projek P5.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Preview Modal -->
    @if ($showPreview && $previewData)
        <flux:modal wire:model="showPreview" class="max-w-4xl">
            <div class="space-y-6">
                <div class="flex items-center justify-between">
                    <flux:heading size="xl">Pratinjau Rapor</flux:heading>
                </div>
                
                <div class="p-8 bg-zinc-50 dark:bg-zinc-900 text-black rounded-lg shadow-inner overflow-y-auto max-h-[70vh]">
                    <div class="max-w-3xl mx-auto bg-white p-12 shadow-sm min-h-screen">
                        <div class="text-center border-b-2 border-black pb-4 mb-8">
                            <h1 class="text-2xl font-bold uppercase">RAPOR HASIL BELAJAR</h1>
                            <p class="text-lg font-semibold">{{ $previewData['classroom']->name }}</p>
                            <p>Tahun Ajaran {{ $previewData['academicYear']->name }} - Semester {{ $previewData['reportCard']->semester }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-x-12 gap-y-2 text-sm mb-8">
                            <div class="flex justify-between"><span>Nama Siswa</span> <span>:</span></div>
                            <div class="font-bold">{{ $previewData['student']->name }}</div>
                            <div class="flex justify-between"><span>Nomor Induk / NISN</span> <span>:</span></div>
                            <div>{{ $previewData['studentProfile']->nis ?? '-' }} / {{ $previewData['studentProfile']->nisn ?? '-' }}</div>
                            <div class="flex justify-between"><span>Kelas</span> <span>:</span></div>
                            <div>{{ $previewData['classroom']->name }}</div>
                            <div class="flex justify-between"><span>Tahun Ajaran</span> <span>:</span></div>
                            <div>{{ $previewData['academicYear']->name }}</div>
                        </div>

                            <div class="space-y-6">
                                <!-- Competencies -->
                                <div>
                                    <h3 class="font-bold border-b border-zinc-300 mb-3">A. Nilai Capaian Kompetensi</h3>
                                    <table class="w-full border-collapse border border-black text-sm">
                                        <thead>
                                            <tr class="bg-zinc-100 italic">
                                                <th class="border border-black p-2 text-left w-6">No</th>
                                                <th class="border border-black p-2 text-left">Mata Pelajaran</th>
                                                <th class="border border-black p-2 text-center w-20">Nilai Akhir</th>
                                                <th class="border border-black p-2 text-left">Capaian Kompetensi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($previewData['reportCard']->scores['subject_grades'] ?? [] as $idx => $grade)
                                                <tr>
                                                    <td class="border border-black p-2 text-center">{{ $idx + 1 }}</td>
                                                    <td class="border border-black p-2 font-medium">{{ $grade['subject_name'] }}</td>
                                                    <td class="border border-black p-2 text-center font-bold">{{ round($grade['grade']) }}</td>
                                                    <td class="border border-black p-2 text-xs">
                                                        @if($grade['best_tp'])
                                                            <div class="mb-1"><strong>Menunjukkan penguasaan dalam:</strong> {{ $grade['best_tp'] }}</div>
                                                        @endif
                                                        @if($grade['improvement_tp'])
                                                            <div><strong>Perlu bantuan dalam:</strong> {{ $grade['improvement_tp'] }}</div>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="4" class="border border-black p-4 text-center text-zinc-400 italic">Data nilai & TP tidak ditemukan</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                
                                <!-- Attendance -->
                                <div class="w-64">
                                    <h3 class="font-bold border-b border-zinc-300 mb-3">B. Ketidakhadiran</h3>
                                    <table class="w-full border-collapse border border-black text-sm">
                                        <tbody>
                                            <tr><td class="border border-black p-2">Sakit</td><td class="border border-black p-2 text-center">{{ $previewData['reportCard']->scores['attendance']['sick'] ?? 0 }} hari</td></tr>
                                            <tr><td class="border border-black p-2">Izin</td><td class="border border-black p-2 text-center">{{ $previewData['reportCard']->scores['attendance']['permission'] ?? 0 }} hari</td></tr>
                                            <tr><td class="border border-black p-2">Alpa</td><td class="border border-black p-2 text-center">{{ $previewData['reportCard']->scores['attendance']['absent'] ?? 0 }} hari</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                        <div class="mt-8 space-y-4">
                            @if($previewData['reportCard']->teacher_notes)
                                <div class="p-3 border border-black italic text-sm">
                                    <strong>Catatan Guru:</strong> {{ $previewData['reportCard']->teacher_notes }}
                                </div>
                            @endif
                            @if($previewData['reportCard']->principal_notes)
                                <div class="p-3 border border-black italic text-sm">
                                    <strong>Catatan Kepala Sekolah:</strong> {{ $previewData['reportCard']->principal_notes }}
                                </div>
                            @endif
                        </div>

                        <div class="grid grid-cols-2 gap-12 pt-16 text-sm text-center">
                            <div>
                                <p>Orang Tua/Wali</p>
                                <div class="h-24"></div>
                                <p class="border-b border-black w-48 mx-auto"></p>
                            </div>
                            <div>
                                <p>Guru Kelas</p>
                                <div class="h-24"></div>
                                <p class="font-bold underline">{{ $previewData['teacher']->name ?? '..................' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-end gap-3">
                    <flux:button variant="ghost" wire:click="closePreview">Tutup</flux:button>
                    <flux:button variant="primary" icon="arrow-down-tray" wire:click="exportPdf({{ $previewData['reportCard']->id }})">Download PDF</flux:button>
                </div>
            </div>
        </flux:modal>
    @endif
</div>
