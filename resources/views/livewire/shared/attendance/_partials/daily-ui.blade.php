<div class="p-6 space-y-6 text-slate-900 dark:text-white">
    <x-ui.header :title="__('Presensi Siswa')" :subtitle="__('Rekap kehadiran siswa per kelas dan mata pelajaran.')" separator />

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <x-ui.select wire:model.live="academic_year_id" :label="__('Tahun Ajaran')" :options="$years" />
        <x-ui.select wire:model.live="classroom_id" :label="__('Kelas')" :placeholder="__('Pilih Kelas')" :options="$classrooms" />
        <x-ui.select wire:model.live="subject_id" :label="__('Mata Pelajaran (Opsional)')" :placeholder="__('Semua-Harian')" :options="$subjects" />
        <x-ui.input wire:model.live="date" type="date" :label="__('Tanggal')" />
    </div>

    @if($classroom_id)
        <x-ui.card shadow padding="false">
            <x-ui.table 
                :headers="[
                    ['key' => 'name', 'label' => __('Nama Siswa')],
                    ['key' => 'status', 'label' => __('Status Kehadiran'), 'class' => 'text-center']
                ]" 
                :rows="$students"
            >
                @scope('cell_name', $student)
                    <div class="flex items-center gap-3">
                        <div class="size-8 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center font-bold text-slate-400 text-xs">
                            {{ substr($student->name, 0, 1) }}
                        </div>
                        <span class="font-bold text-slate-900 dark:text-white">{{ $student->name }}</span>
                    </div>
                @endscope

                @scope('cell_status', $student, $attendance_data)
                    <div class="flex justify-center items-center gap-2">
                        @foreach([
                            'h' => ['label' => __('Hadir'), 'class' => 'bg-emerald-500 text-white shadow-emerald-500/30'],
                            's' => ['label' => __('Sakit'), 'class' => 'bg-amber-400 text-white shadow-amber-400/30'],
                            'i' => ['label' => __('Izin'), 'class' => 'bg-sky-500 text-white shadow-sky-500/30'],
                            'a' => ['label' => __('Alpa'), 'class' => 'bg-rose-500 text-white shadow-rose-500/30']
                        ] as $val => $meta)
                            @php
                                $isSelected = ($attendance_data[$student->id] ?? '') === $val;
                            @endphp
                            <button type="button" 
                                wire:click="setStatus({{ $student->id }}, '{{ $val }}')"
                                @class([
                                    'flex items-center gap-1.5 px-4 py-1.5 rounded-xl transition-all duration-300 text-xs font-black uppercase tracking-wider border-2',
                                    $meta['class'] . ' border-transparent scale-105 ring-2 ring-primary/20' => $isSelected,
                                    'bg-white dark:bg-slate-900 border-slate-100 dark:border-slate-800 text-slate-400 dark:text-slate-500 hover:border-slate-300 dark:hover:border-slate-600' => !$isSelected,
                                ])>
                                {{ $meta['label'] }}
                            </button>
                        @endforeach
                    </div>
                @endscope
            </x-ui.table>

            <div class="p-6 bg-slate-50/50 dark:bg-slate-800/20 border-t border-slate-100 dark:border-slate-800 space-y-4">
                <x-ui.textarea wire:model="notes" :label="__('Catatan Tambahan')" :placeholder="__('Catatan kejadian hari ini (jika ada)...')" rows="2" />
                <div class="flex justify-end gap-2">
                    <x-ui.button :label="__('Batal')" ghost @click="$refresh" />
                    <x-ui.button :label="__('Simpan Presensi')" icon="o-check" class="btn-primary" wire:click="save" spinner="save" />
                </div>
            </div>
        </x-ui.card>
    @else
        <div class="flex flex-col items-center justify-center py-32 rounded-[2rem] border-4 border-dashed border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm transition-all duration-500 group">
            <div class="size-20 rounded-3xl bg-slate-50 dark:bg-slate-800 flex items-center justify-center mb-6 group-hover:scale-110 group-hover:rotate-6 transition-all">
                <x-ui.icon name="o-check-badge" class="size-10 text-primary opacity-20" />
            </div>
            <h3 class="text-xl font-black text-slate-900 dark:text-white mb-2">{{ __('Mulai Absensi Harian') }}</h3>
            <p class="text-slate-400 dark:text-slate-500 text-sm max-w-sm text-center leading-relaxed">
                {{ __('Silakan pilih kelas terlebih dahulu untuk memulai rekap kehadiran siswa hari ini.') }}
            </p>
        </div>
    @endif
</div>
