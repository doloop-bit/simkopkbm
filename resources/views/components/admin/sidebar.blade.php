<flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
    <flux:sidebar.header>
        <x-global.app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
        <flux:sidebar.collapse class="lg:hidden" />
    </flux:sidebar.header>

    <flux:sidebar.nav>
        <flux:sidebar.group :heading="__('Platform')" class="grid">
            <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                {{ __('Dashboard') }}
            </flux:sidebar.item>
            <flux:sidebar.item icon="users" :href="route('students.index')" :current="request()->routeIs('students.index')" wire:navigate>
                {{ __('Siswa') }}
            </flux:sidebar.item>
            <flux:sidebar.item icon="briefcase" :href="route('ptk.index')" :current="request()->routeIs('ptk.index')" wire:navigate>
                {{ __('Manajemen PTK') }}
            </flux:sidebar.item>
        </flux:sidebar.group>

        <flux:sidebar.group :heading="__('Akademik')" class="grid">
            <flux:sidebar.item icon="calendar" :href="route('academic.years')" :current="request()->routeIs('academic.years')" wire:navigate>
                {{ __('Tahun Ajaran') }}
            </flux:sidebar.item>
            <flux:sidebar.item icon="academic-cap" :href="route('academic.levels')" :current="request()->routeIs('academic.levels')" wire:navigate>
                {{ __('Jenjang') }}
            </flux:sidebar.item>
            <flux:sidebar.item icon="building-office" :href="route('academic.classrooms')" :current="request()->routeIs('academic.classrooms')" wire:navigate>
                {{ __('Kelas') }}
            </flux:sidebar.item>
            <flux:sidebar.item icon="book-open" :href="route('academic.subjects')" :current="request()->routeIs('academic.subjects')" wire:navigate>
                {{ __('Mata Pelajaran') }}
            </flux:sidebar.item>
            <flux:sidebar.item icon="user-group" :href="route('academic.assignments')" :current="request()->routeIs('academic.assignments')" wire:navigate>
                {{ __('Penugasan Guru') }}
            </flux:sidebar.item>
            <flux:sidebar.item icon="check-badge" :href="route('academic.attendance')" :current="request()->routeIs('academic.attendance')" wire:navigate>
                {{ __('Presensi') }}
            </flux:sidebar.item>
            <flux:sidebar.item icon="pencil-square" :href="route('academic.grades')" :current="request()->routeIs('academic.grades')" wire:navigate>
                {{ __('Penilaian') }}
            </flux:sidebar.item>
        </flux:sidebar.group>

        <flux:sidebar.group label="{{ __('Keuangan') }}" class="mt-6">
            <flux:sidebar.item icon="wallet" :href="route('financial.payments')" :current="request()->routeIs('financial.payments')" wire:navigate>
                {{ __('Transaksi') }}
            </flux:sidebar.item>
            <flux:sidebar.item icon="document-text" :href="route('financial.billings')" :current="request()->routeIs('financial.billings')" wire:navigate>
                {{ __('Tagihan') }}
            </flux:sidebar.item>
            <flux:sidebar.item icon="swatch" :href="route('financial.categories')" :current="request()->routeIs('financial.categories')" wire:navigate>
                {{ __('Kategori Biaya') }}
            </flux:sidebar.item>
        </flux:sidebar.group>

        <flux:sidebar.group label="{{ __('Website') }}" class="mt-6">
            <flux:sidebar.item icon="building-office-2" :href="route('admin.school-profile.edit')" :current="request()->routeIs('admin.school-profile.*')" wire:navigate>
                {{ __('Profil Sekolah') }}
            </flux:sidebar.item>
            <flux:sidebar.item icon="newspaper" :href="route('admin.news.index')" :current="request()->routeIs('admin.news.*')" wire:navigate>
                {{ __('Berita & Artikel') }}
            </flux:sidebar.item>
            <flux:sidebar.item icon="photo" :href="route('admin.gallery.index')" :current="request()->routeIs('admin.gallery.*')" wire:navigate>
                {{ __('Galeri') }}
            </flux:sidebar.item>
            <flux:sidebar.item icon="academic-cap" :href="route('admin.programs.index')" :current="request()->routeIs('admin.programs.*')" wire:navigate>
                {{ __('Program Pendidikan') }}
            </flux:sidebar.item>
            <flux:sidebar.item icon="envelope" :href="route('admin.contact-inquiries.index')" :current="request()->routeIs('admin.contact-inquiries.*')" wire:navigate>
                {{ __('Pesan Kontak') }}
            </flux:sidebar.item>
        </flux:sidebar.group>

        <flux:sidebar.group label="{{ __('Analitik') }}" class="mt-6">
            <flux:sidebar.item icon="chart-bar" :href="route('reports')" :current="request()->routeIs('reports')" wire:navigate>
                {{ __('Laporan') }}
            </flux:sidebar.item>
        </flux:sidebar.group>
    </flux:sidebar.nav>

    <flux:spacer />

    <x-admin.desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
</flux:sidebar>
