<x-ui.modal wire:model="detailModal" persistent>
    @if($viewing)
        @php $viewProfile = $viewing->latestProfile?->profileable; @endphp
        <div class="space-y-8">
            <x-ui.header :title="__('Detail Siswa')" :subtitle="__('Informasi lengkap data siswa')" separator />

            <div class="flex items-center gap-6 pb-8 border-b border-slate-100 dark:border-slate-800">
                <div class="flex-shrink-0">
                    <x-ui.avatar 
                        :image="$viewProfile?->photo ? '/storage/'.$viewProfile->photo : null" 
                        fallback="o-user" 
                        class="!w-24 !h-24 rounded-2xl shadow-xl ring-4 ring-primary/5"
                    />
                </div>

                <div class="flex-1 space-y-3">
                    <div>
                        <h2 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">{{ $viewing->name }}</h2>
                        <div class="text-xs text-slate-400 font-mono tracking-tighter">{{ $viewing->email }}</div>
                    </div>

                    <div class="flex gap-2 items-center">
                        @if($viewProfile?->classroom)
                            <x-ui.badge :label="$viewProfile->classroom->name" class="bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 text-[10px] font-black" />
                        @else
                            <x-ui.badge :label="__('Belum ada kelas')" class="bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400 text-[10px] font-black" />
                        @endif

                        <x-ui.badge 
                            :label="ucfirst(str_replace('_', ' ', $viewProfile?->status ?? 'baru'))" 
                            class="bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 text-[10px] font-black" 
                        />
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-10 max-h-[50vh] overflow-y-auto pr-4 custom-scrollbar">
                <div class="space-y-6">
                    <div class="text-[11px] font-black uppercase text-slate-400 tracking-widest border-b border-slate-100 dark:border-slate-800 pb-2">{{ __('Identitas Siswa') }}</div>

                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <div class="text-[9px] uppercase tracking-widest text-slate-400 font-black mb-1">NIS</div>
                                <div class="text-sm font-medium text-slate-700 dark:text-slate-300 font-mono">{{ $viewProfile?->nis ?? '-' }}</div>
                            </div>

                            <div>
                                <div class="text-[9px] uppercase tracking-widest text-slate-400 font-black mb-1">NISN</div>
                                <div class="text-sm font-medium text-slate-700 dark:text-slate-300 font-mono">{{ $viewProfile?->nisn ?? '-' }}</div>
                            </div>
                        </div>

                        <div>
                            <div class="text-[9px] uppercase tracking-widest text-slate-400 font-black mb-1">NIK Siswa</div>
                            <div class="text-sm font-medium text-slate-700 dark:text-slate-300 font-mono">{{ $viewProfile?->nik ?? '-' }}</div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <div class="text-[9px] uppercase tracking-widest text-slate-400 font-black mb-1">No. KK</div>
                                <div class="text-sm font-medium text-slate-700 dark:text-slate-300 font-mono">{{ $viewProfile?->no_kk ?? '-' }}</div>
                            </div>
                            <div>
                                <div class="text-[9px] uppercase tracking-widest text-slate-400 font-black mb-1">No. Akta</div>
                                <div class="text-sm font-medium text-slate-700 dark:text-slate-300 font-mono">{{ $viewProfile?->no_akta ?? '-' }}</div>
                            </div>
                        </div>

                        <div>
                            <div class="text-[9px] uppercase tracking-widest text-slate-400 font-black mb-1">{{ __('Tempat, Tanggal Lahir') }}</div>
                            <div class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                {{ $viewProfile?->pob ?? '-' }}{{ $viewProfile?->dob ? ', ' . $viewProfile->dob->format('d F Y') : '' }}
                            </div>
                        </div>

                        <div>
                            <div class="text-[9px] uppercase tracking-widest text-slate-400 font-black mb-1">{{ __('No. Telepon') }}</div>
                            <div class="text-sm font-medium text-slate-700 dark:text-slate-300 font-mono italic">{{ $viewProfile?->phone ?? '-' }}</div>
                        </div>

                        <div>
                            <div class="text-[9px] uppercase tracking-widest text-slate-400 font-black mb-1">{{ __('Alamat') }}</div>
                            <div class="text-sm font-medium text-slate-700 dark:text-slate-300 leading-relaxed">{{ $viewProfile?->address ?? '-' }}</div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <div class="text-[9px] uppercase tracking-widest text-slate-400 font-black mb-1">{{ __('Anak Ke-') }}</div>
                                <div class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ $viewProfile?->birth_order ?? '-' }}</div>
                            </div>
                            <div>
                                <div class="text-[9px] uppercase tracking-widest text-slate-400 font-black mb-1">{{ __('Dari ... Bersaudara') }}</div>
                                <div class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ $viewProfile?->total_siblings ?? '-' }}</div>
                            </div>
                        </div>

                        <div>
                            <div class="text-[9px] uppercase tracking-widest text-slate-400 font-black mb-1">{{ __('Asal Sekolah') }}</div>
                            <div class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ $viewProfile?->previous_school ?? '-' }}</div>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="text-[11px] font-black uppercase text-slate-400 tracking-widest border-b border-slate-100 dark:border-slate-800 pb-2">{{ __('Data Orang Tua / Wali') }}</div>

                    <div class="space-y-5">
                        <div class="bg-slate-50 dark:bg-slate-900/50 p-4 rounded-xl border border-slate-100 dark:border-slate-800">
                            <div class="text-[9px] uppercase tracking-widest text-slate-400 font-black mb-2">{{ __('Ayah') }}</div>
                            <div class="text-sm font-bold text-slate-900 dark:text-white">{{ $viewProfile?->father_name ?? '-' }}</div>
                            <div class="text-[10px] font-mono text-slate-400 mt-1">NIK: {{ $viewProfile?->nik_ayah ?? '-' }}</div>
                        </div>
                        
                        <div class="bg-slate-50 dark:bg-slate-900/50 p-4 rounded-xl border border-slate-100 dark:border-slate-800">
                            <div class="text-[9px] uppercase tracking-widest text-slate-400 font-black mb-2">{{ __('Ibu') }}</div>
                            <div class="text-sm font-bold text-slate-900 dark:text-white">{{ $viewProfile?->mother_name ?? '-' }}</div>
                            <div class="text-[10px] font-mono text-slate-400 mt-1">NIK: {{ $viewProfile?->nik_ibu ?? '-' }}</div>
                        </div>

                        <div class="pt-4 space-y-4">
                            <div class="text-[10px] font-black uppercase text-slate-400 tracking-widest">{{ __('Kontak Wali') }}</div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <div class="text-[9px] uppercase tracking-widest text-slate-400 font-black mb-1">{{ __('Nama Wali') }}</div>
                                    <div class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ $viewProfile?->guardian_name ?? '-' }}</div>
                                </div>

                                <div>
                                    <div class="text-[9px] uppercase tracking-widest text-slate-400 font-black mb-1">{{ __('No. Telp Wali') }}</div>
                                    <div class="text-sm font-medium text-slate-700 dark:text-slate-300 font-mono italic">{{ $viewProfile?->guardian_phone ?? '-' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @php
                $periodicRecords = $viewProfile?->periodicRecords()
                    ->with('academicYear')
                    ->orderBy('academic_year_id', 'desc')
                    ->orderBy('semester', 'desc')
                    ->limit(3)
                    ->get();
            @endphp

            @if($periodicRecords && $periodicRecords->count() > 0)
                <div class="pt-8 border-t border-slate-100 dark:border-slate-800">
                    <div class="text-[11px] font-black mb-5 uppercase text-slate-400 tracking-widest">{{ __('Data Periodik Terbaru') }}</div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        @foreach($periodicRecords as $record)
                            <div class="p-5 bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-sm">
                                <div class="text-[10px] text-slate-400 font-black mb-4 uppercase tracking-widest flex items-center justify-between">
                                    <span>{{ $record->academicYear->name }}</span>
                                    <x-ui.badge :label="'S'.$record->semester" class="bg-primary/10 text-primary border-none text-[8px] px-1.5 py-0.5" />
                                </div>
                                <div class="space-y-3">
                                    <div class="flex justify-between items-center text-xs">
                                        <span class="text-slate-400 font-medium">{{ __('Berat') }}</span>
                                        <span class="font-mono font-bold text-slate-900 dark:text-white tracking-tighter">{{ $record->weight }} <small class="text-[8px] font-black uppercase">kg</small></span>
                                    </div>
                                    <div class="flex justify-between items-center text-xs">
                                        <span class="text-slate-400 font-medium">{{ __('Tinggi') }}</span>
                                        <span class="font-mono font-bold text-slate-900 dark:text-white tracking-tighter">{{ $record->height }} <small class="text-[8px] font-black uppercase">cm</small></span>
                                    </div>
                                    <div class="flex justify-between items-center text-xs">
                                        <span class="text-slate-400 font-medium">{{ __('Ling. Kepala') }}</span>
                                        <span class="font-mono font-bold text-slate-900 dark:text-white tracking-tighter">{{ $record->head_circumference }} <small class="text-[8px] font-black uppercase">cm</small></span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="flex justify-end gap-2 pt-6 border-t border-slate-100 dark:border-slate-800">
                <x-ui.button :label="__('Tutup')" ghost @click="$set('detailModal', false)" />
                <x-ui.button :label="__('Edit Data')" icon="o-pencil-square" wire:click="edit({{ $viewing->id }})" @click="$set('detailModal', false)" class="btn-primary" />
            </div>
        </div>
    @endif
</x-ui.modal>
