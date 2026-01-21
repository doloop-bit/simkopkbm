<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rapor - {{ $student->name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            text-align: center;
            border-bottom: 3px solid #000;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .header h1 {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .header p {
            font-size: 11px;
            color: #666;
        }

        .info-section {
            margin-bottom: 20px;
        }

        .info-row {
            display: flex;
            margin-bottom: 8px;
        }

        .info-label {
            width: 150px;
            font-weight: bold;
        }

        .info-value {
            flex: 1;
        }

        .section-title {
            font-size: 13px;
            font-weight: bold;
            background-color: #f0f0f0;
            padding: 8px;
            margin-top: 15px;
            margin-bottom: 10px;
            border-left: 4px solid #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        table thead {
            background-color: #e8e8e8;
        }

        table th {
            border: 1px solid #999;
            padding: 8px;
            text-align: left;
            font-weight: bold;
            font-size: 11px;
        }

        table td {
            border: 1px solid #999;
            padding: 8px;
            font-size: 11px;
        }

        table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .gpa-box {
            background-color: #f0f0f0;
            border: 2px solid #333;
            padding: 10px;
            margin: 15px 0;
            text-align: center;
        }

        .gpa-box p {
            font-size: 11px;
            color: #666;
        }

        .gpa-box .value {
            font-size: 24px;
            font-weight: bold;
            color: #000;
        }

        .notes-section {
            margin-top: 15px;
            padding: 10px;
            background-color: #f9f9f9;
            border-left: 4px solid #333;
        }

        .notes-section h4 {
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .notes-section p {
            font-size: 10px;
            line-height: 1.5;
            color: #555;
        }

        .signature-section {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
        }

        .signature-box {
            width: 30%;
            text-align: center;
        }

        .signature-box p {
            font-size: 10px;
            margin-bottom: 50px;
            font-weight: bold;
        }

        .signature-box .line {
            border-top: 1px solid #000;
            margin-top: 5px;
            padding-top: 5px;
            font-size: 10px;
        }

        .footer {
            text-align: center;
            font-size: 10px;
            color: #999;
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #ccc;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>RAPOR SISWA</h1>
            <p>{{ $academicYear->year }} - Semester {{ $reportCard->semester }}</p>
        </div>

        <!-- Student Information -->
        <div class="info-section">
            <div class="info-row">
                <div class="info-label">Nama Siswa</div>
                <div class="info-value">: {{ $student->name }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">NIS</div>
                <div class="info-value">: {{ $studentProfile->nis ?? '-' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">NISN</div>
                <div class="info-value">: {{ $studentProfile->nisn ?? '-' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Kelas</div>
                <div class="info-value">: {{ $classroom->name }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Tahun Ajaran</div>
                <div class="info-value">: {{ $academicYear->year }}</div>
            </div>
        </div>

        <!-- Scores Section -->
        <div class="section-title">Nilai Mata Pelajaran</div>
        <table>
            <thead>
                <tr>
                    <th style="width: 5%;">No</th>
                    <th style="width: 60%;">Mata Pelajaran</th>
                    <th style="width: 20%;" class="text-right">Nilai</th>
                    <th style="width: 15%;" class="text-center">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $no = 1;
                    $totalScore = 0;
                    $scoreCount = count($reportCard->scores);
                @endphp
                @foreach ($reportCard->scores as $score)
                    @php
                        $totalScore += $score['score'];
                        $grade = $score['score'] >= 85 ? 'A' : ($score['score'] >= 75 ? 'B' : ($score['score'] >= 65 ? 'C' : 'D'));
                    @endphp
                    <tr>
                        <td class="text-center">{{ $no++ }}</td>
                        <td>{{ $score['subject_name'] }}</td>
                        <td class="text-right">{{ number_format($score['score'], 2) }}</td>
                        <td class="text-center">{{ $grade }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- GPA Box -->
        <div class="gpa-box">
            <p>Indeks Prestasi Kumulatif (IPK)</p>
            <div class="value">{{ number_format($reportCard->gpa, 2) }}</div>
        </div>

        <!-- Teacher Notes -->
        @if ($reportCard->teacher_notes)
            <div class="notes-section">
                <h4>Catatan Guru Kelas</h4>
                <p>{{ $reportCard->teacher_notes }}</p>
            </div>
        @endif

        <!-- Principal Notes -->
        @if ($reportCard->principal_notes)
            <div class="notes-section">
                <h4>Catatan Kepala Sekolah</h4>
                <p>{{ $reportCard->principal_notes }}</p>
            </div>
        @endif

        <!-- Signature Section -->
        <div class="signature-section">
            <div class="signature-box">
                <p>Guru Kelas</p>
                <div class="line"></div>
            </div>
            <div class="signature-box">
                <p>Orang Tua/Wali</p>
                <div class="line"></div>
            </div>
            <div class="signature-box">
                <p>Kepala Sekolah</p>
                <div class="line"></div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>Dokumen ini dicetak dari Sistem Informasi Manajemen Sekolah (SIMKOPKBM)</p>
            <p>{{ date('d F Y H:i') }}</p>
        </div>
    </div>
</body>
</html>
