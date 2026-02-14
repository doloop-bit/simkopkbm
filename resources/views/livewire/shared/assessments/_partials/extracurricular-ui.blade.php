<div class="p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl" level="1">Penilaian Ekstrakurikuler</flux:heading>
            <flux:subheading>Input penilaian kegiatan ekstrakurikuler siswa.</flux:subheading>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <flux:select wire:model.live="academic_year_id" label="Tahun Ajaran" :disabled="$isReadonly">
            @foreach($years as $year)
                <option value="{{ $year->id }}">{{ $year->name }}</option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="semester" label="Semester" :disabled="$isReadonly">
            <option value="1">Semester 1</option>
            <option value="2">Semester 2</option>
        </flux:select>

        <flux:select wire:model.live="classroom_id" label="Kelas" :disabled="$isReadonly">
            <option value="">Pilih Kelas</option>
            @foreach($classrooms as $room)
                <option value="{{ $room->id }}">{{ $room->name }}</option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="activity_id" label="Ekstrakurikuler" :disabled="$isReadonly" :placeholder="$classroom_id ? 'Pilih Ekstrakurikuler' : 'Pilih Kelas Terlebih Dahulu'">
            <option value="">Pilih Ekstrakurikuler</option>
            @foreach($activities as $activity)
                <option value="{{ $activity->id }}">{{ $activity->name }}</option>
            @endforeach
        </flux:select>
    </div>

    @if($selectedActivity)
        <div class="mb-6 p-4 rounded-lg bg-gradient-to-r from-emerald-50 to-teal-50 dark:from-emerald-900/20 dark:to-teal-900/20 border border-emerald-200 dark:border-emerald-800">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0">
                    <flux:icon icon="trophy" class="w-10 h-10 text-emerald-600 dark:text-emerald-400" />
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $selectedActivity->name }}</h3>
                    @if($selectedActivity->instructor)
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">Pembina: {{ $selectedActivity->instructor }}</p>
                    @endif
                    @if($selectedActivity->description)
                        <p class="text-sm text-zinc-500 dark:text-zinc-500 mt-1">{{ $selectedActivity->description }}</p>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <div class="flex flex-wrap gap-4 mb-4 text-sm">
        <div class="flex items-center gap-2">
            <span class="w-3 h-3 rounded-full bg-green-500"></span>
            <span class="text-zinc-600 dark:text-zinc-400">Sangat Baik</span>
        </div>
        <div class="flex items-center gap-2">
            <span class="w-3 h-3 rounded-full bg-blue-500"></span>
            <span class="text-zinc-600 dark:text-zinc-400">Baik</span>
        </div>
        <div class="flex items-center gap-2">
            <span class="w-3 h-3 rounded-full bg-yellow-500"></span>
            <span class="text-zinc-600 dark:text-zinc-400">Cukup</span>
        </div>
        <div class="flex items-center gap-2">
            <span class="w-3 h-3 rounded-full bg-red-500"></span>
            <span class="text-zinc-600 dark:text-zinc-400">Perlu Ditingkatkan</span>
        </div>
    </div>

    @if($classroom_id && $activity_id)
        <div class="border rounded-lg bg-white dark:bg-zinc-900 overflow-hidden">
            <table class="w-full text-sm text-left border-collapse">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b w-48">Nama Siswa</th>
                        <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b w-32 text-center">Capaian</th>
                        <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b">Keterangan (Opsional)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach($students as $student)
                        <tr wire:key="{{ $student->id }}">
                            <td class="px-4 py-3 text-zinc-900 dark:text-white font-medium">
                                {{ $student->name }}
                            </td>
                            <td class="px-4 py-3">
                                <flux:select wire:model="assessments_data.{{ $student->id }}.level" :disabled="$isReadonly">
                                    <option value="Sangat Baik">Sangat Baik</option>
                                    <option value="Baik">Baik</option>
                                    <option value="Cukup">Cukup</option>
                                    <option value="Perlu Ditingkatkan">Perlu Ditingkatkan</option>
                                </flux:select>
                            </td>
                            <td class="px-4 py-3">
                                <flux:input 
                                    wire:model="assessments_data.{{ $student->id }}.description" 
                                    placeholder="Keterangan tambahan..."
                                    :readonly="$isReadonly"
                                />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if(!$isReadonly)
                <div class="p-4 bg-zinc-50 dark:bg-zinc-800 border-t flex justify-end">
                    <flux:button variant="primary" icon="check" wire:click="save">Simpan Penilaian</flux:button>
                </div>
            @endif
        </div>
    @else
        <div class="flex flex-col items-center justify-center py-12 text-zinc-500 border-2 border-dashed rounded-xl">
            <flux:icon icon="trophy" class="w-12 h-12 mb-2 opacity-20" />
            <p>Silakan pilih kelas dan ekstrakurikuler untuk memulai penilaian.</p>
        </div>
    @endif
</div>
