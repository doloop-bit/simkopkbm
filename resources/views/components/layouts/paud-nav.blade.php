@php
    $user = auth()->user();
    $isAdmin = $user->isAdmin();
    $isGuru = $user->isGuru();

    $tabs = [];

    if ($isAdmin) {
        $tabs['paud_master'] = [
            'label' => 'Master PAUD',
            'label_short' => 'Master',
            'icon' => 'o-queue-list',
            'route' => 'admin.report-card.paud.master',
        ];
    }

    if ($isAdmin || $user->teachesPaudLevel()) {
        $tabs['paud_tp'] = [
            'label' => 'TP PAUD',
            'label_short' => 'TP',
            'icon' => 'o-list-bullet',
            'route' => 'admin.report-card.paud.tp',
        ];
        $tabs['paud_grading'] = [
            'label' => 'Nilai PAUD',
            'label_short' => 'Nilai',
            'icon' => 'o-pencil-square',
            'route' => 'admin.report-card.paud.grading',
        ];
    }

    if ($isAdmin) {
        $tabs['paud_report'] = [
            'label' => 'Rapor PAUD',
            'label_short' => 'Rapor',
            'icon' => 'o-document-duplicate',
            'route' => 'admin.report-card.paud.generate',
        ];
    }

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
