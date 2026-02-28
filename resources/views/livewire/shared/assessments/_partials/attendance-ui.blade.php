<div class="p-6 space-y-8 text-slate-900 dark:text-white pb-24 md:pb-6">
    <x-ui.header :title="__('Presensi Rapor')" :subtitle="__('Input rekapitulasi ketidakhadiran siswa untuk ditampilkan di lembar rapor.')" separator>
        @if($classroom_id)
            <x-slot:actions>
                <x-ui.button :label="__('Simpan Rekap Presensi')" icon="o-check" class="btn-primary shadow-lg shadow-primary/20" wire:click="save" spinner="save" />
            </x-slot:actions>
        @endif
    </x-ui.header>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <x-ui.select wire:model.live="academic_year_id" :label="__('Tahun Ajaran')" :options="$years" />
        <x-ui.select 
            wire:model.live="semester" 
            :label="__('Semester')" 
            :options="[
                ['id' => '1', 'name' => __('1 (Ganjil)')],
                ['id' => '2', 'name' => __('2 (Genap)')],
            ]" 
        />
        <x-ui.select 
            wire:model.live="classroom_id" 
            :label="__('Kelas / Rombel')" 
            :placeholder="__('Pilih Kelas')"
            :options="$classrooms"
        />
    </div>

    @if($classroom_id)
        <x-ui.card shadow padding="false">
            <x-ui.table :headers="[
                ['key' => 'student_name', 'label' => __('Nama Siswa')],
                ['key' => 'count_sick', 'label' => __('Sakit (S)'), 'class' => 'text-center w-32'],
                ['key' => 'count_permission', 'label' => __('Izin (I)'), 'class' => 'text-center w-32'],
                ['key' => 'count_absent', 'label' => __('Alpha (A)'), 'class' => 'text-center w-32']
            ]" :rows="$students">
                @scope('cell_student_name', $student)
                    <div class="flex flex-col">
                        <span class="font-bold text-slate-900 dark:text-white">{{ $student->name }}</span>
                        <span class="text-[10px] text-slate-400 font-mono tracking-tighter">{{ $student->nis ?? $student->username }}</span>
                    </div>
                @endscope

                @scope('cell_count_sick', $student)
                    <div class="flex justify-center">
                        <x-ui.input 
                            wire:model="attendance_data.{{ $student->id }}.sick" 
                            type="number" 
                            min="0"
                            class="text-center font-black text-sm !w-20 !py-1.5 bg-emerald-50/50 border-none shadow-sm ring-1 ring-emerald-100"
                        />
                    </div>
                @endscope

                @scope('cell_count_permission', $student)
                    <div class="flex justify-center">
                        <x-ui.input 
                            wire:model="attendance_data.{{ $student->id }}.permission" 
                            type="number" 
                            min="0"
                            class="text-center font-black text-sm !w-20 !py-1.5 bg-blue-50/50 border-none shadow-sm ring-1 ring-blue-100"
                        />
                    </div>
                @endscope

                @scope('cell_count_absent', $student)
                    <div class="flex justify-center">
                        <x-ui.input 
                            wire:model="attendance_data.{{ $student->id }}.absent" 
                            type="number" 
                            min="0"
                            class="text-center font-black text-sm !w-20 !py-1.5 bg-rose-50/50 border-none shadow-sm ring-1 ring-rose-100"
                        />
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
