<div class="p-6 space-y-8 text-slate-900 dark:text-white pb-24 md:pb-6">
    <x-ui.header :title="__('Penilaian Rapor')" :subtitle="__('Input nilai akhir dan pemilihan Target Pembelajaran (TP) kompetensi untuk rapor siswa.')" separator>
        @if($classroom_id && $subject_id && !$isReadonly)
            <x-slot:actions>
                <x-ui.button :label="__('Simpan Semua Penilaian')" icon="o-check" class="btn-primary shadow-lg shadow-primary/20" wire:click="save" spinner="save" />
            </x-slot:actions>
        @endif
    </x-ui.header>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <x-ui.select wire:model.live="academic_year_id" :label="__('Tahun Ajaran')" :options="$years" :disabled="$isReadonly" />
        <x-ui.select 
            wire:model.live="semester" 
            :label="__('Semester')" 
            :options="[
                ['id' => '1', 'name' => __('1 (Ganjil)')],
                ['id' => '2', 'name' => __('2 (Genap)')],
            ]" 
            :disabled="$isReadonly" 
        />
        <x-ui.select 
            wire:model.live="classroom_id" 
            :label="__('Kelas / Rombel')" 
            :placeholder="__('Pilih Kelas')"
            :options="$classrooms"
            :disabled="$isReadonly"
        />
        <x-ui.select 
            wire:model.live="subject_id" 
            :label="__('Mata Pelajaran')" 
            :placeholder="__('Pilih Mata Pelajaran')"
            :options="$subjects"
            :disabled="$isReadonly"
        />
    </div>

    @if($currentPhase)
        <div class="flex items-center gap-3 p-3 bg-indigo-50 dark:bg-indigo-900/20 rounded-2xl border border-indigo-100 dark:border-indigo-800/50">
            <x-ui.badge :label="'Fase ' . $currentPhase" class="bg-indigo-600 text-white border-none font-black italic px-3 py-1 text-[10px]" />
            <span class="text-[10px] font-bold text-indigo-700/70 dark:text-indigo-300/70 italic">{{ __('Target Pembelajaran (TP) difilter otomatis berdasarkan Fase kurikulum kelas ini.') }}</span>
        </div>
    @endif

    @if($classroom_id && $subject_id)
        @if($tps->isEmpty())
            <x-ui.alert :title="__('Data TP Belum Tersedia')" icon="o-exclamation-triangle" class="bg-amber-50 text-amber-800 border-amber-100 italic font-medium">
                {{ __('Belum ada Target Pembelajaran (TP) untuk mata pelajaran ini pada Fase :phase. Silakan lengkapi TP di menu Master Mata Pelajaran.', ['phase' => $currentPhase]) }}
            </x-ui.alert>
        @endif

        <x-ui.card shadow padding="false">
            <x-ui.table :headers="[
                ['key' => 'student_name', 'label' => __('Nama Siswa')],
                ['key' => 'grade_score', 'label' => __('Nilai Akhir'), 'class' => 'text-center w-32'],
                ['key' => 'best_tp', 'label' => __('TP Kompeten (Kelebihan)')],
                ['key' => 'improvement_tp', 'label' => __('TP Perlu Bimbingan')]
            ]" :rows="$students">
                @scope('cell_student_name', $student)
                    <div class="flex flex-col">
                        <span class="font-bold text-slate-900 dark:text-white">{{ $student->name }}</span>
                        <span class="text-[10px] text-slate-400 font-mono tracking-tighter">{{ $student->nis ?? $student->username }}</span>
                    </div>
                @endscope

                @scope('cell_grade_score', $student)
                    <div class="flex justify-center">
                        <x-ui.input 
                            wire:model="grades_data.{{ $student->id }}.grade" 
                            type="number" 
                            min="0"
                            max="100" 
                            step="1" 
                            class="text-center font-black text-sm !w-24 !py-1.5 bg-slate-50 border-none shadow-sm ring-1 ring-slate-200" 
                            :readonly="$isReadonly"
                        />
                    </div>
                @endscope

                @scope('cell_best_tp', $student)
                    <x-ui.select
                        wire:model="grades_data.{{ $student->id }}.best_tp_ids"
                        :options="$tps"
                        option-label="code"
                        :placeholder="__('Pilih TP Terbaik...')"
                        :disabled="$isReadonly"
                        class="!py-1.5 !text-[10px] font-bold"
                        multiple
                    />
                @endscope

                @scope('cell_improvement_tp', $student)
                    <x-ui.select
                        wire:model="grades_data.{{ $student->id }}.improvement_tp_ids"
                        :options="$tps"
                        option-label="code"
                        :placeholder="__('Pilih TP Lemah...')"
                        :disabled="$isReadonly"
                        class="!py-1.5 !text-[10px] font-bold"
                        multiple
                    />
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
            <x-ui.icon name="o-pencil-square" class="size-20 mb-6 opacity-20" />
            <p class="text-sm font-black uppercase tracking-widest italic animate-pulse">{{ __('Tentukan Kelas & Mata Pelajaran Terlebih Dahulu') }}</p>
        </div>
    @endif
</div>
