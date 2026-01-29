<flux:sidebar sticky collapsible class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
    <flux:sidebar.header>
        <flux:sidebar.brand :name="config('app.name')" href="{{ route('dashboard') }}" wire:navigate.hover>
            <x-slot name="logo" class="flex aspect-square size-10 items-center justify-center rounded-lg bg-accent-content text-accent-foreground">
                <x-global.app-logo-icon class="size-7 fill-current text-white dark:text-black" />
            </x-slot>
        </flux:sidebar.brand>
        <flux:sidebar.collapse class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
    </flux:sidebar.header>

    <flux:sidebar.nav>
        <!-- Dashboard -->
        <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate.hover class="mb-3">
            {{ __('Dashboard') }}
        </flux:sidebar.item>

        <!-- Data Master -->
        <flux:sidebar.group expandable :expanded="request()->routeIs('students.*', 'ptk.*') || request()->is('admin/students*', 'admin/ptk*')" icon="book-open" :heading="__('Data Master')" class="grid">
            <flux:sidebar.item icon="users" :href="route('students.index')" :current="request()->routeIs('students.index')" wire:navigate.hover>
                {{ __('Siswa') }}
            </flux:sidebar.item>
            <flux:sidebar.item icon="briefcase" :href="route('ptk.index')" :current="request()->routeIs('ptk.index')" wire:navigate.hover>
                {{ __('PTK') }}
            </flux:sidebar.item>
        </flux:sidebar.group>

        <!-- Akademik -->
        <flux:sidebar.group expandable :expanded="request()->routeIs('academic.*') || request()->is('admin/academic*')" icon="academic-cap" :heading="__('Akademik')" class="grid">
            <flux:sidebar.item icon="calendar" :href="route('academic.years')" :current="request()->routeIs('academic.years')" wire:navigate.hover>
                {{ __('Tahun Ajaran') }}
            </flux:sidebar.item>
            <flux:sidebar.item icon="academic-cap" :href="route('academic.levels')" :current="request()->routeIs('academic.levels')" wire:navigate.hover>
                {{ __('Jenjang') }}
            </flux:sidebar.item>
            <flux:sidebar.item icon="building-office" :href="route('academic.classrooms')" :current="request()->routeIs('academic.classrooms')" wire:navigate.hover>
                {{ __('Kelas') }}
            </flux:sidebar.item>
            <flux:sidebar.item icon="book-open" :href="route('academic.subjects')" :current="request()->routeIs('academic.subjects')" wire:navigate.hover>
                {{ __('Mata Pelajaran') }}
            </flux:sidebar.item>
            <flux:sidebar.item icon="user-group" :href="route('academic.assignments')" :current="request()->routeIs('academic.assignments')" wire:navigate.hover>
                {{ __('Penugasan Guru') }}
            </flux:sidebar.item>
            <flux:sidebar.item icon="check-badge" :href="route('academic.attendance')" :current="request()->routeIs('academic.attendance')" wire:navigate.hover>
                {{ __('Presensi') }}
            </flux:sidebar.item>
        </flux:sidebar.group>

        <!-- Penilaian & Raport -->
        <flux:sidebar.group expandable :expanded="request()->routeIs('admin.assessments.*', 'admin.report-card.*', 'academic.grades') || request()->is('admin/assessments*', 'admin/report-card*', 'admin/grades*')" icon="pencil-square" :heading="__('Penilaian & Raport')" class="grid">
            <flux:sidebar.item icon="pencil-square" :href="route('academic.grades')" :current="request()->routeIs('academic.grades')" wire:navigate.hover>
                {{ __('Penilaian (Nilai)') }}
            </flux:sidebar.item>
            
            @if(auth()->user()->isAdmin() || auth()->user()->teachesPaudLevel())
                <flux:sidebar.item icon="clipboard-document-check" :href="route('admin.assessments.competency')" :current="request()->routeIs('admin.assessments.competency')" wire:navigate.hover>
                    {{ __('Penilaian Kompetensi') }}
                </flux:sidebar.item>
            @endif

            <flux:sidebar.item icon="clipboard-document-list" :href="route('admin.assessments.p5')" :current="request()->routeIs('admin.assessments.p5')" wire:navigate.hover>
                {{ __('Penilaian P5') }}
            </flux:sidebar.item>
            <flux:sidebar.item icon="trophy" :href="route('admin.assessments.extracurricular')" :current="request()->routeIs('admin.assessments.extracurricular')" wire:navigate.hover>
                {{ __('Penilaian Ekskul') }}
            </flux:sidebar.item>
            <flux:sidebar.item icon="calendar-days" :href="route('admin.assessments.attendance')" :current="request()->routeIs('admin.assessments.attendance')" wire:navigate.hover>
                {{ __('Presensi Rapor') }}
            </flux:sidebar.item>
            <flux:sidebar.item icon="document-text" :href="route('admin.report-card.create')" :current="request()->routeIs('admin.report-card.*')" wire:navigate.hover>
                {{ __('Buat Rapor') }}
            </flux:sidebar.item>
        </flux:sidebar.group>


        <!-- Keuangan -->
        <flux:sidebar.group expandable :expanded="request()->routeIs('financial.*') || request()->is('admin/financial*')" icon="banknotes" :heading="__('Keuangan')" class="grid">
            <flux:sidebar.item icon="wallet" :href="route('financial.payments')" :current="request()->routeIs('financial.payments')" wire:navigate.hover>
                {{ __('Transaksi') }}
            </flux:sidebar.item>
            <flux:sidebar.item icon="document-text" :href="route('financial.billings')" :current="request()->routeIs('financial.billings')" wire:navigate.hover>
                {{ __('Tagihan') }}
            </flux:sidebar.item>
            <flux:sidebar.item icon="swatch" :href="route('financial.categories')" :current="request()->routeIs('financial.categories')" wire:navigate.hover>
                {{ __('Kategori Biaya') }}
            </flux:sidebar.item>
        </flux:sidebar.group>

        <!-- Konten Web -->
        <flux:sidebar.group expandable :expanded="request()->routeIs('admin.school-profile.*', 'admin.news.*', 'admin.gallery.*', 'admin.programs.*', 'admin.contact-inquiries.*') || request()->is('admin/profil-sekolah*', 'admin/news*', 'admin/galeri*', 'admin/programs*', 'admin/pesan-kontak*')" icon="globe-alt" :heading="__('Konten Web')" class="grid">
            <flux:sidebar.item icon="building-office-2" :href="route('admin.school-profile.edit')" :current="request()->routeIs('admin.school-profile.*')" wire:navigate.hover>
                {{ __('Profil Sekolah') }}
            </flux:sidebar.item>
            <flux:sidebar.item icon="newspaper" :href="route('admin.news.index')" :current="request()->routeIs('admin.news.*')" wire:navigate.hover>
                {{ __('Berita & Artikel') }}
            </flux:sidebar.item>
            <flux:sidebar.item icon="photo" :href="route('admin.gallery.index')" :current="request()->routeIs('admin.gallery.*')" wire:navigate.hover>
                {{ __('Galeri') }}
            </flux:sidebar.item>
            <flux:sidebar.item icon="academic-cap" :href="route('admin.programs.index')" :current="request()->routeIs('admin.programs.*')" wire:navigate.hover>
                {{ __('Program Pendidikan') }}
            </flux:sidebar.item>
            <flux:sidebar.item icon="envelope" :href="route('admin.contact-inquiries.index')" :current="request()->routeIs('admin.contact-inquiries.*')" wire:navigate.hover>
                {{ __('Pesan Kontak') }}
            </flux:sidebar.item>
        </flux:sidebar.group>

        <!-- Laporan -->
        <flux:sidebar.item icon="chart-bar" :href="route('reports')" :current="request()->routeIs('reports')" wire:navigate.hover class="mt-3">
            {{ __('Laporan') }}
        </flux:sidebar.item>
    </flux:sidebar.nav>

    <flux:spacer />

    <x-admin.desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
</flux:sidebar>
