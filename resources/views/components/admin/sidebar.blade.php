<div class="px-5 py-6 overflow-hidden">
    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 no-underline min-w-0" wire:navigate>
        <div class="shrink-0 flex items-center justify-center">
            <x-global.app-logo-icon class="size-8 fill-primary block aspect-square object-contain" />
        </div>
        <span class="text-xl font-extrabold text-slate-100 whitespace-nowrap overflow-hidden tracking-tight">{{ config('app.name') }}</span>
    </a>
</div>

<x-ui.menu activate-by-route>
    <x-ui.menu-item title="Dashboard" icon="o-home" :link="route('dashboard')" />

    @if(auth()->user()->isAdmin())
        <x-ui.menu-sub title="Data Master" icon="o-book-open">
            <x-ui.menu-item title="Siswa" icon="o-users" :link="route('students.index')" />
            <x-ui.menu-item title="Penempatan Kelas" icon="o-arrows-right-left" :link="route('students.class-placement')" />
            <x-ui.menu-item title="PTK" icon="o-briefcase" :link="route('ptk.index')" />
            <x-ui.menu-item title="Pengguna" icon="o-user-circle" :link="route('users.index')" />
            <x-ui.menu-item title="Pendaftaran" icon="o-user-plus" :link="route('admin.registrations.index')" />
        </x-ui.menu-sub>

        <x-ui.menu-sub title="Akademik" icon="o-academic-cap">
            <x-ui.menu-item title="Tahun Ajaran" icon="o-calendar" :link="route('academic.years')" />
            <x-ui.menu-item title="Jenjang" icon="o-academic-cap" :link="route('academic.levels')" />
            <x-ui.menu-item title="Kelas" icon="o-building-office" :link="route('academic.classrooms')" />
            <x-ui.menu-item title="Mata Pelajaran" icon="o-book-open" :link="route('academic.subjects')" />
            <x-ui.menu-item title="Penugasan Guru" icon="o-user-group" :link="route('academic.assignments')" />
            <x-ui.menu-item title="Presensi" icon="o-check-badge" :link="route('academic.attendance')" />
            <x-ui.menu-item title="Ekstrakurikuler" icon="o-trophy" :link="route('academic.extracurriculars')" />
        </x-ui.menu-sub>
    @endif

    <x-ui.menu-sub title="Penilaian & Raport" icon="o-pencil-square">
        @if(auth()->user()->isAdmin() || auth()->user()->teachesPaudLevel())
            <x-ui.menu-item title="Penilaian PAUD" icon="o-clipboard-document-check" :link="route('admin.assessments.competency')" />
        @endif
        <x-ui.menu-item title="Raport Kesetaraan" icon="o-document-chart-bar" :link="route('admin.report-card.grading')" />
    </x-ui.menu-sub>

    @if(auth()->user()->isAdmin())
        <x-ui.menu-sub title="Keuangan" icon="o-banknotes">
            <x-ui.menu-item title="Transaksi Keuangan" icon="o-wallet" :link="route('financial.transactions')" />
            <x-ui.menu-item title="RAB / Anggaran" icon="o-document-currency-dollar" :link="route('financial.budget-plans')" />
        </x-ui.menu-sub>

        <x-ui.menu-item title="Konten Web" icon="o-globe-alt" :link="route('admin.school-profile.edit')" />
        <x-ui.menu-item title="Laporan" icon="o-chart-bar" :link="route('reports')" />
    @endif
</x-ui.menu>