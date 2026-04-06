@if(!request()->routeIs('teacher.report-cards') && !request()->routeIs('teacher.assessments.*'))
<div class="fixed bottom-0 left-0 right-0 z-50 bg-white border-t border-slate-200 shadow-[0_-2px_15px_rgba(0,0,0,0.06)] lg:hidden dark:bg-slate-900 dark:border-slate-800 safe-area-inset-bottom">
    <div class="flex items-center justify-around h-16">
        {{-- Dashboard --}}
        <a href="{{ route('teacher.dashboard') }}" wire:navigate class="flex flex-col items-center justify-center w-full h-full transition-colors {{ request()->routeIs('teacher.dashboard') ? 'text-primary' : 'text-slate-400' }}">
            <x-ui.icon name="o-home" class="w-6 h-6 mb-1" />
            <span class="text-[10px] font-bold uppercase tracking-tighter">{{ __('Home') }}</span>
        </a>

        {{-- Presensi Harian --}}
        <a href="{{ route('teacher.attendance.daily') }}" wire:navigate class="flex flex-col items-center justify-center w-full h-full transition-colors {{ request()->routeIs('teacher.attendance.daily') ? 'text-primary' : 'text-slate-400' }}">
            <x-ui.icon name="o-check-badge" class="w-6 h-6 mb-1" />
            <span class="text-[10px] font-bold uppercase tracking-tighter">{{ __('Absensi') }}</span>
        </a>

        {{-- Data Periodik --}}
        <a href="{{ route('teacher.students.index') }}" wire:navigate class="flex flex-col items-center justify-center w-full h-full transition-colors {{ request()->routeIs('teacher.students.index') ? 'text-primary' : 'text-slate-400' }}">
            <x-ui.icon name="o-chart-bar" class="w-6 h-6 mb-1" />
            <span class="text-[10px] font-bold uppercase tracking-tighter">{{ __('Periodik') }}</span>
        </a>

        {{-- Raport (Input Nilai) --}}
        <a href="{{ route('teacher.assessments.grading') }}" wire:navigate class="flex flex-col items-center justify-center w-full h-full transition-colors {{ request()->routeIs('teacher.assessments.grading') || request()->routeIs('teacher.report-cards') || request()->routeIs('teacher.assessments.attendance') ? 'text-primary' : 'text-slate-400' }}">
            <x-ui.icon name="o-document-chart-bar" class="w-6 h-6 mb-1" />
            <span class="text-[10px] font-bold uppercase tracking-tighter">{{ __('Nilai') }}</span>
        </a>

        {{-- Profile --}}
        <a href="{{ route('teacher.profile') }}" wire:navigate class="flex flex-col items-center justify-center w-full h-full transition-colors {{ request()->routeIs('teacher.profile') ? 'text-primary' : 'text-slate-400' }}">
            <x-ui.icon name="o-user" class="w-6 h-6 mb-1" />
            <span class="text-[10px] font-bold uppercase tracking-tighter">{{ __('Profil') }}</span>
        </a>
    </div>
</div>

<style>
    .safe-area-inset-bottom {
        padding-bottom: env(safe-area-inset-bottom, 0);
    }
</style>
@endif

