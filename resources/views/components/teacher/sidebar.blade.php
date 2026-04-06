@props([
    'onlyMenu' => false,
])

@if(!$onlyMenu)
    <div class="px-5 py-6 overflow-hidden">
        <a href="{{ route('teacher.dashboard') }}" class="flex items-center gap-3 no-underline min-w-0" wire:navigate>
            <div class="shrink-0 flex items-center justify-center">
                <x-global.app-logo-icon class="size-8 fill-primary block aspect-square object-contain" />
            </div>
            <span class="text-xl font-extrabold text-slate-100 whitespace-nowrap overflow-hidden tracking-tight">{{ config('app.name') }}</span>
        </a>
    </div>
@endif

<x-ui.menu activate-by-route>
    <x-ui.menu-item
        title="Dashboard"
        icon="o-home"
        link="{{ route('teacher.dashboard') }}"
    />
    
    <x-ui.menu-sub title="Data Master" icon="o-book-open" :active="request()->routeIs('teacher.profile') || request()->routeIs('teacher.students.*')">
        <x-ui.menu-item title="Profil Saya" icon="o-identification" link="{{ route('teacher.profile') }}" />
        <x-ui.menu-item title="Siswa" icon="o-users" link="{{ route('teacher.students.index') }}" />
    </x-ui.menu-sub>

    <x-ui.menu-sub title="Akademik" icon="o-academic-cap" :active="request()->routeIs('teacher.academic.*') || request()->routeIs('teacher.attendance.*')">
        <x-ui.menu-item title="Mata Pelajaran" icon="o-book-open" link="{{ route('teacher.academic.subjects') }}" />
        <x-ui.menu-item title="Presensi Harian" icon="o-check-badge" link="{{ route('teacher.attendance.daily') }}" />
        <x-ui.menu-item title="Ekstrakurikuler" icon="o-trophy" link="{{ route('teacher.academic.extracurriculars') }}" />
    </x-ui.menu-sub>

    <x-ui.menu-sub title="Penilaian & Raport" icon="o-pencil-square" :active="request()->routeIs('teacher.assessments.*') || request()->routeIs('teacher.report-cards')">
        <x-ui.menu-item title="Rekap Absensi" icon="o-clipboard-document-list" link="{{ route('teacher.assessments.attendance') }}" />
        <x-ui.menu-item title="Nilai Ekskul" icon="o-star" link="{{ route('teacher.assessments.extracurricular') }}" />
        <x-ui.menu-item title="Raport Kesetaraan" icon="o-document-chart-bar" link="{{ route('teacher.assessments.grading') }}" />

        @if(auth()->user()->teachesPaudLevel())
            <x-ui.menu-item title="Nilai PAUD" icon="o-face-smile" link="{{ route('teacher.assessments.paud') }}" />
        @endif
    </x-ui.menu-sub>
</x-ui.menu>