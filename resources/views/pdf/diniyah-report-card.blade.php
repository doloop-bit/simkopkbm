<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rapor Diniyah - {{ $student->name }}</title>
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
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    @include('pdf._rapor_diniyah_content', [
        'reportCard' => $reportCard,
        'student' => $student,
        'studentProfile' => $studentProfile,
        'classroom' => $classroom,
        'academicYear' => $academicYear,
        'teacher' => $teacher
    ])

    <div style="position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 8px; color: #999;">
        Dicetak otomatis oleh {{ config('app.name') }} pada {{ date('Y-m-d H:i:s') }}
    </div>
</body>
</html>
