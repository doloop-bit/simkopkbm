<div class="p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl">{{ __('Penilaian Rapor (Nilai & TP)') }}</flux:heading>
            <flux:subheading>{{ __('Input nilai akhir dan pemilihan TP kompetensi untuk rapor.') }}</flux:subheading>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <flux:select wire:model.live="academic_year_id" label="Tahun Ajaran" :disabled="$isReadonly">
            @foreach($years as $year)
                <option value="{{ $year->id }}">{{ $year->name }}</option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="semester" label="Semester" :disabled="$isReadonly">
            <option value="1">1 (Ganjil)</option>
            <option value="2">2 (Genap)</option>
        </flux:select>

        <flux:select wire:model.live="classroom_id" label="Kelas" :disabled="$isReadonly">
            <option value="">Pilih Kelas</option>
            @foreach($classrooms as $room)
                <option value="{{ $room->id }}">
                    {{ $room->name }}
                    @if($room->class_level && $room->getPhase())
                        (Fase {{ $room->getPhase() }})
                    @endif
                </option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="subject_id" label="Mata Pelajaran" :disabled="$isReadonly">
            <option value="">Pilih Mata Pelajaran</option>
            @foreach($subjects as $subject)
                <option value="{{ $subject->id }}">{{ $subject->name }}</option>
            @endforeach
        </flux:select>
    </div>

    @if($currentPhase)
        <div class="mb-4 flex items-center gap-2">
            <flux:badge color="indigo">Fase {{ $currentPhase }}</flux:badge>
            <span class="text-sm text-zinc-500 dark:text-zinc-400">
                TP yang ditampilkan sesuai dengan fase kelas yang dipilih.
            </span>
        </div>
    @endif

    @if($classroom_id && $subject_id)
        @if($tps->isEmpty())
            <div class="mb-4 p-4 rounded-lg border border-amber-200 bg-amber-50 dark:bg-amber-950/30 dark:border-amber-900">
                <div class="flex items-center gap-2">
                    <flux:icon icon="exclamation-triangle" class="w-5 h-5 text-amber-600" />
                    <p class="text-sm text-amber-700 dark:text-amber-400">
                        Belum ada TP untuk mata pelajaran ini
                        @if($currentPhase)
                            pada Fase {{ $currentPhase }}
                        @endif
                        . Silakan tambahkan TP melalui menu <strong>Mata Pelajaran (Admin)</strong>.
                    </p>
                </div>
            </div>
        @endif

        <div class="border rounded-lg bg-white dark:bg-zinc-900 pb-20">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left border-collapse min-w-[800px]">
                    <thead class="bg-zinc-50 dark:bg-zinc-800">
                        <tr>
                            <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b w-64">Nama Siswa</th>
                            <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b w-32 text-center">Nilai Final (0-100)</th>
                            <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b">TP Tertinggi (Kompeten)</th>
                            <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300 border-b">TP Terendah (Perlu Bimbingan)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($students as $student)
                            <tr wire:key="{{ $student->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <td class="px-4 py-3 text-zinc-900 dark:text-white font-medium align-top">
                                    {{ $student->name }}
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <flux:input 
                                        wire:model="grades_data.{{ $student->id }}.grade" 
                                        type="number" 
                                        min="0"
                                        max="100" 
                                        step="1" 
                                        class="text-center" 
                                        :readonly="$isReadonly"
                                    />
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <x-select.styled
                                        wire:model="grades_data.{{ $student->id }}.best_tp_ids"
                                        :options="$tps"
                                        select="label:code|value:id|description:description"
                                        multiple
                                        placeholder="Pilih TP Terbaik..."
                                        :disabled="$isReadonly"
                                        color="indigo"
                                    />
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <x-select.styled
                                        wire:model="grades_data.{{ $student->id }}.improvement_tp_ids"
                                        :options="$tps"
                                        select="label:code|value:id|description:description"
                                        multiple
                                        placeholder="Pilih TP Lemah..."
                                        :disabled="$isReadonly"
                                        color="orange"
                                    />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if(!$isReadonly)
                <div class="p-4 bg-zinc-50 dark:bg-zinc-800 border-t flex justify-end sticky bottom-0 z-10">
                    <flux:button variant="primary" icon="check" wire:click="save">Simpan Penilaian Rapor</flux:button>
                </div>
            @endif
        </div>
    @else
        <div class="flex flex-col items-center justify-center py-12 text-zinc-500 border-2 border-dashed rounded-xl">
            <flux:icon icon="pencil-square" class="w-12 h-12 mb-2 opacity-20" />
            <p>Silakan pilih kelas dan mata pelajaran untuk memulai penilaian rapor.</p>
        </div>
    @endif


</div>
