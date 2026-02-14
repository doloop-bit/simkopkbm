<flux:modal name="detail-modal" class="max-w-4xl">
    @if($viewing)
        @php $viewProfile = $viewing->latestProfile?->profileable; @endphp
        <div class="space-y-6">
            <div class="flex items-start justify-between">
                <div>
                    <flux:heading size="lg">Detail Siswa</flux:heading>
                    <flux:subheading>Informasi lengkap data siswa</flux:subheading>
                </div>
            </div>

            <div class="flex items-start gap-6 pb-6 border-b border-zinc-200 dark:border-zinc-700">
                <div class="flex-shrink-0">
                    @if($viewProfile?->photo)
                        <img src="/storage/{{ $viewProfile->photo }}"
                            class="w-32 h-32 rounded-lg object-cover border-2 border-zinc-200 dark:border-zinc-700"
                            alt="{{ $viewing->name }}" />
                    @else
                        <div class="w-32 h-32 rounded-lg bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center">
                            <flux:icon icon="user" class="w-16 h-16 text-zinc-400" />
                        </div>
                    @endif
                </div>

                <div class="flex-1 space-y-2">
                    <div>
                        <flux:heading size="xl">{{ $viewing->name }}</flux:heading>
                        <flux:text class="text-zinc-600 dark:text-zinc-400">{{ $viewing->email }}</flux:text>
                    </div>

                    <div class="flex gap-2 items-center">
                        @if($viewProfile?->classroom)
                            <flux:badge variant="primary">{{ $viewProfile->classroom->name }}</flux:badge>
                        @else
                            <flux:badge variant="danger">Belum ada kelas</flux:badge>
                        @endif

                        <flux:badge variant="neutral">
                            {{ ucfirst(str_replace('_', ' ', $viewProfile?->status ?? 'baru')) }}
                        </flux:badge>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-4">
                    <flux:heading size="md" class="border-b pb-2">Identitas Siswa</flux:heading>

                    <div class="space-y-3">
                        <div>
                            <flux:text size="xs" class="text-zinc-500 dark:text-zinc-400">NIS</flux:text>
                            <flux:text>{{ $viewProfile?->nis ?? '-' }}</flux:text>
                        </div>

                        <div>
                            <flux:text size="xs" class="text-zinc-500 dark:text-zinc-400">NISN</flux:text>
                            <flux:text>{{ $viewProfile?->nisn ?? '-' }}</flux:text>
                        </div>

                        <div>
                            <flux:text size="xs" class="text-zinc-500 dark:text-zinc-400">NIK Siswa</flux:text>
                            <flux:text>{{ $viewProfile?->nik ?? '-' }}</flux:text>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <flux:text size="xs" class="text-zinc-500 dark:text-zinc-400">No. KK</flux:text>
                                <flux:text>{{ $viewProfile?->no_kk ?? '-' }}</flux:text>
                            </div>
                            <div>
                                <flux:text size="xs" class="text-zinc-500 dark:text-zinc-400">No. Akta</flux:text>
                                <flux:text>{{ $viewProfile?->no_akta ?? '-' }}</flux:text>
                            </div>
                        </div>

                        <div>
                            <flux:text size="xs" class="text-zinc-500 dark:text-zinc-400">Tempat, Tanggal Lahir
                            </flux:text>
                            <flux:text>
                                {{ $viewProfile?->pob ?? '-' }}{{ $viewProfile?->dob ? ', ' . $viewProfile->dob->format('d F Y') : '' }}
                            </flux:text>
                        </div>

                        <div>
                            <flux:text size="xs" class="text-zinc-500 dark:text-zinc-400">No. Telepon</flux:text>
                            <flux:text>{{ $viewProfile?->phone ?? '-' }}</flux:text>
                        </div>

                        <div>
                            <flux:text size="xs" class="text-zinc-500 dark:text-zinc-400">Alamat</flux:text>
                            <flux:text>{{ $viewProfile?->address ?? '-' }}</flux:text>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <flux:text size="xs" class="text-zinc-500 dark:text-zinc-400">Anak Ke-</flux:text>
                                <flux:text>{{ $viewProfile?->birth_order ?? '-' }}</flux:text>
                            </div>
                            <div>
                                <flux:text size="xs" class="text-zinc-500 dark:text-zinc-400">Dari ... Bersaudara
                                </flux:text>
                                <flux:text>{{ $viewProfile?->total_siblings ?? '-' }}</flux:text>
                            </div>
                        </div>

                        <div>
                            <flux:text size="xs" class="text-zinc-500 dark:text-zinc-400">Asal Sekolah</flux:text>
                            <flux:text>{{ $viewProfile?->previous_school ?? '-' }}</flux:text>
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <flux:heading size="md" class="border-b pb-2">Data Orang Tua / Wali</flux:heading>

                    <div class="space-y-3">
                        <div>
                            <flux:text size="xs" class="text-zinc-500 dark:text-zinc-400">Nama Ayah</flux:text>
                            <flux:text>{{ $viewProfile?->father_name ?? '-' }} (NIK: {{ $viewProfile?->nik_ayah ?? '-' }})</flux:text>
                        </div>
                        
                        <div>
                            <flux:text size="xs" class="text-zinc-500 dark:text-zinc-400">Nama Ibu</flux:text>
                            <flux:text>{{ $viewProfile?->mother_name ?? '-' }} (NIK: {{ $viewProfile?->nik_ibu ?? '-' }})</flux:text>
                        </div>

                        <div class="pt-4 space-y-3">
                            <flux:heading size="sm">Kontak Wali</flux:heading>

                            <div>
                                <flux:text size="xs" class="text-zinc-500 dark:text-zinc-400">Nama Wali</flux:text>
                                <flux:text>{{ $viewProfile?->guardian_name ?? '-' }}</flux:text>
                            </div>

                            <div>
                                <flux:text size="xs" class="text-zinc-500 dark:text-zinc-400">No. Telp Wali</flux:text>
                                <flux:text>{{ $viewProfile?->guardian_phone ?? '-' }}</flux:text>
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
                <div class="pt-6 border-t border-zinc-200 dark:border-zinc-700">
                    <flux:heading size="md" class="mb-4">Data Periodik Terbaru</flux:heading>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        @foreach($periodicRecords as $record)
                            <div class="p-4 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
                                <div class="text-xs text-zinc-500 dark:text-zinc-400 mb-2">
                                    {{ $record->academicYear->name }} - Semester {{ $record->semester }}
                                </div>
                                <div class="space-y-2">
                                    <div class="flex justify-between">
                                        <span class="text-sm text-zinc-600 dark:text-zinc-400">Berat:</span>
                                        <span class="font-medium">{{ $record->weight }} kg</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm text-zinc-600 dark:text-zinc-400">Tinggi:</span>
                                        <span class="font-medium">{{ $record->height }} cm</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm text-zinc-600 dark:text-zinc-400">Ling. Kepala:</span>
                                        <span class="font-medium">{{ $record->head_circumference }} cm</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="flex justify-end gap-2 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                <flux:modal.close>
                    <flux:button variant="ghost">Tutup</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" icon="pencil-square" wire:click="edit({{ $viewing->id }})"
                    x-on:click="$flux.modal('detail-modal').close(); $flux.modal('student-modal').show()">
                    Edit Data
                </flux:button>
            </div>
        </div>
    @endif
</flux:modal>
