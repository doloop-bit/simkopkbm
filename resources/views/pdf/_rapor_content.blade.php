<!-- resources/views/pdf/_rapor_content.blade.php -->
@php
    // Normalize data for both Preview and PDF
    $r = $reportCard;
    $s = $student;
    $sp = $studentProfile;
    $c = $classroom;
    $ay = $academicYear;
    $t = $teacher;

    $subjectGrades = collect($r->scores['subject_grades'] ?? []);

    // Group 1: Muatan Lokal (Bahasa Jawa or Muatan Lokal)
    $muatanLokal = $subjectGrades->filter(function($grade) {
        $name = strtolower($grade['subject_name'] ?? '');
        return str_contains($name, 'jawa') || str_contains($name, 'lokal') || str_contains($name, 'mulok');
    });

    // Group 2: Muatan Pemberdayaan dan Keterampilan (Prakarya, Pemberdayaan, Keterampilan)
    $pemberdayaanKeterampilan = $subjectGrades->filter(function($grade) {
        $name = strtolower($grade['subject_name'] ?? '');
        return str_contains($name, 'prakarya') || str_contains($name, 'pemberdayaan') || str_contains($name, 'keterampilan') || str_contains($name, 'vokasional');
    });

    // Group 3: Mata Pelajaran Umum (everything else)
    $umum = $subjectGrades->reject(function($grade) use ($muatanLokal, $pemberdayaanKeterampilan) {
        return $muatanLokal->contains($grade) || $pemberdayaanKeterampilan->contains($grade);
    });
@endphp

<div>
    <!-- Header -->
    <div style="text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px;">
        <h1 style="font-size: 16px; text-transform: uppercase; margin: 0;">RAPOR HASIL BELAJAR</h1>
    </div>

    <!-- Info Table -->
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 11px;">
        <tr>
            <td style="width: 60%; vertical-align: top; padding-right: 20px;">
                <table style="width: 100%;">
                    <tr><td style="width: 100px; padding: 2px 0;">Nama Murid</td><td style="width: 10px;">:</td><td style="font-weight: bold;">{{ $s->name }}</td></tr>
                    <tr><td style="padding: 2px 0;">NISN</td><td>:</td><td>{{ $sp->nisn ?? '-' }}</td></tr>
                    <tr><td style="padding: 2px 0;">Sekolah</td><td>:</td><td>{{ config('app.name') }}</td></tr>
                    <tr><td style="padding: 2px 0;">Alamat</td><td>:</td><td>{{ $sp->address ?? '-' }}</td></tr>
                </table>
            </td>
            <td style="width: 40%; vertical-align: top;">
                <table style="width: 100%;">
                    <tr><td style="width: 100px; padding: 2px 0;">Kelas</td><td style="width: 10px;">:</td><td>{{ $c->name }}</td></tr>
                    <tr><td style="padding: 2px 0;">Fase</td><td>:</td><td>{{ strtoupper($c->level?->phase ?? $c->level?->education_level ?? '-') }}</td></tr>
                    <tr><td style="padding: 2px 0;">Semester</td><td>:</td><td>{{ $r->semester }}</td></tr>
                    <tr><td style="padding: 2px 0;">Tahun Ajaran</td><td>:</td><td>{{ $ay->name ?? $ay->year }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    @if(isset($r->scores['paud']))
        <!-- PAUD SECTION -->
        <div style="background: #f3f4f6; padding: 5px 10px; font-weight: bold; margin: 15px 0 10px; border-left: 4px solid #1f2937; font-size: 12px;">A. Laporan Perkembangan Anak</div>
        @foreach($r->scores['paud'] as $item)
            <div style="margin-bottom: 15px; font-size: 11px;">
                <h4 style="margin: 0 0 5px; text-transform: uppercase;">{{ str_replace('_', ' ', $item['aspect_name']) }}</h4>
                <p style="text-align: justify; margin: 0;">{{ $item['description'] }}</p>
            </div>
        @endforeach
    @else
        <!-- STANDARD SECTION -->
        <div style="background: #f3f4f6; padding: 5px 10px; font-weight: bold; margin: 15px 0 10px; border-left: 4px solid #1f2937; font-size: 12px;">A. Nilai Capaian Kompetensi</div>
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 15px; font-size: 11px;" class="data-table">
            <thead>
                <tr>
                    <th style="border: 1px solid #000; padding: 6px; background: #e5e7eb; width: 5%;">No</th>
                    <th style="border: 1px solid #000; padding: 6px; background: #e5e7eb; width: 30%;">Mata Pelajaran</th>
                    <th style="border: 1px solid #000; padding: 6px; background: #e5e7eb; width: 10%;">Nilai Akhir</th>
                    <th style="border: 1px solid #000; padding: 6px; background: #e5e7eb; width: 55%;">Capaian Kompetensi</th>
                </tr>
            </thead>
            <tbody>
                <!-- Kelompok Pelajaran Umum -->
                <tr>
                    <td colspan="4" style="border: 1px solid #000; padding: 6px; background: #f9fafb; font-style: italic; font-weight: bold;">Kelompok Pelajaran Umum</td>
                </tr>
                @forelse($umum as $index => $grade)
                <tr>
                    <td style="border: 1px solid #000; padding: 6px; text-align: center;">{{ $loop->iteration }}</td>
                    <td style="border: 1px solid #000; padding: 6px;">{{ $grade['subject_name'] }}</td>
                    <td style="border: 1px solid #000; padding: 6px; text-align: center; font-weight: bold;">{{ number_format($grade['grade'], 2, ',', '.') }}</td>
                    <td style="border: 1px solid #000; padding: 6px; font-size: 10px;">
                        @if(!empty($grade['best_tp']))
                            <div style="margin-bottom: 5px;">
                                <strong>Menunjukkan penguasaan dalam:</strong>
                                @foreach((array)$grade['best_tp'] as $tp)
                                    <div style="margin-left: 10px;">- {{ $tp }}</div>
                                @endforeach
                            </div>
                        @endif
                        @if(!empty($grade['improvement_tp']))
                            <div>
                                <strong>Perlu bantuan dalam:</strong>
                                @foreach((array)$grade['improvement_tp'] as $tp)
                                    <div style="margin-left: 10px;">- {{ $tp }}</div>
                                @endforeach
                            </div>
                        @endif
                        @if(empty($grade['best_tp']) && empty($grade['improvement_tp']))
                            <span style="color: #999; font-style: italic;">Belum ada deskripsi capaian</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" style="border: 1px solid #000; padding: 10px; text-align: center; color: #999;">Tidak ada data nilai kelompok umum</td></tr>
                @endforelse

                <!-- Kelompok Muatan Pemberdayaan dan Keterampilan -->
                <tr>
                    <td colspan="4" style="border: 1px solid #000; padding: 6px; background: #f9fafb; font-style: italic; font-weight: bold;">Muatan Pemberdayaan dan Keterampilan</td>
                </tr>
                @forelse($pemberdayaanKeterampilan as $index => $grade)
                <tr>
                    <td style="border: 1px solid #000; padding: 6px; text-align: center;">{{ $loop->iteration }}</td>
                    <td style="border: 1px solid #000; padding: 6px;">{{ $grade['subject_name'] }}</td>
                    <td style="border: 1px solid #000; padding: 6px; text-align: center; font-weight: bold;">{{ number_format($grade['grade'], 2, ',', '.') }}</td>
                    <td style="border: 1px solid #000; padding: 6px; font-size: 10px;">
                        @if(!empty($grade['best_tp']))
                            <div style="margin-bottom: 5px;">
                                <strong>Menunjukkan penguasaan dalam:</strong>
                                @foreach((array)$grade['best_tp'] as $tp)
                                    <div style="margin-left: 10px;">- {{ $tp }}</div>
                                @endforeach
                            </div>
                        @endif
                        @if(!empty($grade['improvement_tp']))
                            <div>
                                <strong>Perlu bantuan dalam:</strong>
                                @foreach((array)$grade['improvement_tp'] as $tp)
                                    <div style="margin-left: 10px;">- {{ $tp }}</div>
                                @endforeach
                            </div>
                        @endif
                        @if(empty($grade['best_tp']) && empty($grade['improvement_tp']))
                            <span style="color: #999; font-style: italic;">Belum ada deskripsi capaian</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" style="border: 1px solid #000; padding: 10px; text-align: center; color: #999;">Tidak ada data nilai kelompok pemberdayaan dan keterampilan</td></tr>
                @endforelse

                <!-- Kelompok Muatan Lokal -->
                <tr>
                    <td colspan="4" style="border: 1px solid #000; padding: 6px; background: #f9fafb; font-style: italic; font-weight: bold;">Muatan Lokal</td>
                </tr>
                @forelse($muatanLokal as $index => $grade)
                <tr>
                    <td style="border: 1px solid #000; padding: 6px; text-align: center;">{{ $loop->iteration }}</td>
                    <td style="border: 1px solid #000; padding: 6px;">{{ $grade['subject_name'] }}</td>
                    <td style="border: 1px solid #000; padding: 6px; text-align: center; font-weight: bold;">{{ number_format($grade['grade'], 2, ',', '.') }}</td>
                    <td style="border: 1px solid #000; padding: 6px; font-size: 10px;">
                        @if(!empty($grade['best_tp']))
                            <div style="margin-bottom: 5px;">
                                <strong>Menunjukkan penguasaan dalam:</strong>
                                @foreach((array)$grade['best_tp'] as $tp)
                                    <div style="margin-left: 10px;">- {{ $tp }}</div>
                                @endforeach
                            </div>
                        @endif
                        @if(!empty($grade['improvement_tp']))
                            <div>
                                <strong>Perlu bantuan dalam:</strong>
                                @foreach((array)$grade['improvement_tp'] as $tp)
                                    <div style="margin-left: 10px;">- {{ $tp }}</div>
                                @endforeach
                            </div>
                        @endif
                        @if(empty($grade['best_tp']) && empty($grade['improvement_tp']))
                            <span style="color: #999; font-style: italic;">Belum ada deskripsi capaian</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" style="border: 1px solid #000; padding: 10px; text-align: center; color: #999;">Tidak ada data nilai kelompok muatan lokal</td></tr>
                @endforelse
            </tbody>
        </table>
    @endif

    <!-- Extra/Attendance -->
    <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
        <tr>
            <td style="width: 60%; vertical-align: top; padding-right: 20px;">
                <div style="background: #f3f4f6; padding: 5px 10px; font-weight: bold; margin-bottom: 10px; border-left: 4px solid #1f2937; font-size: 12px;">Ekstrakurikuler</div>
                <table style="width: 100%; border-collapse: collapse; font-size: 11px;">
                    <thead>
                        <tr>
                            <th style="border: 1px solid #000; padding: 6px; background: #f9fafb;">Kegiatan</th>
                            <th style="border: 1px solid #000; padding: 6px; background: #f9fafb;">Predikat</th>
                            <th style="border: 1px solid #000; padding: 6px; background: #f9fafb;">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($r->scores['extracurricular'] ?? [] as $ekskul)
                        <tr>
                            <td style="border: 1px solid #000; padding: 6px;">{{ $ekskul['name'] }}</td>
                            <td style="border: 1px solid #000; padding: 6px; text-align: center;">{{ $ekskul['level'] }}</td>
                            <td style="border: 1px solid #000; padding: 6px;">{{ $ekskul['description'] ?? '-' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="3" style="border: 1px solid #000; padding: 6px; text-align: center; color: #999;">Tidak ada data</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </td>
            <td style="width: 40%; vertical-align: top;">
                <div style="background: #f3f4f6; padding: 5px 10px; font-weight: bold; margin-bottom: 10px; border-left: 4px solid #1f2937; font-size: 12px;">Ketidakhadiran</div>
                <table style="width: 100%; border-collapse: collapse; font-size: 11px;">
                    <tr><td style="border: 1px solid #000; padding: 6px;">Sakit</td><td style="border: 1px solid #000; padding: 6px; text-align: center; width: 80px;">{{ $r->scores['attendance']['sick'] ?? 0 }} hari</td></tr>
                    <tr><td style="border: 1px solid #000; padding: 6px;">Izin</td><td style="border: 1px solid #000; padding: 6px; text-align: center;">{{ $r->scores['attendance']['permission'] ?? 0 }} hari</td></tr>
                    <tr><td style="border: 1px solid #000; padding: 6px;">Tanpa Keterangan</td><td style="border: 1px solid #000; padding: 6px; text-align: center;">{{ $r->scores['attendance']['absent'] ?? 0 }} hari</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Notes -->
    <div style="background: #f3f4f6; padding: 5px 10px; font-weight: bold; margin: 20px 0 10px; border-left: 4px solid #1f2937; font-size: 12px;">Catatan & Perkembangan Karakter</div>
    <div style="border: 1px solid #000; padding: 10px; min-height: 80px; font-size: 11px; text-align: justify;">
        <strong>Catatan Guru:</strong><br>
        {{ $r->teacher_notes ?? '-' }}
        
        @if($r->character_notes)
        <br><br>
        <strong>Perkembangan Karakter:</strong><br>
        {{ $r->character_notes }}
        @endif
    </div>

    <!-- Signatures -->
    <table style="width: 100%; margin-top: 40px; font-size: 11px;">
        <tr>
            <td style="text-align: center; width: 33%;">
                Mengetahui,<br>Orang Tua/Wali
                <div style="height: 60px;"></div>
                ( ..................................... )
            </td>
            <td style="width: 33%;"></td>
            <td style="text-align: center; width: 33%;">
                {{ config('app.city', 'Kab. Semarang') }}, {{ date('d F Y') }}<br>Guru Kelas
                <div style="height: 60px;"></div>
                <span style="font-weight: bold; text-decoration: underline;">{{ $t->name }}</span>
            </td>
        </tr>
        <tr>
            <td colspan="3" style="text-align: center; padding-top: 30px;">
                Mengetahui,<br>Kepala Sekolah
                <div style="height: 60px;"></div>
                ( ..................................... )
            </td>
        </tr>
    </table>
</div>
