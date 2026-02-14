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
            <option value="1">1 (Ganjil)</option>
            <option value="2">2 (Genap)</option>
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
