<div class="p-6">
    <x-header title="Presensi Siswa" subtitle="Rekap kehadiran siswa per kelas dan mata pelajaran." separator />

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <x-select wire:model.live="academic_year_id" label="Tahun Ajaran" :options="$years" />

        <x-select wire:model.live="classroom_id" label="Kelas" placeholder="Pilih Kelas" :options="$classrooms" />

        <x-select wire:model.live="subject_id" label="Mata Pelajaran (Opsional)" placeholder="Semua-Harian" :options="$subjects" />

        <x-input wire:model.live="date" type="date" label="Tanggal" />
    </div>

    @if($classroom_id)
        <div class="border rounded-xl bg-white dark:bg-zinc-900 overflow-hidden shadow-sm border-base-200">
            <table class="table">
                <thead>
                    <tr>
                        <th class="bg-base-200">Nama Siswa</th>
                        <th class="bg-base-200 text-center">Status Kehadiran</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($students as $student)
                        <tr wire:key="{{ $student->id }}" class="hover">
                            <td class="font-medium">
                                {{ $student->name }}
                            </td>
                            <td>
                                <div class="flex justify-center items-center">
                                    <div class="flex gap-2">
                                        @foreach(['h' => 'Hadir', 's' => 'Sakit', 'i' => 'Izin', 'a' => 'Alpa'] as $val => $label)
                                            <button type="button" 
                                                wire:click="setStatus({{ $student->id }}, '{{ $val }}')"
                                                class="flex items-center gap-1 px-3 py-1 rounded-full border transition-all text-sm font-semibold {{ ($attendance_data[$student->id] ?? '') === $val ? 'bg-primary border-primary text-primary-content shadow-md' : 'bg-base-100 border-base-300 text-base-content hover:border-primary/50' }}">
                                                {{ $label }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="p-4 bg-base-200 border-t border-base-300 flex flex-col gap-4">
                <x-textarea wire:model="notes" label="Catatan Tambahan" placeholder="Catatan kejadian hari ini (jika ada)..." rows="2" />
                <div class="flex justify-end gap-2">
                    <x-button label="Batal" wire:click="$refresh" />
                    <x-button label="Simpan Presensi" icon="o-check" class="btn-primary" wire:click="save" spinner="save" />
                </div>
            </div>
        </div>
    @else
        <div class="flex flex-col items-center justify-center py-20 text-base-content/30 border-2 border-dashed border-base-300 rounded-3xl bg-base-200/50">
            <x-icon name="o-check-badge" class="w-16 h-16 mb-4 opacity-20" />
            <p class="text-xl font-medium">Silakan pilih kelas untuk memulai absensi harian.</p>
            <p class="text-sm">Data kehadiran akan otomatis tersimpan sementara sebelum difinalisasi.</p>
        </div>
    @endif
</div>
