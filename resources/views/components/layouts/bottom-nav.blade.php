@php
    $user = auth()->user();
    $isGuru = $user->isGuru();
    $isAdmin = $user->isAdmin();
    $isTreasurer = $user->isTreasurer();
    $isHeadmaster = $user->isHeadmaster();
    $isYayasan = $user->isYayasan();

    // Specific sub-nav visibility check (don't show bottom nav if page-specific sub-nav is present)
    $hasReportCardNav = $isGuru 
        ? request()->routeIs('teacher.report-cards') || request()->routeIs('teacher.assessments.*')
        : request()->routeIs('admin.report-card.*') || request()->routeIs('admin.assessments.*');
    
    $hasFinancialNav = request()->routeIs('financial.*');
    $hasWebContentNav = request()->routeIs('admin.school-profile.*', 'admin.news.*', 'admin.gallery.*', 'admin.programs.*', 'admin.contact-inquiries.*');

    // If any specific mobile nav is showing, don't show the main bottom nav
    if ($hasReportCardNav || $hasFinancialNav || $hasWebContentNav) {
        return;
    }

    $items = [];

    if ($isGuru) {
        $items = [
            ['label' => 'Home', 'icon' => 'o-home', 'route' => 'teacher.dashboard'],
            ['label' => 'Absensi', 'icon' => 'o-check-badge', 'route' => 'teacher.attendance.daily'],
            ['label' => 'Periodik', 'icon' => 'o-chart-bar', 'route' => 'teacher.students.index'],
            ['label' => 'Nilai', 'icon' => 'o-document-chart-bar', 'route' => 'teacher.assessments.grading'],
            ['label' => 'Profil', 'icon' => 'o-user', 'route' => 'teacher.profile'],
        ];
    } elseif ($isTreasurer) {
        $items = [
            ['label' => 'Home', 'icon' => 'o-home', 'route' => 'dashboard'],
            ['label' => 'Transaksi', 'icon' => 'o-wallet', 'route' => 'financial.transactions'],
            ['label' => 'RAB', 'icon' => 'o-document-currency-dollar', 'route' => 'financial.budget-plans'],
            ['label' => 'Laporan', 'icon' => 'o-presentation-chart-bar', 'route' => 'reports'],
            ['label' => 'Profil', 'icon' => 'o-user-circle', 'route' => 'appearance.edit'],
        ];
    } elseif ($isYayasan) {
        $items = [
            ['label' => 'Home', 'icon' => 'o-home', 'route' => 'dashboard'],
            ['label' => 'Siswa', 'icon' => 'o-users', 'route' => 'students.index'],
            ['label' => 'Keuangan', 'icon' => 'o-banknotes', 'route' => 'financial.transactions'],
            ['label' => 'Laporan', 'icon' => 'o-presentation-chart-bar', 'route' => 'reports'],
            ['label' => 'Profil', 'icon' => 'o-user-circle', 'route' => 'appearance.edit'],
        ];
    } else {
        // Admin & Kepsek - Group Navigation
        $items = [
            ['label' => 'Home', 'icon' => 'o-home', 'route' => 'dashboard'],
            ['label' => 'Master', 'icon' => 'o-book-open', 'route' => 'students.index', 'active_pattern' => 'students.*|ptk.*|users.*|admin.registrations.*'],
            ['label' => 'Akademik', 'icon' => 'o-academic-cap', 'route' => 'academic.classrooms', 'active_pattern' => 'academic.*'],
            ['label' => 'Keuangan', 'icon' => 'o-banknotes', 'route' => 'financial.transactions', 'active_pattern' => 'financial.*'],
            ['label' => 'Profil', 'icon' => 'o-user-circle', 'route' => 'appearance.edit'],
        ];
    }
@endphp

<div class="fixed bottom-0 left-0 right-0 z-40 bg-white/95 border-t border-slate-200 shadow-[0_-2px_15px_rgba(0,0,0,0.06)] lg:hidden dark:bg-slate-900/95 dark:border-slate-800 backdrop-blur-md safe-area-inset-bottom">
    <div class="flex items-center justify-around h-16">
        @foreach($items as $item)
            @php
                $isActive = isset($item['active_pattern']) 
                    ? request()->routeIs(explode('|', $item['active_pattern'])) 
                    : request()->routeIs($item['route']);
            @endphp
            <a href="{{ route($item['route']) }}" wire:navigate 
               class="flex flex-col items-center justify-center w-full h-full transition-all duration-200 {{ $isActive ? 'text-primary' : 'text-slate-400' }}">
                <x-ui.icon name="{{ $item['icon'] }}" class="size-6 mb-1 {{ $isActive ? 'scale-110' : '' }}" />
                <span class="text-[10px] font-bold uppercase tracking-tighter">{{ __($item['label']) }}</span>
            </a>
        @endforeach
    </div>
</div>

<style>
    .safe-area-inset-bottom {
        padding-bottom: env(safe-area-inset-bottom, 0);
    }
</style>
