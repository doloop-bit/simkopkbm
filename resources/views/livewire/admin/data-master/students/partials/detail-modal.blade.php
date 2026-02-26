<x-modal id="detail-modal" class="backdrop-blur" persistent>
    @if($viewing)
        @php $viewProfile = $viewing->latestProfile?->profileable; @endphp
        <div class="space-y-6">
            <x-header title="Detail Siswa" subtitle="Informasi lengkap data siswa" separator />

            <div class="flex items-start gap-6 pb-6 border-b border-base-200">
                <div class="flex-shrink-0">
                    <x-avatar 
                        image="{{ $viewProfile?->photo ? '/storage/'.$viewProfile->photo : null }}" 
                        fallback="o-user" 
                        class="!w-32 !h-32 rounded-lg"
                    />
                </div>

                <div class="flex-1 space-y-2">
                    <div>
                        <h2 class="text-2xl font-bold">{{ $viewing->name }}</h2>
                        <div class="text-sm opacity-60">{{ $viewing->email }}</div>
                    </div>

                    <div class="flex gap-2 items-center">
                        @if($viewProfile?->classroom)
                            <x-badge :label="$viewProfile->classroom->name" class="badge-primary" />
                        @else
                            <x-badge label="Belum ada kelas" class="badge-error" />
                        @endif

                        <x-badge 
                            :label="ucfirst(str_replace('_', ' ', $viewProfile?->status ?? 'baru'))" 
                            class="badge-neutral" 
                        />
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 h-[40vh] overflow-y-auto pr-2 custom-scrollbar">
                <div class="space-y-4">
                    <div class="font-bold border-b pb-2 text-sm opacity-70">Identitas Siswa</div>

                    <div class="space-y-3">
                        <div>
                            <div class="text-[10px] uppercase tracking-widest opacity-50 font-bold">NIS</div>
                            <div class="text-sm">{{ $viewProfile?->nis ?? '-' }}</div>
                        </div>

                        <div>
                            <div class="text-[10px] uppercase tracking-widest opacity-50 font-bold">NISN</div>
                            <div class="text-sm">{{ $viewProfile?->nisn ?? '-' }}</div>
                        </div>

                        <div>
                            <div class="text-[10px] uppercase tracking-widest opacity-50 font-bold">NIK Siswa</div>
                            <div class="text-sm">{{ $viewProfile?->nik ?? '-' }}</div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <div class="text-[10px] uppercase tracking-widest opacity-50 font-bold">No. KK</div>
                                <div class="text-sm">{{ $viewProfile?->no_kk ?? '-' }}</div>
                            </div>
                            <div>
                                <div class="text-[10px] uppercase tracking-widest opacity-50 font-bold">No. Akta</div>
                                <div class="text-sm">{{ $viewProfile?->no_akta ?? '-' }}</div>
                            </div>
                        </div>

                        <div>
                            <div class="text-[10px] uppercase tracking-widest opacity-50 font-bold">Tempat, Tanggal Lahir</div>
                            <div class="text-sm">
                                {{ $viewProfile?->pob ?? '-' }}{{ $viewProfile?->dob ? ', ' . $viewProfile->dob->format('d F Y') : '' }}
                            </div>
                        </div>

                        <div>
                            <div class="text-[10px] uppercase tracking-widest opacity-50 font-bold">No. Telepon</div>
                            <div class="text-sm">{{ $viewProfile?->phone ?? '-' }}</div>
                        </div>

                        <div>
                            <div class="text-[10px] uppercase tracking-widest opacity-50 font-bold">Alamat</div>
                            <div class="text-sm">{{ $viewProfile?->address ?? '-' }}</div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <div class="text-[10px] uppercase tracking-widest opacity-50 font-bold">Anak Ke-</div>
                                <div class="text-sm">{{ $viewProfile?->birth_order ?? '-' }}</div>
                            </div>
                            <div>
                                <div class="text-[10px] uppercase tracking-widest opacity-50 font-bold">Dari ... Bersaudara</div>
                                <div class="text-sm">{{ $viewProfile?->total_siblings ?? '-' }}</div>
                            </div>
                        </div>

                        <div>
                            <div class="text-[10px] uppercase tracking-widest opacity-50 font-bold">Asal Sekolah</div>
                            <div class="text-sm">{{ $viewProfile?->previous_school ?? '-' }}</div>
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="font-bold border-b pb-2 text-sm opacity-70">Data Orang Tua / Wali</div>

                    <div class="space-y-3">
                        <div>
                            <div class="text-[10px] uppercase tracking-widest opacity-50 font-bold">Nama Ayah</div>
                            <div class="text-sm">{{ $viewProfile?->father_name ?? '-' }} (NIK: {{ $viewProfile?->nik_ayah ?? '-' }})</div>
                        </div>
                        
                        <div>
                            <div class="text-[10px] uppercase tracking-widest opacity-50 font-bold">Nama Ibu</div>
                            <div class="text-sm">{{ $viewProfile?->mother_name ?? '-' }} (NIK: {{ $viewProfile?->nik_ibu ?? '-' }})</div>
                        </div>

                        <div class="pt-4 space-y-3">
                            <div class="font-bold text-xs opacity-70 uppercase tracking-widest">Kontak Wali</div>

                            <div>
                                <div class="text-[10px] uppercase tracking-widest opacity-50 font-bold">Nama Wali</div>
                                <div class="text-sm">{{ $viewProfile?->guardian_name ?? '-' }}</div>
                            </div>

                            <div>
                                <div class="text-[10px] uppercase tracking-widest opacity-50 font-bold">No. Telp Wali</div>
                                <div class="text-sm">{{ $viewProfile?->guardian_phone ?? '-' }}</div>
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
                <div class="pt-6 border-t border-base-200">
                    <div class="font-bold mb-4 text-sm opacity-70 italic">Data Periodik Terbaru</div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        @foreach($periodicRecords as $record)
                            <div class="p-4 bg-base-200 rounded-lg shadow-inner">
                                <div class="text-[10px] opacity-50 font-bold mb-2 uppercase tracking-widest">
                                    {{ $record->academicYear->name }} - Sem {{ $record->semester }}
                                </div>
                                <div class="space-y-2">
                                    <div class="flex justify-between text-sm">
                                        <span class="opacity-60">Berat:</span>
                                        <span class="font-mono font-bold">{{ $record->weight }} kg</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="opacity-60">Tinggi:</span>
                                        <span class="font-mono font-bold">{{ $record->height }} cm</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="opacity-60">Ling. Kepala:</span>
                                        <span class="font-mono font-bold">{{ $record->head_circumference }} cm</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <x-slot:actions>
                <x-button label="Tutup" @click="$dispatch('close-modal', 'detail-modal')" />
                <x-button label="Edit Data" icon="o-pencil-square" wire:click="edit({{ $viewing->id }})" @click="$dispatch('close-modal', 'detail-modal'); $dispatch('open-modal', 'student-modal')" class="btn-primary" />
            </x-slot:actions>
        </div>
    @endif
</x-modal>
