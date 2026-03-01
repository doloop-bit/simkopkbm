@php
    $isVisible = request()->routeIs('admin.report-card.*')
        || request()->routeIs('admin.assessments.attendance')
        || request()->routeIs('admin.assessments.extracurricular');

    $tabs = $isVisible ? [
        'grading' => [
            'label' => 'Input Nilai & TP',
            'label_short' => 'Nilai',
            'icon' => 'o-clipboard-document-list',
            'route' => 'admin.report-card.grading',
            'route_pattern' => 'admin.report-card.grading',
        ],
        'attendance' => [
            'label' => 'Input Kehadiran',
            'label_short' => 'Hadir',
            'icon' => 'o-calendar-days',
            'route' => 'admin.assessments.attendance',
            'route_pattern' => 'admin.assessments.attendance',
        ],
        'extracurricular' => [
            'label' => 'Input Ekskul',
            'label_short' => 'Ekskul',
            'icon' => 'o-trophy',
            'route' => 'admin.assessments.extracurricular',
            'route_pattern' => 'admin.assessments.extracurricular',
        ],
        'create' => [
            'label' => 'Buat Rapor',
            'label_short' => 'Rapor',
            'icon' => 'o-document-text',
            'route' => 'admin.report-card.create',
            'route_pattern' => 'admin.report-card.create',
        ],
    ] : [];
@endphp

<x-admin.sub-nav :tabs="$tabs" />