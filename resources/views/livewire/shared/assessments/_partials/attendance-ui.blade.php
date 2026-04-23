<div class="p-4 md:p-6 space-y-4 md:space-y-8 text-slate-900 dark:text-white pb-24 md:pb-6">
    @php
        $selectedClassroomName = $classrooms->firstWhere('id', (int)$classroom_id)?->name;
    @endphp
    <x-ui.header :title="__('Presensi Rapor :class', ['class' => $selectedClassroomName ?? ''])" :subtitle="__('Rekapitulasi ketidakhadiran siswa.')" class="mb-0 md:mb-6">
        @if($classroom_id)
            <x-slot:actions>
                <x-ui.button 
                    :label="__('Ambil dari Harian')" 
                    icon="o-arrow-path" 
                    ghost
                    class="text-xs" 
                    wire:click="syncWithDaily" 
                    spinner="syncWithDaily" 
                />
                <x-ui.button :label="__('Simpan Rekap Presensi')" icon="o-check" class="btn-primary shadow-lg shadow-primary/20" wire:click="save" spinner="save" />
            </x-slot:actions>
        @endif
    </x-ui.header>

    <div class="grid grid-cols-1 md:grid-cols-{{ count($classrooms) > 1 ? 3 : 2 }} gap-6">
        <x-ui.select wire:model.live="academic_year_id" :label="__('Tahun Ajaran')" :options="$years" />
        <x-ui.select 
            wire:model.live="semester" 
            :label="__('Semester')" 
            :options="[
                ['id' => '1', 'name' => __('1 (Ganjil)')],
                ['id' => '2', 'name' => __('2 (Genap)')],
            ]" 
        />
        
        @if(count($classrooms) > 1)
            <x-ui.select 
                wire:model.live="classroom_id" 
                :label="__('Kelas / Rombel')" 
                :placeholder="__('Pilih Kelas')"
                :options="$classrooms"
            />
        @endif
    </div>

    @if($classroom_id)
        <x-ui.card shadow padding="false">
            <x-ui.table :headers="[
                ['key' => 'student_name', 'label' => __('Siswa')],
                ['key' => 'counts', 'label' => __('Rekap (S/I/A)'), 'class' => 'text-center']
            ]" :rows="$students">
                @scope('cell_student_name', $student)
                    <div class="flex flex-col">
                        <span class="font-bold text-slate-900 dark:text-white text-[11px] sm:text-sm leading-tight">{{ $student->name }}</span>
                        <span class="text-[9px] text-slate-400 font-mono tracking-tighter truncate max-w-[80px] sm:max-w-none">{{ $student->nis ?? $student->username }}</span>
                    </div>
                @endscope

                @scope('cell_counts', $student)
                    <div class="flex items-center justify-center gap-1 sm:gap-4">
                        {{-- Sick --}}
                        <div class="flex flex-col items-center">
                            <span class="text-[9px] uppercase font-bold text-amber-500 mb-0.5 sm:hidden">{{ __('S') }}</span>
                            <x-ui.input 
                                wire:model="attendance_data.{{ $student->id }}.sick" 
                                type="number" 
                                class="w-10 sm:w-20 text-center text-[11px] sm:text-sm !p-1 bg-amber-50/50 border-none ring-1 ring-amber-100"
                                min="0"
                            />
                        </div>

                        {{-- Permission --}}
                        <div class="flex flex-col items-center">
                            <span class="text-[9px] uppercase font-bold text-blue-500 mb-0.5 sm:hidden">{{ __('I') }}</span>
                            <x-ui.input 
                                wire:model="attendance_data.{{ $student->id }}.permission" 
                                type="number" 
                                class="w-10 sm:w-20 text-center text-[11px] sm:text-sm !p-1 bg-blue-50/50 border-none ring-1 ring-blue-100"
                                min="0"
                            />
                        </div>

                        {{-- Absent --}}
                        <div class="flex flex-col items-center">
                            <span class="text-[9px] uppercase font-bold text-rose-500 mb-0.5 sm:hidden">{{ __('A') }}</span>
                            <x-ui.input 
                                wire:model="attendance_data.{{ $student->id }}.absent" 
                                type="number" 
                                class="w-10 sm:w-20 text-center text-[11px] sm:text-sm !p-1 bg-rose-50/50 border-none ring-1 ring-rose-100"
                                min="0"
                            />
                        </div>
                    </div>
                @endscope
            </x-ui.table>

            @if($students->isEmpty())
                <div class="py-12 text-center text-slate-400 italic text-sm">
                    {{ __('Belum ada siswa terdaftar di kelas ini.') }}
                </div>
            @endif
        </x-ui.card>
    @else
        <div class="flex flex-col items-center justify-center py-32 text-slate-300 dark:text-slate-700 border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-[32px] bg-slate-50/50 dark:bg-slate-900/50 transition-all">
            <x-ui.icon name="o-calendar-days" class="size-20 mb-6 opacity-20" />
            <p class="text-sm font-black uppercase tracking-widest italic animate-pulse">{{ __('Pilih Kelas Untuk Memulai Rekap Presensi') }}</p>
        </div>
    @endif
</div>
