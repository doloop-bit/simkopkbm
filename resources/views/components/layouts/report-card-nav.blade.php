@php
    $isGuru = auth()->user()->isGuru();
    $prefix = $isGuru ? 'teacher.assessments' : 'admin.assessments';
    $reportRoute = $isGuru ? 'teacher.report-cards' : 'admin.report-card.create';
    $gradingRoute = $isGuru ? 'teacher.assessments.grading' : 'admin.report-card.grading';

    $isVisible = $isGuru 
        ? request()->routeIs('teacher.report-cards') || request()->routeIs('teacher.assessments.*') 
        : request()->routeIs('admin.report-card.*') || request()->routeIs('admin.assessments.*');

    $tabs = $isVisible ? [
        'grading' => [
            'label' => 'Input Nilai & TP',
            'label_short' => 'Nilai',
            'icon' => 'o-clipboard-document-list',
            'route' => $gradingRoute,
        ],
        'attendance' => [
            'label' => 'Input Kehadiran',
            'label_short' => 'Hadir',
            'icon' => 'o-calendar-days',
            'route' => $prefix . '.attendance',
        ],
        'extracurricular' => [
            'label' => 'Input Ekskul',
            'label_short' => 'Ekskul',
            'icon' => 'o-trophy',
            'route' => $prefix . '.extracurricular',
        ],
        'diniyah_grading' => [
            'label' => 'Nilai Diniyah',
            'label_short' => 'Diniyah',
            'icon' => 'o-sparkles',
            'route' => $isGuru ? 'teacher.assessments.diniyah' : 'admin.report-card.diniyah-grading',
        ],
        'diniyah_report' => [
            'label' => 'Rapor Diniyah',
            'label_short' => 'R. Diniyah',
            'icon' => 'o-book-open',
            'route' => $isGuru ? 'teacher.report-cards' : 'admin.report-card.diniyah', // Teacher handles both in index? No, actually teacher link is to report-cards index which should show both.
        ],
        'report_card' => [
            'label' => 'Buat Rapor',
            'label_short' => 'Rapor',
            'icon' => 'o-document-text',
            'route' => $reportRoute,
        ],
    ] : [];



    // Teacher-only home back button if needed, or keep it consistent
    if ($isGuru && !empty($tabs)) {
        $tabs['home'] = [
            'label' => 'Dashboard',
            'label_short' => 'Home',
            'icon' => 'o-home',
            'route' => 'teacher.dashboard',
        ];
    }
@endphp

<x-ui.sub-nav :tabs="$tabs" />
