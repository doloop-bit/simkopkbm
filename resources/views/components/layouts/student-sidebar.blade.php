<x-ui.menu activate-by-route>
    {{-- Portal Siswa --}}
    <x-ui.menu-item title="{{ __('Dashboard Utama') }}" icon="o-home" :link="route('student.dashboard')" />

    <x-ui.menu-separator />

    {{-- Akademik & E-Learning --}}
    <x-ui.menu-sub title="{{ __('Akademik & E-Learning') }}" icon="o-academic-cap" :active="request()->routeIs('student.materials*') || request()->routeIs('student.exams*')">
        <x-ui.menu-item title="{{ __('Materi Pembelajaran') }}" icon="o-book-open" :link="route('student.materials')" />
        <x-ui.menu-item title="{{ __('Ulangan & Ujian') }}" icon="o-clipboard-document-check" :link="route('student.exams')" />
    </x-ui.menu-sub>

    <x-ui.menu-separator />

    {{-- Akun & Pengaturan --}}
    <x-ui.menu-sub title="{{ __('Akun Siswa') }}" icon="o-user-circle" :active="request()->routeIs('profile.edit')">
        <x-ui.menu-item title="{{ __('Profil Saya') }}" icon="o-identification" :link="route('profile.edit')" />
    </x-ui.menu-sub>
</x-ui.menu>
