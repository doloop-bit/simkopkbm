@props([
    'title' => null,
])

@php
    $user = auth()->user();
    $isGuru = $user->isGuru();
    
    // Determine dashboard route
    $dashboardRoute = $isGuru ? route('teacher.dashboard') : route('dashboard');
    
    // Determine sub-nav visibility
    $hasAdminSubNav = request()->routeIs(
        'admin.school-profile.*', 
        'admin.news.*', 
        'admin.gallery.*', 
        'admin.programs.*', 
        'admin.contact-inquiries.*', 
        'admin.report-card.*', 
        'admin.assessments.attendance', 
        'admin.assessments.extracurricular',
        'admin.report-card.diniyah-grading',
        'admin.report-card.diniyah',
        'financial.*'
    );
    
    $hasTeacherSubNav = request()->routeIs(
        'teacher.report-cards', 
        'teacher.assessments.grading', 
        'teacher.assessments.diniyah',
        'teacher.assessments.attendance', 
        'teacher.assessments.extracurricular'
    );
    
    $hasSubNav = $isGuru ? $hasTeacherSubNav : $hasAdminSubNav;
@endphp

<x-layouts.dashboard
    :title="$title"
    :dashboard-route="$dashboardRoute"
    :has-sub-nav="$hasSubNav"
>
    {{-- Unified Sub Navigation --}}
    @if ($hasSubNav)
        <x-slot:subNav>
            @if($isGuru)
                <x-layouts.report-card-nav />
            @else
                <x-admin.konten-web-nav />
                <x-layouts.report-card-nav />
                <x-admin.keuangan-nav />
            @endif
        </x-slot:subNav>
    @endif

    {{-- Unified Bottom Navigation (Visible on Mobile) --}}
    <x-slot:bottomNav>
        <x-layouts.bottom-nav />
    </x-slot:bottomNav>

    {{ $slot }}
</x-layouts.dashboard>
