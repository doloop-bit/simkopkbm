<div class="space-y-8">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <x-ui.select wire:model.live="academic_year_id" :label="__('Tahun Ajaran')" :options="$years" />
        <x-ui.select 
            wire:model.live="semester" 
            :label="__('Semester')" 
            :options="[
                ['id' => '1', 'name' => __('Semester 1')],
                ['id' => '2', 'name' => __('Semester 2')],
            ]" 
        />
        <x-ui.select 
            wire:model.live="classroom_id" 
            :label="__('Kelas')" 
            :placeholder="__('Pilih Kelas')"
            :options="$classrooms"
        />
        <x-ui.select 
            wire:model.live="student_id" 
            :label="__('Siswa')" 
            :placeholder="__('Pilih Siswa')"
            :options="$students"
            :disabled="!$classroom_id"
        />
    </div>

    @if($student_id)
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2 space-y-6">
                <x-ui.card :title="__('Informasi Generasi Rapor')" shadow separator>
                    <div class="space-y-4">
                        <x-ui.textarea 
                            wire:model="teacher_notes" 
                            :label="__('Catatan Pembimbing / Guru Diniyah')" 
                            placeholder="Tuliskan catatan keseluruhan untuk perkembangan religi siswa..."
                            rows="4"
                        />
                        
                        <div class="flex justify-end">
                            <x-ui.button 
                                :label="__('Generate / Update Rapor Diniyah')" 
                                icon="o-sparkles" 
                                class="btn-primary shadow-lg shadow-primary/20" 
                                wire:click="generate" 
                                spinner="generate" 
                            />
                        </div>
                    </div>
                </x-ui.card>

                {{-- Preview Section or History could go here --}}
            </div>

            <div class="space-y-6">
                <x-ui.card :title="__('Status Rapor Siswa')" shadow separator>
                    @if(isset($existingReports[$student_id]))
                        <div class="flex flex-col items-center text-center p-4">
                            <div class="size-16 rounded-full bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 flex items-center justify-center mb-4">
                                <x-ui.icon name="o-document-check" class="size-8" />
                            </div>
                            <h4 class="font-bold text-slate-900 dark:text-white mb-1">{{ __('Rapor Tersedia') }}</h4>
                            <p class="text-xs text-slate-500 mb-6">{{ __('Terakhir diperbarui:') }} {{ $existingReports[$student_id]->updated_at->format('d/m/Y H:i') }}</p>
                            
                            <x-ui.button 
                                :label="__('Download PDF')" 
                                icon="o-arrow-down-tray" 
                                class="btn-outline btn-block" 
                                wire:click="downloadPdf({{ $existingReports[$student_id]->id }})" 
                            />
                        </div>
                    @else
                        <div class="flex flex-col items-center text-center p-4">
                            <div class="size-16 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center mb-4 border-2 border-dashed border-slate-200 dark:border-slate-700">
                                <x-ui.icon name="o-document" class="size-8" />
                            </div>
                            <h4 class="font-bold text-slate-400 mb-1">{{ __('Belum Tersedia') }}</h4>
                            <p class="text-xs text-slate-400">{{ __('Silakan klik Generate untuk membuat rapor diniyah.') }}</p>
                        </div>
                    @endif
                </x-ui.card>
            </div>
        </div>
    @else
        <div class="flex flex-col items-center justify-center py-32 text-slate-300 dark:text-slate-700 border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-[32px] bg-slate-50/50 dark:bg-slate-900/50 transition-all">
            <x-ui.icon name="o-printer" class="size-20 mb-6 opacity-20" />
            <p class="text-sm font-black uppercase tracking-widest italic animate-pulse">{{ __('Pilih Kelas & Siswa Terlebih Dahulu') }}</p>
        </div>
    @endif
</div>
