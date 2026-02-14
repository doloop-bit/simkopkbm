<div class="p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl" level="1">Presensi Siswa</flux:heading>
            <flux:subheading>Rekap kehadiran siswa per kelas dan mata pelajaran.</flux:subheading>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
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

        <flux:select wire:model.live="subject_id" label="Mata Pelajaran (Opsional)">
            <option value="">Semua-Harian</option>
            @foreach($subjects as $subject)
                <option value="{{ $subject->id }}">{{ $subject->name }}</option>
            @endforeach
        </flux:select>

        <flux:input wire:model.live="date" type="date" label="Tanggal" />
    </div>

    @if($classroom_id)
        <div class="border rounded-lg bg-white dark:bg-zinc-900 overflow-hidden shadow-sm">
            <table class="w-full text-sm text-left border-collapse">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b">Nama Siswa</th>
                        <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b text-center">Status Kehadiran</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach($students as $student)
                        <tr wire:key="{{ $student->id }}">
                            <td class="px-4 py-3 text-zinc-900 dark:text-white font-medium">
                                {{ $student->name }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-center items-center">
                                    {{-- Custom Radio Group for better mobile UX --}}
                                    <div class="flex gap-2">
                                        @foreach(['h' => 'Hadir', 's' => 'Sakit', 'i' => 'Izin', 'a' => 'Alpa'] as $val => $label)
                                            <button type="button" 
                                                wire:click="setStatus({{ $student->id }}, '{{ $val }}')"
                                                class="flex items-center gap-1 px-3 py-1 rounded-full border transition-colors {{ ($attendance_data[$student->id] ?? '') === $val ? 'bg-blue-100 border-blue-500 text-blue-700 dark:bg-blue-900/40 dark:text-blue-200' : 'bg-white dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700 text-zinc-500 hover:border-zinc-300' }}">
                                                <span class="text-xs font-semibold">{{ $label }}</span>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="p-4 bg-zinc-50 dark:bg-zinc-800 border-t flex flex-col gap-4">
                <flux:textarea wire:model="notes" label="Catatan Tambahan" placeholder="Catatan kejadian hari ini (jika ada)..." rows="2" />
                <div class="flex justify-end gap-2">
                    <flux:button variant="ghost" wire:click="$refresh">Batal</flux:button>
                    <flux:button variant="primary" icon="check" wire:click="save">Simpan Presensi</flux:button>
                </div>
            </div>
        </div>
    @else
        <div class="flex flex-col items-center justify-center py-12 text-zinc-500 border-2 border-dashed border-zinc-200 dark:border-zinc-700 rounded-xl bg-zinc-50/50 dark:bg-zinc-900/50">
            <flux:icon icon="check-badge" class="w-12 h-12 mb-2 opacity-20" />
            <p>Silakan pilih kelas untuk memulai absensi harian.</p>
        </div>
    @endif
</div>
