<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('Rapor Diniyah') }} - {{ $student->name }}</title>
    <style>
        @page {
            margin: 1cm;
        }
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 11pt;
            line-height: 1.4;
            color: #333;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header-logo {
            width: 80px;
        }
        .header-text {
            text-align: center;
        }
        .header-school {
            font-size: 16pt;
            font-weight: bold;
            margin: 0;
            text-transform: uppercase;
        }
        .header-address {
            font-size: 10pt;
            font-style: italic;
            margin: 5px 0 0 0;
        }
        .student-info {
            width: 100%;
            margin-bottom: 20px;
        }
        .student-info td {
            padding: 2px 5px;
            vertical-align: top;
        }
        .report-title {
            text-align: center;
            font-size: 14pt;
            font-weight: bold;
            margin-bottom: 15px;
            text-decoration: underline;
            text-transform: uppercase;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table.data-table th, table.data-table td {
            border: 1px solid #000;
            padding: 5px;
            word-wrap: break-word; /* Allow content to wrap */
            overflow-wrap: break-word;
        }
        table.data-table th {
            background-color: #f2f2f2;
            text-align: center;
            font-weight: bold;
            font-size: 10pt;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        
        /* Specific layouts for Diniyah Assessment */
        .academic-table th { height: 40px; }
        .target-table th { height: 40px; }

        .attendance-table {
            width: 50%;
            margin-bottom: 20px;
        }
        .attendance-table th, .attendance-table td {
            border: 1px solid #000;
            padding: 5px;
        }

        .signature-section {
            width: 100%;
            margin-top: 30px;
        }
        .signature-box {
            width: 33%;
            text-align: center;
            float: left;
        }
        .signature-space {
            height: 70px;
        }
        .clear { clear: both; }

        .notes-box {
            border: 1px solid #000;
            padding: 10px;
            margin-bottom: 20px;
            min-height: 50px;
        }
        .label { font-weight: bold; margin-bottom: 5px; }
    </style>
</head>
<body>
    {{-- Header with distinct logos and address --}}
    <table class="header-table">
        <tr>
            <td width="15%" class="text-center">
                @if($headerInfo['logo'])
                    <img src="{{ public_path('storage/' . $headerInfo['logo']) }}" class="header-logo">
                @else
                    <div style="width: 60px; height: 60px; background: #eee; border: 1px solid #ccc; display: inline-block;"></div>
                @endif
            </td>
            <td class="header-text">
                <p class="header-school">{{ $headerInfo['name'] }}</p>
                <p class="header-school">{{ config('app.name') }}</p>
                <p class="header-address">{{ $headerInfo['address'] }}</p>
            </td>
            <td width="15%" class="text-center">
                @php $yayasanLogo = public_path('img/logo.png'); @endphp
                @if(file_exists($yayasanLogo))
                    <img src="{{ $yayasanLogo }}" class="header-logo">
                @endif
            </td>
        </tr>
    </table>

    <div class="report-title">
        LAPORAN HASIL BELAJAR DINIYAH (RAPOR DINIYAH)
    </div>

    <table class="student-info">
        <tr>
            <td width="15%">Nama Santri</td>
            <td width="2%">:</td>
            <td width="40%" class="font-bold">{{ $student->name }}</td>
            <td width="15%">Kelas</td>
            <td width="2%">:</td>
            <td width="26%">{{ $classroom->name }}</td>
        </tr>
        <tr>
            <td>Nomor Induk</td>
            <td>:</td>
            <td>{{ $studentProfile->nis ?: '-' }}</td>
            <td>Semester</td>
            <td>:</td>
            <td>{{ $semester }} ({{ $semester == 1 ? 'Ganjil' : 'Genap' }})</td>
        </tr>
        <tr>
            <td>Jenjang</td>
            <td>:</td>
            <td>{{ $classroom->level->name }}</td>
            <td>Tahun Pelajaran</td>
            <td>:</td>
            <td>{{ $academicYear->name }}</td>
        </tr>
    </table>

    @include('pdf._rapor_diniyah_content')

    <div class="signature-section">
        <div class="signature-box">
            <p>Orang Tua/Wali,</p>
            <div class="signature-space"></div>
            <p>( ................................ )</p>
        </div>
        <div class="signature-box">
            <p>Wali Kelas,</p>
            <div class="signature-space"></div>
            <p class="font-bold">{{ $teacher->name ?? '................................' }}</p>
        </div>
        <div class="signature-box">
            <p>Kepala Sekolah,</p>
            <div class="signature-space"></div>
            <p class="font-bold">H. MUSA, S.T., M.Pd</p>
        </div>
        <div class="clear"></div>
    </div>

    <div style="margin-top: 30px; font-size: 9pt; color: #666; font-style: italic;">
        Dicetak pada: {{ now()->translatedFormat('d F Y H:i') }}
    </div>
</body>
</html>
