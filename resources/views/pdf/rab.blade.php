<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>RAB - {{ $plan->title }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 3px double #000;
            padding-bottom: 10px;
            position: relative;
        }
        .header img {
            position: absolute;
            left: 0;
            top: 5px;
            height: 70px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
            text-transform: uppercase;
        }
        .header h2 {
            margin: 5px 0;
            font-size: 14px;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        
        .meta-table {
            width: 100%;
            margin-bottom: 20px;
            border-collapse: collapse;
        }
        .meta-table td {
            padding: 3px;
            vertical-align: top;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .items-table th, .items-table td {
            border: 1px solid #ddd;
            padding: 6px;
        }
        .items-table th {
            background-color: #f2f2f2;
            font-weight: bold;
            text-align: center;
        }
        
        .signatures {
            margin-top: 40px;
            width: 100%;
            page-break-inside: avoid;
        }
        .signature-box {
            width: 40%;
            float: left;
            text-align: center;
        }
        .signature-box.right {
            float: right;
        }
        .signature-space {
            height: 70px;
        }
        
        .footer {
            margin-top: 30px;
            font-size: 9px;
            text-align: right;
            color: #777;
            border-top: 1px solid #eee;
            padding-top: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        @if($profile?->logo_path)
            <img src="{{ public_path('storage/' . $profile->logo_path) }}" alt="Logo">
        @endif
        
        <h1 style="margin-top: 5px;">{{ $profile?->name ?? 'PKBM BAITUSYUKUR LEARNING CENTER' }}</h1>
        <p style="margin:0; font-size:12px;">{{ $profile?->address ?? 'Alamat Sekolah Belum Diatur' }}</p>
        <p style="margin:0; font-size:11px;">
            @if($profile?->phone) Telp: {{ $profile->phone }} @endif
            @if($profile?->email) | Email: {{ $profile->email }} @endif
        </p>
    </div>

    <div class="text-center" style="margin-bottom: 20px;">
        <h3 style="margin: 0; text-decoration: underline; text-transform:uppercase;">Rencana Anggaran Biaya (RAB)</h3>
        <p style="margin: 5px 0; font-size:11px;">Nomor: RAB/{{ $plan->created_at->format('Y') }}/{{ str_pad($plan->id, 4, '0', STR_PAD_LEFT) }}</p>
    </div>

    <table class="meta-table">
        <tr>
            <td width="130"><strong>Judul Kegiatan</strong></td>
            <td width="10">:</td>
            <td>{{ $plan->title }}</td>
        </tr>
        <tr>
            <td><strong>Tahun Ajaran</strong></td>
            <td>:</td>
            <td>{{ $plan->academicYear->name ?? '-' }}</td>
        </tr>
        <tr>
            <td><strong>Jenjang</strong></td>
            <td>:</td>
            <td>{{ $plan->level->name ?? '-' }}</td>
        </tr>
        <tr>
            <td><strong>Diajukan Oleh</strong></td>
            <td>:</td>
            <td>{{ $plan->submitter->name ?? '-' }}</td>
        </tr>
        <tr>
            <td><strong>Tanggal</strong></td>
            <td>:</td>
            <td>{{ $plan->created_at->translatedFormat('d F Y') }}</td>
        </tr>
        <tr>
            <td><strong>Status</strong></td>
            <td>:</td>
            <td><strong>{{ ucfirst($plan->status) }}</strong></td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="35%">Item / Uraian</th>
                <th width="15%">Kategori</th>
                <th width="10%">Qty</th>
                <th width="10%">Satuan</th>
                <th width="15%">Harga</th>
                <th width="15%">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($plan->items as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $item->name }}</td>
                <td>{{ $item->standardItem->category->name ?? '-' }}</td>
                <td class="text-center">{{ $item->quantity }}</td>
                <td class="text-center">{{ $item->unit }}</td>
                <td class="text-right">Rp {{ number_format($item->amount, 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($item->total, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background-color: #f9f9f9; font-weight:bold;">
                <td colspan="6" class="text-right">TOTAL ANGGARAN</td>
                <td class="text-right">Rp {{ number_format($plan->total_amount, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <div style="margin-bottom: 20px;">
        <strong>Catatan Tambahan:</strong><br>
        <div style="border: 1px solid #ddd; padding: 10px; min-height: 50px; background: #fdfdfd;">
            {{ $plan->notes ?: '-' }}
        </div>
    </div>

    <div class="signatures">
        <div class="signature-box">
            <p>Diajukan Oleh,</p>
            <div class="signature-space"></div>
            <p><strong>{{ $plan->submitter->name ?? '....................' }}</strong></p>
            <p>Bendahara / Kepala Sekolah</p>
        </div>

        <div class="signature-box right">
            <p>Ungaran, {{ now()->translatedFormat('d F Y') }}</p>
            <p>Disetujui Oleh,</p>
            <div class="signature-space">
                @if($plan->status === 'approved' && $plan->approver)
                    <div style="color: green; font-weight: bold; border: 2px solid green; padding: 5px; display:inline-block; margin-top:20px; transform: rotate(-5deg);">
                        APPROVED<br>
                        <span style="font-size:10px">{{ $plan->updated_at->format('d/m/Y') }}</span>
                    </div>
                @endif
            </div>
            <p><strong>{{ $plan->approver->name ?? '(Ketua Yayasan)' }}</strong></p>
            <p>Ketua Yayasan</p>
        </div>
        <div style="clear: both;"></div>
    </div>

    <div class="footer">
        Dicetak pada {{ now()->format('d/m/Y H:i') }} | SIMKOPKBM System
    </div>
</body>
</html>
