<x-ui.menu activate-by-route>
    {{-- Common Dashboard --}}
    @if(auth()->user()->isGuru())
        <x-ui.menu-item title="Dashboard" icon="o-home" :link="route('teacher.dashboard')" />
    @else
        <x-ui.menu-item title="Dashboard" icon="o-home" :link="route('dashboard')" />
    @endif

    {{-- Teacher Sections --}}
    @if(auth()->user()->isGuru())
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
    @endif

    {{-- Admin / Staff Sections --}}
    @if(auth()->user()->isAdmin() || auth()->user()->isHeadmaster() || auth()->user()->isYayasan())
        <x-ui.menu-sub title="Data Master" icon="o-book-open" :active="request()->routeIs('students.*') || request()->routeIs('ptk.*') || request()->routeIs('users.*') || request()->routeIs('admin.registrations.*')">
            <x-ui.menu-item title="Siswa" icon="o-users" :link="route('students.index')" />
            
            @if(!auth()->user()->isYayasan())
                <x-ui.menu-item title="Penempatan Kelas" icon="o-arrows-right-left" :link="route('students.class-placement')" />
            @endif

            <x-ui.menu-item title="PTK" icon="o-briefcase" :link="route('ptk.index')" />
            
            @if(!auth()->user()->isYayasan())
                <x-ui.menu-item title="Pengguna" icon="o-user-circle" :link="route('users.index')" />
            @endif

            @if(!auth()->user()->isYayasan() || auth()->user()->isHeadmaster())
                <x-ui.menu-item title="Pendaftaran" icon="o-user-plus" :link="route('admin.registrations.index')" />
            @endif
        </x-ui.menu-sub>

        @if(!auth()->user()->isYayasan() || auth()->user()->isHeadmaster())
            <x-ui.menu-sub title="Akademik" icon="o-academic-cap" :active="request()->routeIs('academic.*')">
                <x-ui.menu-item title="Tahun Ajaran" icon="o-calendar" :link="route('academic.years')" />
                <x-ui.menu-item title="Jenjang" icon="o-academic-cap" :link="route('academic.levels')" />
                <x-ui.menu-item title="Kelas" icon="o-building-office" :link="route('academic.classrooms')" />

                <x-ui.menu-item title="Mata Pelajaran" icon="o-book-open" :link="route('academic.subjects')" />
                <x-ui.menu-item title="Penugasan Guru" icon="o-user-group" :link="route('academic.assignments')" />
                <x-ui.menu-item title="Presensi" icon="o-check-badge" :link="route('academic.attendance')" />
                <x-ui.menu-item title="Ekstrakurikuler" icon="o-trophy" :link="route('academic.extracurriculars')" />
            </x-ui.menu-sub>

            <x-ui.menu-sub title="Penilaian & Raport" icon="o-pencil-square" :active="request()->routeIs('admin.assessments.*') || request()->routeIs('admin.report-card.*')">
                <x-ui.menu-item title="Penilaian PAUD" icon="o-clipboard-document-check" :link="route('admin.assessments.competency')" />
                <x-ui.menu-item title="Raport Kesetaraan" icon="o-document-chart-bar" :link="route('admin.report-card.grading')" />
            </x-ui.menu-sub>
        @endif
    @endif

    @if(auth()->user()->isAdmin() || auth()->user()->isTreasurer() || auth()->user()->isHeadmaster() || auth()->user()->isYayasan())
        <x-ui.menu-sub title="Keuangan" icon="o-banknotes" :active="request()->routeIs('financial.*')">
            <x-ui.menu-item title="Transaksi Keuangan" icon="o-wallet" :link="route('financial.transactions')" />
            <x-ui.menu-item title="RAB / Anggaran" icon="o-document-currency-dollar" :link="route('financial.budget-plans')" />
        </x-ui.menu-sub>

        @if(auth()->user()->isAdmin())
            <x-ui.menu-item title="Konten Web" icon="o-globe-alt" :link="route('admin.school-profile.edit')" />
        @endif

        <x-ui.menu-item title="Laporan" icon="o-chart-bar" :link="route('reports')" />
    @endif
</x-ui.menu>
