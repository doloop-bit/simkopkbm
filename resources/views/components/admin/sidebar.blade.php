<div class="px-5 py-6">
    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 no-underline" wire:navigate>
        <x-global.app-logo-icon class="size-8 fill-primary" />
        <span class="text-xl font-bold text-base-content">{{ config('app.name') }}</span>
    </a>
</div>

<x-menu activate-by-route>
    <x-menu-item title="Dashboard" icon="o-home" :link="route('dashboard')" />

    @if(auth()->user()->isAdmin())
        <x-menu-sub title="Data Master" icon="o-book-open">
            <x-menu-item title="Siswa" icon="o-users" :link="route('students.index')" />
            <x-menu-item title="Penempatan Kelas" icon="o-arrows-right-left" :link="route('students.class-placement')" />
            <x-menu-item title="PTK" icon="o-briefcase" :link="route('ptk.index')" />
            <x-menu-item title="Pengguna" icon="o-user-circle" :link="route('users.index')" />
            <x-menu-item title="Pendaftaran" icon="o-user-plus" :link="route('admin.registrations.index')" />
        </x-menu-sub>

        <x-menu-sub title="Akademik" icon="o-academic-cap">
            <x-menu-item title="Tahun Ajaran" icon="o-calendar" :link="route('academic.years')" />
            <x-menu-item title="Jenjang" icon="o-academic-cap" :link="route('academic.levels')" />
            <x-menu-item title="Kelas" icon="o-building-office" :link="route('academic.classrooms')" />
            <x-menu-item title="Mata Pelajaran" icon="o-book-open" :link="route('academic.subjects')" />
            <x-menu-item title="Penugasan Guru" icon="o-user-group" :link="route('academic.assignments')" />
            <x-menu-item title="Presensi" icon="o-check-badge" :link="route('academic.attendance')" />
            <x-menu-item title="Ekstrakurikuler" icon="o-trophy" :link="route('academic.extracurriculars')" />
        </x-menu-sub>
    @endif

    <x-menu-sub title="Penilaian & Raport" icon="o-pencil-square">
        @if(auth()->user()->isAdmin() || auth()->user()->teachesPaudLevel())
            <x-menu-item title="Penilaian PAUD" icon="o-clipboard-document-check" :link="route('admin.assessments.competency')" />
        @endif
        <x-menu-item title="Raport Kesetaraan" icon="o-document-chart-bar" :link="route('admin.report-card.grading')" />
    </x-menu-sub>

    @if(auth()->user()->isAdmin())
        <x-menu-sub title="Keuangan" icon="o-banknotes">
            <x-menu-item title="Transaksi Keuangan" icon="o-wallet" :link="route('financial.transactions')" />
            <x-menu-item title="RAB / Anggaran" icon="o-document-currency-dollar" :link="route('financial.budget-plans')" />
        </x-menu-sub>

        <x-menu-item title="Konten Web" icon="o-globe-alt" :link="route('admin.school-profile.edit')" />
        <x-menu-item title="Laporan" icon="o-chart-bar" :link="route('reports')" />
    @endif
</x-menu>