<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('Rapor PAUD') }} - {{ $student->name }}</title>
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
        .text-center { text-align: center; }
        .signature-section {
            width: 100%;
            margin-top: 30px;
            page-break-inside: avoid;
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
    </style>
</head>
<body>
    {{-- Header --}}
    <table class="header-table">
        <tr>
            <td width="15%" class="text-center">
                @if($schoolProfile && $schoolProfile->logo_path)
                    <img src="{{ public_path('storage/' . $schoolProfile->logo_path) }}" class="header-logo">
                @else
                    <div style="width: 60px; height: 60px; background: #eee; border: 1px solid #ccc; display: inline-block;"></div>
                @endif
            </td>
            <td class="header-text">
                <p class="header-school">{{ $schoolProfile->name ?? config('app.name') }}</p>
                <p class="header-school">PENDIDIKAN ANAK USIA DINI (PAUD)</p>
                <p class="header-address">{{ $schoolProfile->address ?? 'Alamat Sekolah' }}</p>
            </td>
            <td width="15%" class="text-center">
                @php $yayasanLogo = public_path('img/logo.png'); @endphp
                @if(file_exists($yayasanLogo))
                    <img src="{{ $yayasanLogo }}" class="header-logo">
                @endif
            </td>
        </tr>
    </table>

    {{-- Content Partial --}}
    @include('pdf._paud_rapor_content')

    {{-- Signatures --}}
    <div class="signature-section">
        <div class="signature-box">
            <p>Mengetahui,</p>
            <p>Orang Tua/Wali,</p>
            <div class="signature-space"></div>
            <p>( ................................ )</p>
        </div>
        <div class="signature-box">
            <p>&nbsp;</p>
            <p>Kepala Sekolah,</p>
            <div class="signature-space"></div>
            <p class="font-bold">{{ \App\Models\StaffMember::where('position', 'Kepala Sekolah')->first()?->name ?? '................................' }}</p>
        </div>
        <div class="signature-box">
            <p>{{ config('app.city', 'Malang') }}, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</p>
            <p>Wali Kelas,</p>
            <div class="signature-space"></div>
            <p class="font-bold">{{ $teacher->name ?? '................................' }}</p>
        </div>
        <div class="clear"></div>
    </div>

    <div style="margin-top: 30px; font-size: 9pt; color: #666; font-style: italic;">
        Dicetak pada: {{ now()->translatedFormat('d F Y H:i:s') }}
    </div>
</body>
</html>
