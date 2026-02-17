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

        <div class="border rounded-lg bg-white dark:bg-zinc-900 overflow-hidden">
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
                                    <div class="flex items-center justify-between p-2 border rounded-md bg-zinc-50 dark:bg-zinc-800 dark:border-zinc-700 min-h-[42px]">
                                        <div class="text-sm truncate mr-2">
                                            @php 
                                                $selectedIds = $grades_data[$student->id]['best_tp_ids'] ?? [];
                                                $count = count($selectedIds);
                                            @endphp
                                            @if($count > 0)
                                                <span class="font-medium text-indigo-600 dark:text-indigo-400">{{ $count }} TP dipilih</span>
                                            @else
                                                <span class="text-zinc-400 italic">Pilih TP...</span>
                                            @endif
                                        </div>
                                        <flux:button size="sm" icon="pencil-square" variant="ghost" class="-my-1"
                                            wire:click="openTpSelection({{ $student->id }}, 'best')" 
                                            :disabled="$isReadonly" />
                                    </div>
                                    @if(count($grades_data[$student->id]['best_tp_ids'] ?? []) > 0)
                                        <div class="mt-1 text-xs text-zinc-500 truncate">
                                            @foreach($grades_data[$student->id]['best_tp_ids'] as $id)
                                                {{ $tps->firstWhere('id', $id)->code ?? '' }}@if(!$loop->last), @endif
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <div class="flex items-center justify-between p-2 border rounded-md bg-zinc-50 dark:bg-zinc-800 dark:border-zinc-700 min-h-[42px]">
                                        <div class="text-sm truncate mr-2">
                                            @php 
                                                $selectedIds = $grades_data[$student->id]['improvement_tp_ids'] ?? [];
                                                $count = count($selectedIds);
                                            @endphp
                                            @if($count > 0)
                                                <span class="font-medium text-orange-600 dark:text-orange-400">{{ $count }} TP dipilih</span>
                                            @else
                                                <span class="text-zinc-400 italic">Pilih TP...</span>
                                            @endif
                                        </div>
                                        <flux:button size="sm" icon="pencil-square" variant="ghost" class="-my-1"
                                            wire:click="openTpSelection({{ $student->id }}, 'improvement')"
                                            :disabled="$isReadonly" />
                                    </div>
                                    @if(count($grades_data[$student->id]['improvement_tp_ids'] ?? []) > 0)
                                        <div class="mt-1 text-xs text-zinc-500 truncate">
                                            @foreach($grades_data[$student->id]['improvement_tp_ids'] as $id)
                                                {{ $tps->firstWhere('id', $id)->code ?? '' }}@if(!$loop->last), @endif
                                            @endforeach
                                        </div>
                                    @endif
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

    <!-- TP Selection Modal -->
    <div x-data x-show="$wire.showTpModal" 
         x-cloak
         @keydown.escape.window="$wire.showTpModal = false"
         class="fixed inset-0 z-50 overflow-y-auto" 
         style="display: none;">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black/50 transition-opacity" 
             @click="$wire.showTpModal = false"></div>
        
        <!-- Modal Content -->
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative bg-white dark:bg-zinc-900 rounded-xl shadow-2xl max-w-2xl w-full border border-zinc-200 dark:border-zinc-700">
                <div class="p-6 space-y-6">
                    <div>
                        <flux:heading size="lg">Pilih TP {{ $editingType === 'best' ? 'Terbaik' : 'Perlu Bimbingan' }}</flux:heading>
                        <flux:subheading>Siswa: {{ $editingStudentName }}</flux:subheading>
                    </div>

                    <div class="max-h-[60vh] overflow-y-auto space-y-3 pr-2">
                        @if($tps->isNotEmpty())
                            @foreach($tps as $tp)
                                <div class="flex items-start gap-3 p-3 rounded-lg border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                                    <flux:checkbox 
                                        wire:model="tempSelectedTps" 
                                        value="{{ $tp->id }}" 
                                    />
                                    <div class="flex-1 text-sm">
                                        <span class="font-bold text-zinc-900 dark:text-zinc-100 block mb-1">
                                            {{ $tp->code ?? 'TP' }}
                                            @if($tp->learningAchievement) 
                                                <span class="font-normal text-zinc-500">({{ $tp->learningAchievement->phase ?? '' }})</span>
                                            @endif
                                        </span>
                                        <span class="text-zinc-600 dark:text-zinc-400 block leading-relaxed">
                                            {{ $tp->description }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="text-center py-8 text-zinc-500">
                                Tidak ada TP yang tersedia.
                            </div>
                        @endif
                    </div>

                    <div class="flex justify-end gap-2 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                        <flux:button variant="ghost" @click="$wire.showTpModal = false">Batal</flux:button>
                        <flux:button variant="primary" wire:click="saveTpSelection">Simpan Pilihan</flux:button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
