<flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
    <flux:sidebar.header>
        <x-global.app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate.hover />
        <flux:sidebar.collapse class="lg:hidden" />
    </flux:sidebar.header>

    <flux:sidebar.nav>
        <!-- Dashboard -->
        <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate.hover class="mb-6">
            {{ __('Dashboard') }}
        </flux:sidebar.item>

        <!-- Data Master -->
        <flux:sidebar.group :heading="__('Data Master')" class="grid">
            <flux:sidebar.item icon="users" :href="route('students.index')" :current="request()->routeIs('students.index')" wire:navigate.hover>
                {{ __('Siswa') }}
            </flux:sidebar.item>
            <flux:sidebar.item icon="briefcase" :href="route('ptk.index')" :current="request()->routeIs('ptk.index')" wire:navigate.hover>
                {{ __('PTK') }}
            </flux:sidebar.item>
        </flux:sidebar.group>

        <!-- Akademik -->
        <flux:sidebar.group :heading="__('Akademik')" class="grid">
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
        <flux:sidebar.group :heading="__('Penilaian & Raport')" class="grid">
            <flux:sidebar.item icon="pencil-square" :href="route('academic.grades')" :current="request()->routeIs('academic.grades')" wire:navigate.hover>
                {{ __('Penilaian') }}
            </flux:sidebar.item>
            <flux:sidebar.item icon="document-text" :href="route('admin.report-card.create')" :current="request()->routeIs('admin.report-card.*')" wire:navigate.hover>
                {{ __('Buat Rapor') }}
            </flux:sidebar.item>
        </flux:sidebar.group>

        <!-- Keuangan -->
        <flux:sidebar.group :heading="__('Keuangan')" class="grid">
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

        <!-- Manajemen Konten Web -->
        <flux:sidebar.group :heading="__('Manajemen Konten Web')" class="grid">
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
        <flux:sidebar.item icon="chart-bar" :href="route('reports')" :current="request()->routeIs('reports')" wire:navigate.hover class="mt-6">
            {{ __('Laporan') }}
        </flux:sidebar.item>
    </flux:sidebar.nav>

    <flux:spacer />

    <x-admin.desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
</flux:sidebar>
