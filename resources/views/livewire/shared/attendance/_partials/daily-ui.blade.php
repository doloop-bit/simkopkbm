<div class="p-4 md:p-6 space-y-4 md:space-y-6 text-slate-900 dark:text-white" wire:key="attendance-container-{{ $classroom_id }}-{{ $date }}">
    @php
        $selectedClassroomName = $classrooms->firstWhere('id', (int)$classroom_id)?->name;
    @endphp
    <x-ui.header :title="__('Presensi :class', ['class' => $selectedClassroomName ?? __('Siswa')])" :subtitle="__('Rekap kehadiran harian siswa.')" class="mb-0 md:mb-6" />

    @php
        $statuses = [
            'h' => ['label' => __('Hadir'), 'color' => 'bg-emerald-500', 'ring' => 'ring-emerald-500/20', 'shadow' => 'shadow-emerald-500/30'],
            's' => ['label' => __('Sakit'), 'color' => 'bg-amber-400', 'ring' => 'ring-amber-400/20', 'shadow' => 'shadow-amber-400/30'],
            'i' => ['label' => __('Izin'), 'color' => 'bg-sky-500', 'ring' => 'ring-sky-500/20', 'shadow' => 'shadow-sky-500/30'],
            'a' => ['label' => __('Alpa'), 'color' => 'bg-rose-500', 'ring' => 'ring-rose-500/20', 'shadow' => 'shadow-rose-500/30']
        ];
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-{{ count($classrooms) > 1 ? 3 : 2 }} gap-4">
        <x-ui.select wire:model.live="academic_year_id" :label="__('Tahun Ajaran')" :options="$years" />
        
        @if($classrooms->count() > 1)
            <x-ui.select wire:model.live="classroom_id" :label="__('Kelas')" :placeholder="__('Pilih Kelas')" :options="$classrooms" />
        @endif

        <x-ui.input wire:model.live="date" type="date" :label="__('Tanggal')" />
    </div>

    @if($classroom_id)
        <x-ui.card shadow padding="false" wire:key="attendance-card-{{ $classroom_id }}" class="overflow-visible">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center px-6 py-4 gap-4 bg-slate-50/50 dark:bg-slate-800/20 border-b border-slate-100 dark:border-slate-800">
                <div>
                    <h3 class="text-base font-black text-slate-900 dark:text-white tracking-tight">{{ __('Daftar Kehadiran') }}</h3>
                    <p class="text-[10px] uppercase font-black tracking-widest text-slate-400 mt-0.5">{{ __('Silakan sesuaikan status jika ada yang tidak hadir') }}</p>
                </div>
                <x-ui.button 
                    wire:click="setAllStatus('h')" 
                    icon="o-check-circle" 
                    :label="__('Semua Hadir')" 
                    class="btn-sm bg-emerald-500 text-white border-none hover:bg-emerald-600 shadow-lg shadow-emerald-500/20 px-4" 
                    spinner="setAllStatus"
                    wire:key="btn-all-present"
                />
            </div>

            <x-ui.table 
                :headers="[
                    ['key' => 'name', 'label' => __('Siswa')],
                    ['key' => 'status', 'label' => __('Status'), 'class' => 'text-center']
                ]" 
                :rows="$this->students"
            >
                @scope('cell_name', $student)
                    <div class="flex items-center gap-2 sm:gap-3" wire:key="student-name-{{ $student->id }}">
                        <div class="size-8 rounded-full bg-slate-50 dark:bg-slate-800 hidden sm:flex items-center justify-center font-bold text-slate-300 text-[10px] uppercase">
                            {{ substr($student->name, 0, 1) }}
                        </div>
                        <span class="font-bold text-slate-900 dark:text-white text-[11px] sm:text-sm leading-tight">{{ $student->name }}</span>
                    </div>
                @endscope

                @scope('cell_status', $student)
                    <div class="flex justify-center items-center gap-1.5 sm:gap-2" 
                         wire:key="student-status-{{ $student->id }}-{{ $this->attendance_data[(string)$student->id] ?? 'h' }}"
                         x-data="{ current: '{{ $this->attendance_data[(string)$student->id] ?? 'h' }}' }">
                        @foreach([
                            'h' => ['label' => __('Hadir'), 'color' => 'bg-emerald-500', 'ring' => 'ring-emerald-500/30'],
                            's' => ['label' => __('Sakit'), 'color' => 'bg-amber-400', 'ring' => 'ring-amber-400/30'],
                            'i' => ['label' => __('Izin'), 'color' => 'bg-sky-500', 'ring' => 'ring-sky-500/30'],
                            'a' => ['label' => __('Alpa'), 'color' => 'bg-rose-500', 'ring' => 'ring-rose-500/30']
                        ] as $val => $meta)
                            <button type="button" 
                                @click="current = '{{ $val }}'; $wire.setStatus({{ $student->id }}, '{{ $val }}')"
                                wire:key="btn-{{ $student->id }}-{{ $val }}"
                                :class="current === '{{ $val }}' 
                                    ? '{{ $meta['color'] }} text-white border-transparent ring-2 {{ $meta['ring'] }}' 
                                    : 'bg-white dark:bg-slate-900 border-slate-100 dark:border-slate-800 text-slate-400 dark:text-slate-500 hover:border-slate-300 dark:hover:border-slate-600'"
                                class="flex items-center justify-center size-8 sm:size-auto sm:px-4 sm:py-1.5 rounded-lg sm:rounded-xl transition-all duration-300 text-[10px] sm:text-xs font-black uppercase tracking-wider border-2"
                            >
                                <span class="sm:hidden">{{ substr($meta['label'], 0, 1) }}</span>
                                <span class="hidden sm:inline">{{ $meta['label'] }}</span>
                            </button>
                        @endforeach
                    </div>
                @endscope
            </x-ui.table>

            <div class="p-6 bg-slate-50/50 dark:bg-slate-800/20 border-t border-slate-100 dark:border-slate-800 space-y-4">
                <x-ui.textarea wire:model="notes" :label="__('Catatan Tambahan')" :placeholder="__('Catatan kejadian hari ini (jika ada)...')" rows="2" />
                <div class="flex justify-end gap-2">
                    <x-ui.button :label="__('Batal')" ghost wire:click="$refresh" />
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
