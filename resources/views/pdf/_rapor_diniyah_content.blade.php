@php
    $numericGrades = collect($reportCard->scores)->filter(fn($score) => isset($score['knowledge_grade']));
    $targetGrades = collect($reportCard->scores)->filter(fn($score) => !isset($score['knowledge_grade']));
@endphp

<div class="label">A. NILAI AKADEMIK</div>
<table class="data-table">
    <thead>
        <tr>
            <th width="5%" rowspan="2">No</th>
            <th width="25%" rowspan="2">Mata Pelajaran</th>
            <th width="10%" rowspan="2">KKM</th>
            <th width="20%" colspan="2">Pengetahuan</th>
            <th width="20%" colspan="2">Praktek</th>
            <th width="10%" rowspan="2">Sikap</th>
        </tr>
        <tr>
            <th width="7%">Nilai</th>
            <th width="13%">Huruf</th>
            <th width="7%">Nilai</th>
            <th width="13%">Huruf</th>
        </tr>
    </thead>
    <tbody>
        @foreach($numericGrades as $index => $grade)
        <tr>
            <td class="text-center">{{ $loop->iteration }}</td>
            <td>{{ $grade['subject_name'] }}</td>
            <td class="text-center">{{ $grade['kkm'] ?? '-' }}</td>
            <td class="text-center">{{ $grade['knowledge_grade'] }}</td>
            <td class="text-center" style="font-size: 8pt;">{{ $terbilang($grade['knowledge_grade'] ?? 0) }}</td>
            <td class="text-center">{{ $grade['has_practice'] ? $grade['practice_grade'] : '-' }}</td>
            <td class="text-center" style="font-size: 8pt;">{{ ($grade['has_practice'] ?? false) ? $terbilang($grade['practice_grade'] ?? 0) : '-' }}</td>
            <td class="text-center font-bold">{{ $grade['attitude_grade'] }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

@if($targetGrades->count() > 0)
<div class="label">B. MUATAN LOKAL (TARGET CAPAIAN)</div>
<table class="data-table">
    <thead>
        <tr>
            <th width="5%">No</th>
            <th width="20%">Mata Pelajaran</th>
            <th width="10%">KKM</th>
            <th width="10%">Angka</th>
            <th width="20%">Target</th>
            <th width="20%">Capaian</th>
            <th width="15%">Ketuntasan</th>
        </tr>
    </thead>
    <tbody>
        @foreach($targetGrades as $index => $grade)
        <tr>
            <td class="text-center">{{ $loop->iteration }}</td>
            <td>{{ $grade['subject_name'] }}</td>
            <td class="text-center">{{ $grade['kkm'] ?? '-' }}</td>
            <td class="text-center font-bold">{{ $grade['grade'] }}</td>
            <td>{{ $grade['target'] }}</td>
            <td>{{ $grade['achievement'] }}</td>
            <td class="text-center {{ $grade['target_status'] === 'Tercapai' ? 'font-bold' : '' }}">
                {{ $grade['target_status'] ?? '-' }}
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

<div class="label">C. KEHADIRAN</div>
<table class="attendance-table">
    <tr>
        <th width="70%">Keterangan</th>
        <th width="30%">Jumlah (Hari)</th>
    </tr>
    <tr>
        <td>Sakit</td>
        <td class="text-center">{{ $attendance->sick ?? 0 }}</td>
    </tr>
    <tr>
        <td>Izin</td>
        <td class="text-center">{{ $attendance->permission ?? 0 }}</td>
    </tr>
    <tr>
        <td>Tanpa Keterangan (Alfa)</td>
        <td class="text-center">{{ $attendance->absent ?? 0 }}</td>
    </tr>
</table>

<div class="label">CATATAN WALI KELAS:</div>
<div class="notes-box italic">
    {{ $reportCard->teacher_notes ?: '-' }}
</div>
