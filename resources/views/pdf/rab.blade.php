<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rencana Anggaran Biaya (RAB)</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .details { margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .total { font-weight: bold; text-align: right; }
        .footer { margin-top: 30px; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>RENCANA ANGGARAN BIAYA (RAB)</h1>
        <h3>{{ $plan->level->name }} - {{ $plan->academicYear->name }}</h3>
    </div>

    <div class="details">
        <p><strong>Judul:</strong> {{ $plan->title }}</p>
        <p><strong>Diajukan Oleh:</strong> {{ $plan->submitter->name }}</p>
        <p><strong>Tanggal Pengajuan:</strong> {{ $plan->created_at->format('d F Y') }}</p>
        <p><strong>Status:</strong> {{ ucfirst($plan->status) }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Kategori</th>
                <th>Item</th>
                <th>Qty</th>
                <th>Satuan</th>
                <th>Harga Satuan</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($plan->items as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item->standardItem->category->name ?? '-' }}</td>
                <td>{{ $item->name }}</td>
                <td style="text-align: center;">{{ $item->quantity }}</td>
                <td style="text-align: center;">{{ $item->unit }}</td>
                <td style="text-align: right;">Rp {{ number_format($item->amount, 0, ',', '.') }}</td>
                <td style="text-align: right;">Rp {{ number_format($item->total, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6" class="total">Total Anggaran</td>
                <td class="total">Rp {{ number_format($plan->total_amount, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="details">
        <strong>Catatan:</strong>
        <p>{{ $plan->notes ?? '-' }}</p>
    </div>

    <div class="footer">
        <p>Mengetahui,</p>
        <br><br><br>
        <p>( {{ $plan->submitter->name }} )</p>
    </div>
</body>
</html>
