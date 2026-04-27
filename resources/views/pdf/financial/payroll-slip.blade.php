<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Slip Gaji - {{ $payroll->user->name }} - {{ $payroll->month }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 100%;
            padding: 30px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #444;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 20px;
            text-transform: uppercase;
        }
        .header p {
            margin: 5px 0 0;
            color: #666;
        }
        .info-table {
            width: 100%;
            margin-bottom: 20px;
        }
        .info-table td {
            padding: 3px 0;
            vertical-align: top;
        }
        .info-label {
            width: 120px;
            font-weight: bold;
        }
        .section-title {
            background-color: #f4f4f4;
            padding: 5px 10px;
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 10px;
            border-left: 4px solid #059669;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .details-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #eee;
        }
        .details-table .amount {
            text-align: right;
            font-family: 'Courier New', Courier, monospace;
        }
        .summary-table {
            width: 100%;
            margin-top: 20px;
            border-top: 2px solid #444;
        }
        .summary-table td {
            padding: 10px;
            font-size: 14px;
        }
        .net-salary {
            font-weight: bold;
            background-color: #059669;
            color: white;
        }
        .footer {
            margin-top: 50px;
            width: 100%;
        }
        .footer-table {
            width: 100%;
        }
        .footer-table td {
            width: 50%;
            text-align: center;
        }
        .signature-space {
            height: 80px;
        }
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 80px;
            color: rgba(0,0,0,0.05);
            z-index: -1;
            white-space: nowrap;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>SLIP GAJI KARYAWAN</h1>
            <p>{{ config('app.name') }}</p>
            @if(isset($schoolProfile))
                <p>{{ $schoolProfile->address }}</p>
            @endif
        </div>

        <div class="watermark">{{ strtoupper($payroll->status) }}</div>

        <table class="info-table">
            <tr>
                <td class="info-label">Nama PTK</td>
                <td>: {{ $payroll->user->name }}</td>
                <td class="info-label">Bulan</td>
                <td>: {{ \Carbon\Carbon::parse($payroll->month.'-01')->translatedFormat('F Y') }}</td>
            </tr>
            <tr>
                <td class="info-label">Jabatan</td>
                <td>: {{ ucfirst($payroll->user->role) }}</td>
                <td class="info-label">Tahun Ajaran</td>
                <td>: {{ $payroll->academicYear->name ?? '-' }}</td>
            </tr>
            <tr>
                <td class="info-label">ID Slip</td>
                <td>: #PAY-{{ str_pad($payroll->id, 6, '0', STR_PAD_LEFT) }}</td>
                <td class="info-label">Status</td>
                <td>: {{ strtoupper($payroll->status) }}</td>
            </tr>
        </table>

        <div class="section-title">PENGHASILAN</div>
        <table class="details-table">
            <tr>
                <td>Gaji Pokok</td>
                <td class="amount">Rp {{ number_format($payroll->base_salary, 0, ',', '.') }}</td>
            </tr>
            @foreach($payroll->getGroupedComponents()['allowances'] as $item)
                <tr>
                    <td>{{ $item['name'] }}</td>
                    <td class="amount">Rp {{ number_format($item['amount'], 0, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr style="font-weight: bold; background-color: #f9f9f9;">
                <td>Total Penghasilan (A)</td>
                <td class="amount">Rp {{ number_format($payroll->base_salary + $payroll->total_allowances, 0, ',', '.') }}</td>
            </tr>
        </table>

        @if($payroll->total_deductions > 0)
            <div class="section-title">POTONGAN</div>
            <table class="details-table">
                @foreach($payroll->getGroupedComponents()['deductions'] as $item)
                    <tr>
                        <td>{{ $item['name'] }} ({{ $item['description'] ?? '-' }})</td>
                        <td class="amount">Rp {{ number_format($item['amount'], 0, ',', '.') }}</td>
                    </tr>
                @endforeach
                <tr style="font-weight: bold; background-color: #f9f9f9;">
                    <td>Total Potongan (B)</td>
                    <td class="amount">Rp {{ number_format($payroll->total_deductions, 0, ',', '.') }}</td>
                </tr>
            </table>
        @endif

        <table class="summary-table">
            <tr class="net-salary">
                <td>TOTAL GAJI BERSIH (A - B)</td>
                <td style="text-align: right; font-size: 16px;">
                    Rp {{ number_format($payroll->net_salary, 0, ',', '.') }}
                </td>
            </tr>
            <tr>
                <td colspan="2" style="font-style: italic; font-size: 11px; padding-top: 5px;">
                    Terbilang: {{ \Illuminate\Support\Str::title($payroll->getAmountInWords()) }} Rupiah
                </td>
            </tr>
        </table>

        @if($payroll->notes)
            <div style="margin-top: 20px; font-size: 11px; color: #666;">
                <strong>Catatan:</strong><br>
                {{ $payroll->notes }}
            </div>
        @endif

        <div class="footer">
            <table class="footer-table">
                <tr>
                    <td>
                        Dicetak pada: {{ now()->translatedFormat('d F Y H:i') }}
                    </td>
                    <td>
                        {{ $schoolProfile->district ?? 'Bandung' }}, {{ now()->translatedFormat('d F Y') }}<br>
                        Bendahara Sekolah,
                        <div class="signature-space"></div>
                        ( __________________________ )
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
