<flux:sidebar sticky collapsible class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
    <flux:sidebar.header>
        <flux:sidebar.brand :name="config('app.name')" href="{{ route('teacher.dashboard') }}" wire:navigate.hover>
            <x-slot name="logo" class="flex aspect-square size-10 items-center justify-center rounded-lg bg-accent-content text-accent-foreground">
                <x-global.app-logo-icon class="size-7 fill-current text-white dark:text-black" />
            </x-slot>
        </flux:sidebar.brand>
        <flux:sidebar.collapse class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
    </flux:sidebar.header>

    <flux:sidebar.nav>
        {{-- Dashboard --}}
        <flux:sidebar.item icon="home" :href="route('teacher.dashboard')" :current="request()->routeIs('teacher.dashboard')" wire:navigate.hover>
            {{ __('Dashboard') }}
        </flux:sidebar.item>

        {{-- Students Group --}}
        <flux:sidebar.group
            expandable
            icon="users"
            :heading="__('Siswa Saya')"
            class="grid"
        >
            <flux:sidebar.item icon="users" :href="route('teacher.students.index')" :current="request()->routeIs('teacher.students.*')" wire:navigate.hover>
                {{ __('Daftar Siswa') }}
            </flux:sidebar.item>
        </flux:sidebar.group>

        {{-- Assessment & Report Cards Group --}}
        <flux:sidebar.group
            expandable
            icon="pencil-square"
            :heading="__('Penilaian & Raport')"
            class="grid"
        >

            <flux:sidebar.item icon="clipboard-document-list" :href="route('admin.report-card.grading')" :current="request()->routeIs('admin.report-card.grading')" wire:navigate.hover>
                {{ __('Nilai & TP') }}
            </flux:sidebar.item>
            <flux:sidebar.item icon="clipboard-document-check" :href="route('teacher.assessments.competency')" :current="request()->routeIs('teacher.assessments.competency')" wire:navigate.hover>
                {{ __('Kompetensi') }}
            </flux:sidebar.item>
            <flux:sidebar.item icon="clipboard-document-list" :href="route('teacher.assessments.p5')" :current="request()->routeIs('teacher.assessments.p5')" wire:navigate.hover>
                {{ __('P5') }}
            </flux:sidebar.item>
            <flux:sidebar.item icon="trophy" :href="route('teacher.assessments.extracurricular')" :current="request()->routeIs('teacher.assessments.extracurricular')" wire:navigate.hover>
                {{ __('Ekskul') }}
            </flux:sidebar.item>
            @if(auth()->user()->teachesPaudLevel())
                <flux:sidebar.item icon="face-smile" :href="route('teacher.assessments.paud')" :current="request()->routeIs('teacher.assessments.paud')" wire:navigate.hover>
                    {{ __('PAUD') }}
                </flux:sidebar.item>
            @endif
            <flux:sidebar.item icon="calendar-days" :href="route('teacher.assessments.attendance')" :current="request()->routeIs('teacher.assessments.attendance')" wire:navigate.hover>
                {{ __('Presensi') }}
            </flux:sidebar.item>
            <flux:sidebar.item icon="document-text" :href="route('teacher.report-cards')" :current="request()->routeIs('teacher.report-cards')" wire:navigate.hover>
                {{ __('Lihat Rapor') }}
            </flux:sidebar.item>
        </flux:sidebar.group>
    </flux:sidebar.nav>

    <flux:spacer />

    <x-admin.desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
</flux:sidebar>
