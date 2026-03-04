<div class="p-6 space-y-8 text-slate-900 dark:text-white pb-24 md:pb-6">
    <x-ui.header :title="__('Penilaian Ekstrakurikuler')" :subtitle="__('Input penilaian capaian kegiatan ekstrakurikuler siswa untuk setiap kegiatan.')" separator>
        @if($classroom_id && $activity_id && !$isReadonly)
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
                ['id' => '1', 'name' => __('Semester 1')],
                ['id' => '2', 'name' => __('Semester 2')],
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
            wire:model.live="activity_id" 
            :label="__('Ekstrakurikuler')" 
            :placeholder="$classroom_id ? __('Pilih Ekstrakurikuler') : __('Pilih Kelas Terlebih Dahulu')"
            :options="$activities"
            :disabled="$isReadonly"
        />
    </div>

    @if($selectedActivity)
        <div class="p-6 rounded-[2rem] bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-800/50 flex items-center gap-6 group hover:bg-indigo-100 dark:hover:bg-indigo-900/30 transition-all duration-300">
            <div class="p-4 bg-white dark:bg-indigo-800 rounded-2xl shadow-sm ring-1 ring-indigo-100 dark:ring-indigo-700">
                <x-ui.icon name="o-trophy" class="size-10 text-indigo-600 dark:text-indigo-400 group-hover:scale-110 transition-transform" />
            </div>
            <div class="flex-1 min-w-0">
                <h3 class="text-xl font-black text-slate-900 dark:text-white tracking-tight leading-none mb-1 capitalize">{{ $selectedActivity->name }}</h3>
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
                    @if($selectedActivity->instructor)
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest flex items-center gap-1.5 whitespace-nowrap">
                            <span class="size-1.5 bg-indigo-400 rounded-full"></span>
                            {{ __('Pembina') }}: {{ $selectedActivity->instructor }}
                        </p>
                    @endif
                    @if($selectedActivity->description)
                        <p class="text-xs text-slate-400 italic truncate max-w-md">{{ $selectedActivity->description }}</p>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <div class="flex flex-wrap gap-2">
        <x-ui.badge :label="__('Sangat Baik')" class="bg-emerald-50 text-emerald-700 border-none text-[8px] font-black italic tracking-widest px-3 py-1" />
        <x-ui.badge :label="__('Baik')" class="bg-blue-50 text-blue-700 border-none text-[8px] font-black italic tracking-widest px-3 py-1" />
        <x-ui.badge :label="__('Cukup')" class="bg-amber-50 text-amber-700 border-none text-[8px] font-black italic tracking-widest px-3 py-1" />
        <x-ui.badge :label="__('Perlu Bimbingan')" class="bg-rose-50 text-rose-700 border-none text-[8px] font-black italic tracking-widest px-3 py-1" />
    </div>

    @if($classroom_id && $activity_id)
        <x-ui.card shadow padding="false">
            <x-ui.table :headers="[
                ['key' => 'student_name', 'label' => __('Nama Siswa')],
                ['key' => 'capaian_level', 'label' => __('Capaian / Predikat'), 'class' => 'w-56 text-center'],
                ['key' => 'capaian_desc', 'label' => __('Keterangan Capaian (Opsional)')]
            ]" :rows="$students">
                @scope('cell_student_name', $student)
                    <div class="flex flex-col">
                        <span class="font-bold text-slate-900 dark:text-white">{{ $student->name }}</span>
                        <span class="text-[10px] text-slate-400 font-mono tracking-tighter">{{ $student->nis ?? $student->username }}</span>
                    </div>
                @endscope

                @scope('cell_capaian_level', $student)
                    <div class="flex justify-center">
                        <x-ui.select 
                            wire:model="assessments_data.{{ $student->id }}.level" 
                            :options="[
                                ['id' => 'Sangat Baik', 'name' => __('Sangat Baik')],
                                ['id' => 'Baik', 'name' => __('Baik')],
                                ['id' => 'Cukup', 'name' => __('Cukup')],
                                ['id' => 'Perlu Ditingkatkan', 'name' => __('Perlu Ditingkatkan')],
                            ]"
                            :disabled="$isReadonly"
                            class="!py-1 font-bold text-xs"
                        />
                    </div>
                @endscope

                @scope('cell_capaian_desc', $student)
                    <x-ui.input 
                        wire:model="assessments_data.{{ $student->id }}.description" 
                        :placeholder="__('Keterangan pencapaian siswa...')"
                        :readonly="$isReadonly"
                        class="border-none bg-slate-50/50 shadow-none focus:ring-1 italic text-sm"
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
            <x-ui.icon name="o-trophy" class="size-20 mb-6 opacity-20" />
            <p class="text-sm font-black uppercase tracking-widest italic animate-pulse">{{ __('Pilih Kelas & Kegiatan Untuk Memulai Penilaian') }}</p>
        </div>
    @endif
</div>
