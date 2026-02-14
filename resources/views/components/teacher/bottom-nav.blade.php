@if(!request()->routeIs('teacher.report-cards') && !request()->routeIs('teacher.assessments.*'))
<div class="fixed bottom-0 left-0 right-0 z-50 bg-white border-t border-zinc-200 shadow-[0_-2px_10px_rgba(0,0,0,0.05)] md:hidden dark:bg-zinc-900 dark:border-zinc-800">
    <div class="flex items-center justify-around h-16">
        {{-- Dashboard --}}
        <a href="{{ route('teacher.dashboard') }}" wire:navigate class="flex flex-col items-center justify-center w-full h-full text-zinc-500 hover:text-blue-600 dark:text-zinc-400 dark:hover:text-blue-400 {{ request()->routeIs('teacher.dashboard') ? 'text-blue-600 dark:text-blue-400' : '' }}">
            <flux:icon icon="home" class="w-6 h-6 mb-1" />
            <span class="text-[10px] font-medium leading-none">{{ __('Dashboard') }}</span>
        </a>

        {{-- Presensi Harian --}}
        <a href="{{ route('teacher.attendance.daily') }}" wire:navigate class="flex flex-col items-center justify-center w-full h-full text-zinc-500 hover:text-blue-600 dark:text-zinc-400 dark:hover:text-blue-400 {{ request()->routeIs('teacher.attendance.daily') ? 'text-blue-600 dark:text-blue-400' : '' }}">
            <flux:icon icon="check-badge" class="w-6 h-6 mb-1" />
            <span class="text-[10px] font-medium leading-none">{{ __('Presensi') }}</span>
        </a>

        {{-- Raport (Input Nilai) --}}
        <a href="{{ route('teacher.assessments.grading') }}" wire:navigate class="flex flex-col items-center justify-center w-full h-full text-zinc-500 hover:text-blue-600 dark:text-zinc-400 dark:hover:text-blue-400 {{ request()->routeIs('teacher.assessments.grading') || request()->routeIs('teacher.report-cards') ? 'text-blue-600 dark:text-blue-400' : '' }}">
            <flux:icon icon="document-chart-bar" class="w-6 h-6 mb-1" />
            <span class="text-[10px] font-medium leading-none">{{ __('Rapor') }}</span>
        </a>
    </div>
</div>
@endif
