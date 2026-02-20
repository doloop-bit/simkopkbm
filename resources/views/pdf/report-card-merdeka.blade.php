<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rapor Merdeka - {{ $student->name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Arial', sans-serif; font-size: 11px; line-height: 1.4; color: #333; padding: 40px; }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { font-size: 16px; text-transform: uppercase; }
        .info-table { width: 100%; margin-bottom: 20px; }
        .info-table td { padding: 3px 0; vertical-align: top; }
        .label { width: 120px; font-weight: bold; }
        .colon { width: 15px; }
        .section-title { background: #f3f4f6; padding: 5px 10px; font-weight: bold; margin: 15px 0 10px; border-left: 4px solid #1f2937; font-size: 12px; }
        table.data-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        table.data-table th, table.data-table td { border: 1px solid #000; padding: 6px 8px; text-align: left; }
        table.data-table th { background: #e5e7eb; font-weight: bold; text-align: center; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .description { font-style: italic; color: #4b5563; margin-top: 4px; display: block; }
        .signature-table { width: 100%; margin-top: 40px; }
        .signature-table td { text-align: center; width: 33%; }
        .sig-space { height: 60px; }
        .sig-name { font-weight: bold; text-decoration: underline; }
        .page-break { page-break-after: always; }
        .level-badge { padding: 2px 4px; border-radius: 3px; font-weight: bold; font-size: 9px; }
        .bb { background: #fee2e2; color: #991b1b; }
        .mb { background: #fef3c7; color: #92400e; }
        .bsh { background: #dbeafe; color: #1e40af; }
        .sb { background: #dcfce7; color: #166534; }
    </style>
</head>
<body>
    @include('pdf._rapor_content', [
        'reportCard' => $reportCard,
        'student' => $student,
        'studentProfile' => $studentProfile,
        'classroom' => $classroom,
        'academicYear' => $academicYear,
        'teacher' => $teacher
    ])

    <div style="position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 8px; color: #999;">
        Dicetak otomatis oleh SIMKOPKBM pada {{ date('Y-m-d H:i:s') }}
    </div>
</body>
</html>
