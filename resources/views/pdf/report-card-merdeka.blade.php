<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rapor Merdeka - {{ $student->name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Arial', sans-serif; font-size: 11px; line-height: 1.4; color: #333; padding: 40px; }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { font-size: 16px; text-transform: uppercase; }
        .info-table { width: 100%; margin-bottom: 20px; }
        .info-table td { padding: 3px 0; vertical-align: top; }
        .label { width: 120px; font-weight: bold; }
        .colon { width: 15px; }
        .section-title { background: #f3f4f6; padding: 5px 10px; font-weight: bold; margin: 15px 0 10px; border-left: 4px solid #1f2937; font-size: 12px; }
        table.data-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        table.data-table th, table.data-table td { border: 1px solid #000; padding: 6px 8px; text-align: left; }
        table.data-table th { background: #e5e7eb; font-weight: bold; text-align: center; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .description { font-style: italic; color: #4b5563; margin-top: 4px; display: block; }
        .signature-table { width: 100%; margin-top: 40px; }
        .signature-table td { text-align: center; width: 33%; }
        .sig-space { height: 60px; }
        .sig-name { font-weight: bold; text-decoration: underline; }
        .page-break { page-break-after: always; }
        .level-badge { padding: 2px 4px; border-radius: 3px; font-weight: bold; font-size: 9px; }
        .bb { background: #fee2e2; color: #991b1b; }
        .mb { background: #fef3c7; color: #92400e; }
        .bsh { background: #dbeafe; color: #1e40af; }
        .sb { background: #dcfce7; color: #166534; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Laporan Hasil Belajar (Rapor)</h1>
        <p>Kurikulum Merdeka</p>
    </div>

    <table class="info-table">
        <tr>
            <td class="label">Nama Peserta Didik</td><td class="colon">:</td><td>{{ $student->name }}</td>
            <td class="label">Kelas</td><td class="colon">:</td><td>{{ $classroom->name }}</td>
        </tr>
        <tr>
            <td class="label">NISN / NIS</td><td class="colon">:</td><td>{{ $studentProfile->nisn ?? '-' }} / {{ $studentProfile->nis ?? '-' }}</td>
            <td class="label">Fase</td><td class="colon">:</td><td>{{ strtoupper($classroom->level?->education_level ?? '-') }}</td>
        </tr>
        <tr>
            <td class="label">Sekolah</td><td class="colon">:</td><td>{{ config('app.name') }}</td>
            <td class="label">Semester</td><td class="colon">:</td><td>{{ $reportCard->semester }}</td>
        </tr>
        <tr>
            <td class="label">Alamat</td><td class="colon">:</td><td>{{ $studentProfile->address ?? '-' }}</td>
            <td class="label">Tahun Ajaran</td><td class="colon">:</td><td>{{ $academicYear->year }}</td>
        </tr>
    </table>

    @if(isset($reportCard->scores['paud']))
        <!-- PAUD SPECIFIC SECTION -->
        <div class="section-title">A. Laporan Perkembangan Anak</div>
        @foreach($reportCard->scores['paud'] as $item)
            <div style="margin-bottom: 15px;">
                <h4 style="font-size: 11px; margin-bottom: 5px; text-transform: uppercase;">{{ str_replace('_', ' ', $item['aspect_name']) }}</h4>
                <p style="text-align: justify; text-indent: 20px;">{{ $item['description'] }}</p>
            </div>
        @endforeach
    @else
        <!-- SD/SMP/SMA COMPETENCY SECTION -->
        <div class="section-title">A. Nilai Capaian Kompetensi</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 5%;">No</th>
                    <th style="width: 25%;">Mata Pelajaran</th>
                    <th style="width: 15%;">Predikat</th>
                    <th style="width: 55%;">Deskripsi Capaian</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reportCard->scores['competencies'] ?? [] as $index => $comp)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $comp['subject_name'] }}</td>
                    <td class="text-center">
                        <span class="level-badge {{ strtolower($comp['level']) }}">{{ $comp['level'] }}</span>
                    </td>
                    <td style="text-align: justify;">{{ $comp['description'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- P5 SECTION -->
        <div class="section-title">B. Projek Penguatan Profil Pelajar Pancasila (P5)</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 30%;">Nama Projek</th>
                    <th style="width: 20%;">Dimensi</th>
                    <th style="width: 10%;">Capaian</th>
                    <th style="width: 40%;">Catatan Projek</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reportCard->scores['p5'] ?? [] as $p5)
                <tr>
                    <td>{{ $p5['project_name'] }}</td>
                    <td>{{ str_replace('_', ' ', $p5['dimension']) }}</td>
                    <td class="text-center">{{ $p5['level'] }}</td>
                    <td>{{ $p5['description'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <!-- EXTRA/ATTENDANCE SECTION -->
    <div style="display: flex; gap: 20px;">
        <div style="width: 60%;">
            <div class="section-title">Extrakurikuler</div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Kegiatan</th>
                        <th>Predikat</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reportCard->scores['extracurricular'] ?? [] as $ekskul)
                    <tr>
                        <td>{{ $ekskul['name'] }}</td>
                        <td class="text-center">{{ $ekskul['level'] }}</td>
                        <td>{{ $ekskul['description'] ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="text-center">Tidak ada data</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="width: 35%; margin-left: 5%;">
            <div class="section-title">Ketidakhadiran</div>
            <table class="data-table">
                <tr><td>Sakit</td><td class="text-center">{{ $reportCard->scores['attendance']['sick'] ?? 0 }} hari</td></tr>
                <tr><td>Izin</td><td class="text-center">{{ $reportCard->scores['attendance']['permission'] ?? 0 }} hari</td></tr>
                <tr><td>Tanpa Keterangan</td><td class="text-center">{{ $reportCard->scores['attendance']['absent'] ?? 0 }} hari</td></tr>
            </table>
        </div>
    </div>

    <!-- NOTES SECTION -->
    <div class="section-title">Catatan & Perkembangan Karakter</div>
    <div style="border: 1px solid #000; padding: 10px; min-height: 80px;">
        <strong>Catatan Guru:</strong><br>
        {{ $reportCard->teacher_notes ?? '-' }}
        
        @if($reportCard->character_notes)
        <br><br>
        <strong>Perkembangan Karakter:</strong><br>
        {{ $reportCard->character_notes }}
        @endif
    </div>

    <!-- SIGNATURES -->
    <table class="signature-table">
        <tr>
            <td>
                Mengetahui,<br>Orang Tua/Wali
                <div class="sig-space"></div>
                ( ..................................... )
            </td>
            <td>
                Malang, {{ date('d F Y') }}<br>Guru Kelas
                <div class="sig-space"></div>
                <span class="sig-name">{{ auth()->user()->name }}</span>
            </td>
        </tr>
        <tr>
            <td colspan="2" style="padding-top: 30px;">
                Mengetahui,<br>Kepala Sekolah
                <div class="sig-space"></div>
                ( ..................................... )
            </td>
        </tr>
    </table>

    <div style="position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 8px; color: #999;">
        Dicetak otomatis oleh SIMKOPKBM pada {{ date('Y-m-d H:i:s') }}
    </div>
</body>
</html>
