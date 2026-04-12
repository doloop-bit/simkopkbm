@php
    $r = $reportCard;
    $s = $student;
    $sp = $studentProfile;
    $c = $classroom;
    $ay = $academicYear;
    $t = $teacher;
@endphp

<div>
    <!-- Header -->
    <div style="text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px;">
        <h1 style="font-size: 16px; text-transform: uppercase; margin: 0;">RAPOR HASIL BELAJAR DINIYAH</h1>
    </div>

    <!-- Info Table -->
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 11px;">
        <tr>
            <td style="width: 60%; vertical-align: top; padding-right: 20px;">
                <table style="width: 100%;">
                    <tr><td style="width: 100px; padding: 2px 0;">Nama Murid</td><td style="width: 10px;">:</td><td style="font-weight: bold;">{{ $s->name }}</td></tr>
                    <tr><td style="padding: 2px 0;">NISN</td><td>:</td><td>{{ $sp->nisn ?? '-' }}</td></tr>
                    <tr><td style="padding: 2px 0;">Sekolah</td><td>:</td><td>{{ config('app.name') }}</td></tr>
                </table>
            </td>
            <td style="width: 40%; vertical-align: top;">
                <table style="width: 100%;">
                    <tr><td style="width: 100px; padding: 2px 0;">Kelas</td><td style="width: 10px;">:</td><td>{{ $c->name }}</td></tr>
                    <tr><td style="padding: 2px 0;">Semester</td><td>:</td><td>{{ $r->semester }}</td></tr>
                    <tr><td style="padding: 2px 0;">Tahun Ajaran</td><td>:</td><td>{{ $ay->name ?? $ay->year }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <div style="background: #f3f4f6; padding: 5px 10px; font-weight: bold; margin: 15px 0 10px; border-left: 4px solid #1f2937; font-size: 12px;">A. Nilai Mata Pelajaran Diniyah</div>
    
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 15px; font-size: 10px;" class="data-table">
        <thead>
            <tr>
                <th style="border: 1px solid #000; padding: 5px; background: #e5e7eb; width: 5%;">No</th>
                <th style="border: 1px solid #000; padding: 5px; background: #e5e7eb; width: 25%;">Mata Pelajaran</th>
                <th style="border: 1px solid #000; padding: 5px; background: #e5e7eb; width: 10%;">Pengetahuan</th>
                <th style="border: 1px solid #000; padding: 5px; background: #e5e7eb; width: 10%;">Praktek</th>
                <th style="border: 1px solid #000; padding: 5px; background: #e5e7eb; width: 7%;">Sikap</th>
                <th style="border: 1px solid #000; padding: 5px; background: #e5e7eb; width: 43%;">Keterangan / Uraian Capaian</th>
            </tr>
        </thead>
        <tbody>
            @forelse($r->scores as $index => $score)
                @php
                    $isNumeric = ($score['assessment_type'] ?? 'numeric') === 'numeric';
                @endphp
                <tr>
                    <td style="border: 1px solid #000; padding: 5px; text-align: center;">{{ $index + 1 }}</td>
                    <td style="border: 1px solid #000; padding: 5px;">
                        <strong>{{ $score['subject_name'] }}</strong>
                        @if(!$isNumeric && !empty($score['target']))
                            <br><span style="font-size: 8px; font-style: italic; color: #666;">Target: {{ $score['target'] }}</span>
                        @endif
                    </td>
                    <td style="border: 1px solid #000; padding: 5px; text-align: center;">
                        @if($isNumeric)
                            {{ $score['knowledge_grade'] ?? '-' }}
                        @else
                            {{ $score['grade'] ?? '-' }}
                        @endif
                    </td>
                    <td style="border: 1px solid #000; padding: 5px; text-align: center;">
                        @if($isNumeric)
                            {{ $score['has_practice'] ? ($score['practice_grade'] ?? '-') : 'N/A' }}
                        @else
                            -
                        @endif
                    </td>
                    <td style="border: 1px solid #000; padding: 5px; text-align: center;">
                        {{ $isNumeric ? ($score['attitude_grade'] ?? '-') : '-' }}
                    </td>
                    <td style="border: 1px solid #000; padding: 5px; text-align: justify;">
                        @if(!$isNumeric)
                            @if(!empty($score['achievement']))
                                <strong>Capaian:</strong> {{ $score['achievement'] }}<br>
                            @endif
                            {{ $score['notes'] ?? '-' }}
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" style="border: 1px solid #000; padding: 20px; text-align: center; color: #999;">Tidak ada data nilai diniyah</td></tr>
            @endforelse
        </tbody>
    </table>

    <!-- Notes -->
    <div style="background: #f3f4f6; padding: 5px 10px; font-weight: bold; margin: 20px 0 10px; border-left: 4px solid #1f2937; font-size: 12px;">Catatan Pembimbing / Guru Diniyah</div>
    <div style="border: 1px solid #000; padding: 10px; min-height: 80px; font-size: 11px; text-align: justify;">
        {{ $r->teacher_notes ?? '-' }}
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
                {{ config('app.city', 'Kota') }}, {{ date('d F Y') }}<br>Guru/Pembimbing Diniyah
                <div style="height: 60px;"></div>
                <span style="font-weight: bold; text-decoration: underline;">{{ $t->name ?? '( ..................................... )' }}</span>
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
