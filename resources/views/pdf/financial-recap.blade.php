<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekapitulasi Keuangan</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        .title {
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 0;
        }
        .subtitle {
            font-size: 12px;
            margin-top: 5px;
        }
        .info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 6px;
        }
        th {
            background-color: #f5f5f5;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .nowrap {
            white-space: nowrap;
        }
        .total-row {
            font-weight: bold;
            background-color: #f9f9f9;
        }
        .summary {
            width: 300px;
            float: right;
            border: 1px solid #000;
            padding: 10px;
        }
        .summary table {
            border: none;
            margin: 0;
        }
        .summary td {
            border: none;
            padding: 3px;
        }
        .footer {
            clear: both;
            margin-top: 30px;
            text-align: right;
        }
        .signature {
            margin-top: 50px;
            display: inline-block;
            text-align: center;
            width: 200px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div style="font-size: 18px; font-weight: bold; margin-bottom: 5px;">{{ $schoolName }}</div>
        <h1 class="title">Rekapitulasi Keuangan ({{ strtoupper($tab === 'bku' ? 'Buku Kas Umum' : ($tab === 'bank' ? 'Buku Bank' : 'Buku Tunai')) }})</h1>
        <div class="subtitle">
            Jenjang: <strong>{{ $levelName }}</strong> | 
            Bulan: <strong>{{ $monthName }} {{ $year }}</strong>
        </div>
    </div>

    <div class="info">
        <div style="float:left;">
            <strong>Dicetak pada:</strong> {{ now()->format('d/m/Y H:i') }}
        </div>
        <div style="clear:both;"></div>
    </div>

    <table>
        <thead>
            <tr>
                <th width="3%">No</th>
                <th width="10%">Tanggal</th>
                <th width="35%">Keterangan / Uraian</th>
                <th width="12%">Ref/Bukti</th>
                <th width="13%">Penerimaan</th>
                <th width="13%">Pengeluaran</th>
                <th width="14%">Saldo</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-center">-</td>
                <td class="text-center">{{ \Carbon\Carbon::createFromDate($year, $month, 1)->format('d/m/Y') }}</td>
                <td><strong>Saldo Pindahan</strong></td>
                <td></td>
                <td></td>
                <td></td>
                <td class="text-right"><strong>{{ number_format($startBalance, 0, ',', '.') }}</strong></td>
            </tr>

            @php 
                $runningBalance = $startBalance; 
                $totalIn = 0;
                $totalOut = 0;
            @endphp
            
            @forelse($transactions as $index => $tx)
                @php
                    if ($tx->type === 'income') {
                        $runningBalance += $tx->amount;
                        $totalIn += $tx->amount;
                    } else {
                        $runningBalance -= $tx->amount;
                        $totalOut += $tx->amount;
                    }
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center nowrap">{{ $tx->payment_date->format('d/m/Y') }}</td>
                    <td>
                        @if($tx->type === 'income')
                            {{ $tx->billing?->feeCategory?->name ?? 'Pemasukan' }} - {{ $tx->billing?->student?->name ?? 'Siswa' }}
                        @else
                            {{ $tx->budgetItem?->name ?? 'Pengeluaran' }} 
                        @endif
                        @if($tx->notes)
                            <br><small><i>{{ $tx->notes }}</i></small>
                        @endif
                    </td>
                    <td class="text-center">{{ $tx->reference_number ?: '-' }}</td>
                    <td class="text-right">{{ $tx->type === 'income' ? number_format($tx->amount, 0, ',', '.') : '-' }}</td>
                    <td class="text-right">{{ $tx->type === 'expense' ? number_format($tx->amount, 0, ',', '.') : '-' }}</td>
                    <td class="text-right">{{ number_format($runningBalance, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">Tidak ada transaksi pada bulan ini.</td>
                </tr>
            @endforelse
            
            <tr class="total-row">
                <td colspan="4" class="text-right">TOTAL MUTASI BULAN INI</td>
                <td class="text-right">{{ number_format($totalIn, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($totalOut, 0, ',', '.') }}</td>
                <td></td>
            </tr>
            <tr class="total-row">
                <td colspan="6" class="text-right">SALDO AKHIR</td>
                <td class="text-right">{{ number_format($runningBalance, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <div class="signature">
            Mengetahui,<br>
            <strong>Bendahara</strong>
            <br><br><br><br>
            (_______________________)
        </div>
    </div>
</body>
</html>
