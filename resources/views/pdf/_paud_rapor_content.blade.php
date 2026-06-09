{{-- resources/views/pdf/_paud_rapor_content.blade.php --}}
@php
    use App\Models\PaudCpElement;
    use App\Models\PaudSklItem;
    use App\Models\PaudTp;

    $r = $reportCard;
    $s = $student;
    $sp = $studentProfile;
    $c = $classroom;
    $ay = $academicYear;

    $displayMode = $r->display_mode ?? 'cp';
    $cpSummaries = $r->cp_summaries ?? [];
    $attendance = $r->attendance ?? ['sick' => 0, 'permission' => 0, 'absent' => 0];
    $physicalData = $r->physical_data ?? ['weight' => null, 'height' => null];

    // Fetch TPs with assessments for this student
    $tps = PaudTp::where([
        'classroom_id' => $c->id,
        'academic_year_id' => $ay->id,
        'semester' => $r->semester,
    ])->with([
        'cpElement',
        'sklItem',
        'assessments' => fn($q) => $q->where('student_id', $s->id)
    ])->orderBy('order')->get();

    $cpElements = PaudCpElement::orderBy('order')->get();
    $sklItems = PaudSklItem::orderBy('order')->get();

    $levelColors = [
        'BSB' => '#1d4ed8', // blue
        'BSH' => '#15803d', // green
        'MB'  => '#d97706', // amber
        'BB'  => '#dc2626', // red
    ];

    $levelLabels = [
        'BSB' => 'BSB',
        'BSH' => 'BSH',
        'MB'  => 'MB',
        'BB'  => 'BB',
    ];
@endphp

<div>
    {{-- Title --}}
    <div style="text-align:center; margin-bottom:15px;">
        <h2 style="font-size:14px; font-weight:bold; text-transform:uppercase; margin:0; text-decoration:underline;">LAPORAN HASIL BELAJAR PESERTA DIDIK</h2>
        <p style="font-size:11px; margin:4px 0 0;">PENDIDIKAN ANAK USIA DINI (PAUD)</p>
    </div>

    {{-- Student info --}}
    <table style="width:100%; border-collapse:collapse; margin-bottom:15px; font-size:11px;">
        <tr>
            <td style="width:50%; vertical-align:top; padding-right:15px;">
                <table style="width:100%;">
                    <tr>
                        <td style="width:110px; padding:2px 0;">Nama Anak</td>
                        <td style="width:10px;">:</td>
                        <td style="font-weight:bold;">{{ $s->name }}</td>
                    </tr>
                    <tr>
                        <td style="padding:2px 0;">NISN</td>
                        <td>:</td>
                        <td>{{ $sp->nisn ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td style="padding:2px 0;">Tempat, Tgl Lahir</td>
                        <td>:</td>
                        <td>{{ $sp->birth_place ?? '-' }}, {{ $sp->date_of_birth ? \Carbon\Carbon::parse($sp->date_of_birth)->translatedFormat('d F Y') : '-' }}</td>
                    </tr>
                </table>
            </td>
            <td style="width:50%; vertical-align:top;">
                <table style="width:100%;">
                    <tr>
                        <td style="width:110px; padding:2px 0;">Kelompok</td>
                        <td style="width:10px;">:</td>
                        <td>{{ $c->name }}</td>
                    </tr>
                    <tr>
                        <td style="padding:2px 0;">Semester</td>
                        <td>:</td>
                        <td>{{ $r->semester }} ({{ $r->semester == 1 ? 'Ganjil' : 'Genap' }})</td>
                    </tr>
                    <tr>
                        <td style="padding:2px 0;">Tahun Pelajaran</td>
                        <td>:</td>
                        <td>{{ $ay->name }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Capaian Pembelajaran --}}
    <div style="background:#f3f4f6; padding:5px 10px; font-weight:bold; margin:15px 0 10px; border-left:4px solid #1f2937; font-size:12px;">
        A. CAPAIAN PEMBELAJARAN
    </div>

    @if ($displayMode === 'cp')
        {{-- MODE CP: grouped by CP Element --}}
        @foreach ($cpElements as $cpIndex => $cp)
            @php
                $cpTps = $tps->where('paud_cp_element_id', $cp->id)->values();
            @endphp

            <div style="font-weight:bold; font-size:11px; margin:10px 0 5px;">
                {{ $cpIndex + 1 }}. {{ $cp->name }}
            </div>

            @if ($cpTps->isNotEmpty())
                <table style="width:100%; border-collapse:collapse; font-size:10px; margin-bottom:10px;">
                    <thead>
                        <tr>
                            <th style="border:1px solid #000; padding:5px; background:#f9fafb; width:5%;">No</th>
                            <th style="border:1px solid #000; padding:5px; background:#f9fafb;">Tujuan Pembelajaran</th>
                            <th style="border:1px solid #000; padding:5px; background:#f9fafb; width:12%; text-align:center;">Capaian</th>
                            <th style="border:1px solid #000; padding:5px; background:#f9fafb; width:35%;">Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($cpTps as $i => $tp)
                            @php
                                $assessment = $tp->assessments->first();
                                $level = $assessment?->level;
                                $levelColor = $level ? ($levelColors[$level] ?? '#666') : '#999';
                            @endphp
                            <tr>
                                <td style="border:1px solid #000; padding:5px; text-align:center;">{{ $i + 1 }}</td>
                                <td style="border:1px solid #000; padding:5px;">{{ $tp->description }}</td>
                                <td style="border:1px solid #000; padding:5px; text-align:center;">
                                    @if ($level)
                                        <span style="display:inline-block; padding:2px 6px; border-radius:4px; background:{{ $levelColor }}; color:#fff; font-weight:bold; font-size:10px;">
                                            {{ $level }}
                                        </span>
                                    @else
                                        <span style="color:#999; font-style:italic;">-</span>
                                    @endif
                                </td>
                                <td style="border:1px solid #000; padding:5px; font-style:italic; font-size:10px;">
                                    {{ $assessment?->notes ?? '-' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                @if (!empty($cpSummaries[$cp->id]))
                    <div style="margin-bottom:12px; padding:6px 10px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:4px; font-size:10px; font-style:italic;">
                        <strong>Rangkuman:</strong> {{ $cpSummaries[$cp->id] }}
                    </div>
                @endif
            @else
                <div style="margin-bottom:10px; color:#999; font-style:italic; font-size:10px; padding:5px 10px;">
                    Belum ada Tujuan Pembelajaran yang dinilai.
                </div>
            @endif
        @endforeach

    @else
        {{-- MODE SKL: grouped by SKL item --}}
        @foreach ($sklItems as $sklIndex => $skl)
            @php
                $sklTps = $tps->where('paud_skl_item_id', $skl->id)->values();
            @endphp
            @if ($sklTps->isNotEmpty())
                <div style="font-weight:bold; font-size:11px; margin:10px 0 5px;">
                    {{ $sklIndex + 1 }}. {{ $skl->name }}
                </div>

                <table style="width:100%; border-collapse:collapse; font-size:10px; margin-bottom:10px;">
                    <thead>
                        <tr>
                            <th style="border:1px solid #000; padding:5px; background:#f9fafb; width:5%;">No</th>
                            <th style="border:1px solid #000; padding:5px; background:#f9fafb;">Tujuan Pembelajaran</th>
                            <th style="border:1px solid #000; padding:5px; background:#f9fafb; width:12%; text-align:center;">Capaian</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sklTps as $i => $tp)
                            @php
                                $assessment = $tp->assessments->first();
                                $level = $assessment?->level;
                                $levelColor = $level ? ($levelColors[$level] ?? '#666') : '#999';
                            @endphp
                            <tr>
                                <td style="border:1px solid #000; padding:5px; text-align:center;">{{ $i + 1 }}</td>
                                <td style="border:1px solid #000; padding:5px;">{{ $tp->description }}</td>
                                <td style="border:1px solid #000; padding:5px; text-align:center;">
                                    @if ($level)
                                        <span style="display:inline-block; padding:2px 6px; border-radius:4px; background:{{ $levelColor }}; color:#fff; font-weight:bold; font-size:10px;">
                                            {{ $level }}
                                        </span>
                                    @else
                                        <span style="color:#999; font-style:italic;">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        @endforeach
    @endif

    {{-- Catatan Guru --}}
    <div style="background:#f3f4f6; padding:5px 10px; font-weight:bold; margin:15px 0 10px; border-left:4px solid #1f2937; font-size:12px;">
        B. CATATAN GURU
    </div>
    <div style="border:1px solid #ccc; padding:10px; min-height:60px; font-size:11px; font-style:italic; text-align:justify; margin-bottom:15px;">
        {{ $r->teacher_notes ?? '-' }}
    </div>

    {{-- Refleksi Orang Tua --}}
    <div style="background:#f3f4f6; padding:5px 10px; font-weight:bold; margin:15px 0 10px; border-left:4px solid #1f2937; font-size:12px;">
        C. REFLEKSI ORANG TUA
    </div>
    <div style="border:1px solid #ccc; padding:10px; min-height:60px; font-size:11px; font-style:italic; text-align:justify; margin-bottom:15px;">
        {{ $r->parent_reflection ?? '' }}
    </div>

    {{-- Kehadiran & Pertumbuhan --}}
    <table style="width:100%; border-collapse:collapse; margin:15px 0;">
        <tr>
            <td style="width:48%; vertical-align:top; padding-right:15px;">
                <div style="background:#f3f4f6; padding:5px 10px; font-weight:bold; margin-bottom:10px; border-left:4px solid #1f2937; font-size:12px;">D. KETIDAKHADIRAN</div>
                <table style="width:100%; border-collapse:collapse; font-size:11px;">
                    <tr>
                        <td style="border:1px solid #000; padding:5px;">Sakit</td>
                        <td style="border:1px solid #000; padding:5px; text-align:center; width:60px;">{{ $attendance['sick'] ?? 0 }} hari</td>
                    </tr>
                    <tr>
                        <td style="border:1px solid #000; padding:5px;">Izin</td>
                        <td style="border:1px solid #000; padding:5px; text-align:center;">{{ $attendance['permission'] ?? 0 }} hari</td>
                    </tr>
                    <tr>
                        <td style="border:1px solid #000; padding:5px;">Tanpa Keterangan</td>
                        <td style="border:1px solid #000; padding:5px; text-align:center;">{{ $attendance['absent'] ?? 0 }} hari</td>
                    </tr>
                </table>
            </td>
            <td style="width:52%; vertical-align:top;">
                <div style="background:#f3f4f6; padding:5px 10px; font-weight:bold; margin-bottom:10px; border-left:4px solid #1f2937; font-size:12px;">E. DATA PERTUMBUHAN</div>
                <table style="width:100%; border-collapse:collapse; font-size:11px;">
                    <tr>
                        <td style="border:1px solid #000; padding:5px;">Berat Badan (BB)</td>
                        <td style="border:1px solid #000; padding:5px; text-align:center; width:80px;">{{ $physicalData['weight'] ? $physicalData['weight'] . ' kg' : '-' }}</td>
                    </tr>
                    <tr>
                        <td style="border:1px solid #000; padding:5px;">Tinggi Badan (TB)</td>
                        <td style="border:1px solid #000; padding:5px; text-align:center;">{{ $physicalData['height'] ? $physicalData['height'] . ' cm' : '-' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</div>
