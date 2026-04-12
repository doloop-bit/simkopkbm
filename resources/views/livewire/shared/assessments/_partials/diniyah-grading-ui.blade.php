<div class="space-y-6">
    @if($currentDiniyahSubject)
        <div class="p-4 bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-2xl flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h3 class="font-bold text-lg text-slate-900 dark:text-white">{{ $currentDiniyahSubject->name }}</h3>
                <p class="text-sm text-slate-500">
                    Tipe: <span class="font-semibold">{{ $currentDiniyahSubject->assessment_type === 'numeric' ? 'Nilai Angka' : 'Target & Capaian' }}</span>
                </p>
            </div>
            
            @if($currentDiniyahSubject->assessment_type === 'target_achievement')
                <div class="flex items-start gap-3 bg-white dark:bg-slate-800 p-3 rounded-xl border border-indigo-100 dark:border-indigo-900/30 shadow-sm">
                    <x-ui.icon name="o-information-circle" class="size-5 text-indigo-500 mt-0.5" />
                    <div>
                        <span class="text-[10px] font-black text-indigo-500 uppercase tracking-widest leading-none block mb-1">Target Mapel</span>
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-200">{{ $currentDiniyahSubject->target ?: 'Tidak ada target spesifik' }}</span>
                    </div>
                </div>
            @endif
        </div>

        <x-ui.card shadow padding="false">
            <x-ui.table :headers="[
                ['key' => 'student_name', 'label' => __('Nama Siswa')],
                ...($currentDiniyahSubject->assessment_type === 'numeric' ? [
                    ['key' => 'knowledge', 'label' => __('Pengetahuan'), 'class' => 'w-24 text-center'],
                    ...($currentDiniyahSubject->has_practice ? [['key' => 'practice', 'label' => __('Praktek'), 'class' => 'w-24 text-center']] : []),
                    ['key' => 'attitude', 'label' => __('Sikap'), 'class' => 'w-24 text-center'],
                ] : [
                    ['key' => 'achievement', 'label' => __('Capaian'), 'class' => 'w-48'],
                    ['key' => 'grade', 'label' => __('Nilai'), 'class' => 'w-24 text-center'],
                    ['key' => 'target_status', 'label' => __('Status'), 'class' => 'w-32'],
                ])
            ]" :rows="$students">
                
                @scope('cell_student_name', $student)
                    <div class="flex flex-col">
                        <span class="font-bold text-slate-900 dark:text-white">{{ $student->name }}</span>
                        <span class="text-[10px] text-slate-400 font-mono tracking-tighter">{{ $student->nis ?? $student->username }}</span>
                    </div>
                @endscope

                {{-- Numeric Scopes --}}
                @scope('cell_knowledge', $student)
                    <div class="flex justify-center">
                        <x-ui.input 
                            wire:model="grades_data.{{ $student->id }}.knowledge_grade" 
                            type="number" 
                            min="0" max="100" step="0.01"
                            class="!py-1 font-bold text-center !w-20"
                            placeholder="0-100"
                        />
                    </div>
                @endscope

                @scope('cell_practice', $student)
                    <div class="flex justify-center">
                        <x-ui.input 
                            wire:model="grades_data.{{ $student->id }}.practice_grade" 
                            type="number" 
                            min="0" max="100" step="0.01"
                            class="!py-1 font-bold text-center !w-20"
                            placeholder="0-100"
                        />
                    </div>
                @endscope

                @scope('cell_attitude', $student)
                    <div class="flex justify-center">
                        <x-ui.select 
                            wire:model="grades_data.{{ $student->id }}.attitude_grade" 
                            :options="[
                                ['id' => 'A', 'name' => 'A'],
                                ['id' => 'B', 'name' => 'B'],
                                ['id' => 'C', 'name' => 'C'],
                                ['id' => 'D', 'name' => 'D'],
                            ]" 
                            class="!py-1 font-black !w-16 text-center"
                        />
                    </div>
                @endscope

                {{-- Target Achievement Scopes --}}
                @scope('cell_achievement', $student)
                    <x-ui.input 
                        wire:model="grades_data.{{ $student->id }}.achievement" 
                        placeholder="Contoh: Hafal 4 surat"
                        class="!py-1 text-sm border-none bg-slate-50/50"
                    />
                @endscope

                @scope('cell_grade', $student)
                    <div class="flex justify-center">
                        <x-ui.input 
                            wire:model="grades_data.{{ $student->id }}.grade" 
                            type="number" 
                            min="0" max="100" step="0.01"
                            class="!py-1 font-bold text-center !w-20"
                            placeholder="0-100"
                        />
                    </div>
                @endscope

                @scope('cell_target_status', $student)
                    <div class="flex justify-center">
                        <x-ui.select 
                            wire:model="grades_data.{{ $student->id }}.target_status" 
                            :options="[
                                ['id' => 'Tercapai', 'name' => 'Tercapai'],
                                ['id' => 'Belum Tercapai', 'name' => 'Belum'],
                            ]" 
                            class="!py-1 font-bold !w-28 text-sm"
                            placeholder="Pilih..."
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
            <x-ui.icon name="o-book-open" class="size-20 mb-6 opacity-20" />
            <p class="text-sm font-black uppercase tracking-widest italic animate-pulse">{{ __('Pilih Kelas & Mata Pelajaran Diniyah Terlebih Dahulu') }}</p>
        </div>
    @endif
</div>
