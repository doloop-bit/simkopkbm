<flux:sidebar sticky collapsible class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
    <flux:sidebar.header>
        <flux:sidebar.brand :name="config('app.name')" href="{{ route('teacher.dashboard') }}" wire:navigate.hover>
            <x-slot name="logo"
                class="flex aspect-square size-10 items-center justify-center rounded-lg bg-accent-content text-accent-foreground">
                <x-global.app-logo-icon class="size-7 fill-current text-white dark:text-black" />
            </x-slot>
        </flux:sidebar.brand>
        <flux:sidebar.collapse
            class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
    </flux:sidebar.header>

    <flux:sidebar.nav>
        {{-- Dashboard --}}
        <flux:sidebar.item icon="home" :href="route('teacher.dashboard')"
            :current="request()->routeIs('teacher.dashboard')" wire:navigate.hover>
            {{ __('Dashboard') }}
        </flux:sidebar.item>


        {{-- Penilaian & Raport --}}
        <flux:sidebar.group expandable icon="pencil-square" :heading="__('Penilaian & Raport')" class="grid">
            <flux:sidebar.item icon="document-chart-bar" :href="route('teacher.report-cards')"
                :current="request()->routeIs('teacher.academic.grades') || request()->routeIs('teacher.assessments.attendance') || request()->routeIs('teacher.assessments.extracurricular') || request()->routeIs('teacher.report-cards')"
                wire:navigate.hover>
                {{ __('Raport Kesetaraan') }}
            </flux:sidebar.item>

            @if(auth()->user()->teachesPaudLevel())
                <flux:sidebar.item icon="face-smile" :href="route('teacher.assessments.paud')"
                    :current="request()->routeIs('teacher.assessments.paud')" wire:navigate.hover>
                    {{ __('Nilai PAUD') }}
                </flux:sidebar.item>
            @endif
        </flux:sidebar.group>
    </flux:sidebar.nav>

    <flux:spacer />

    <x-admin.desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
</flux:sidebar>