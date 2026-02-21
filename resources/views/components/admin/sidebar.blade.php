<flux:sidebar sticky collapsible class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
    <flux:sidebar.header>
        <flux:sidebar.brand :name="config('app.name')" href="{{ route('dashboard') }}" wire:navigate.hover>
            <x-slot name="logo"
                class="flex aspect-square size-10 items-center justify-center rounded-lg bg-accent-content text-accent-foreground">
                <x-global.app-logo-icon class="size-7 fill-current text-white dark:text-black" />
            </x-slot>
        </flux:sidebar.brand>
        <flux:sidebar.collapse
            class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
    </flux:sidebar.header>

    <flux:sidebar.nav>
        <!-- Dashboard -->
        <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')"
            wire:navigate.hover class="mb-3">
            {{ __('Dashboard') }}
        </flux:sidebar.item>

        @if(auth()->user()->isAdmin())
            <!-- Data Master -->
            <flux:sidebar.group expandable icon="book-open" :heading="__('Data Master')" class="grid">
                <flux:sidebar.item icon="users" :href="route('students.index')"
                    :current="request()->routeIs('students.index')" wire:navigate.hover>
                    {{ __('Siswa') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="briefcase" :href="route('ptk.index')" :current="request()->routeIs('ptk.index')"
                    wire:navigate.hover>
                    {{ __('PTK') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="user-circle" :href="route('users.index')" :current="request()->routeIs('users.index')"
                    wire:navigate.hover>
                    {{ __('Pengguna') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="user-plus" :href="route('admin.registrations.index')" :current="request()->routeIs('admin.registrations.*')"
                    wire:navigate.hover>
                    {{ __('Pendaftaran') }}
                </flux:sidebar.item>
            </flux:sidebar.group>
        @endif

        @if(auth()->user()->isAdmin())
            <!-- Akademik -->
            <flux:sidebar.group expandable icon="academic-cap" :heading="__('Akademik')" class="grid">
                <flux:sidebar.item icon="calendar" :href="route('academic.years')"
                    :current="request()->routeIs('academic.years')" wire:navigate.hover>
                    {{ __('Tahun Ajaran') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="academic-cap" :href="route('academic.levels')"
                    :current="request()->routeIs('academic.levels')" wire:navigate.hover>
                    {{ __('Jenjang') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="building-office" :href="route('academic.classrooms')"
                    :current="request()->routeIs('academic.classrooms')" wire:navigate.hover>
                    {{ __('Kelas') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="book-open" :href="route('academic.subjects')"
                    :current="request()->routeIs('academic.subjects')" wire:navigate.hover>
                    {{ __('Mata Pelajaran') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="user-group" :href="route('academic.assignments')"
                    :current="request()->routeIs('academic.assignments')" wire:navigate.hover>
                    {{ __('Penugasan Guru') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="check-badge" :href="route('academic.attendance')"
                    :current="request()->routeIs('academic.attendance')" wire:navigate.hover>
                    {{ __('Presensi') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="trophy" :href="route('academic.extracurriculars')"
                    :current="request()->routeIs('academic.extracurriculars')" wire:navigate.hover>
                    {{ __('Ekstrakurikuler') }}
                </flux:sidebar.item>
            </flux:sidebar.group>
        @endif

        <!-- Penilaian & Raport -->
        <flux:sidebar.group expandable icon="pencil-square" :heading="__('Penilaian & Raport')" class="grid">
            @if(auth()->user()->isAdmin() || auth()->user()->teachesPaudLevel())
                <flux:sidebar.item icon="clipboard-document-check" :href="route('admin.assessments.competency')"
                    :current="request()->routeIs('admin.assessments.competency')" wire:navigate.hover>
                    {{ __('Penilaian PAUD') }}
                </flux:sidebar.item>
            @endif


            <flux:sidebar.item icon="document-chart-bar" :href="route('admin.report-card.grading')"
                :current="request()->routeIs('admin.report-card.*') || request()->routeIs('admin.assessments.grading')" wire:navigate.hover>
                {{ __('Raport Kesetaraan') }}
            </flux:sidebar.item>
        </flux:sidebar.group>


        @if(auth()->user()->isAdmin())
            <!-- Keuangan -->
            <flux:sidebar.group expandable icon="banknotes" :heading="__('Keuangan')" class="grid">
                <flux:sidebar.item icon="wallet" :href="route('financial.payments')"
                    :current="request()->routeIs('financial.payments')" wire:navigate.hover>
                    {{ __('Transaksi') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="document-text" :href="route('financial.billings')"
                    :current="request()->routeIs('financial.billings')" wire:navigate.hover>
                    {{ __('Tagihan Siswa') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="gift" :href="route('financial.discounts')"
                    :current="request()->routeIs('financial.discounts')" wire:navigate.hover>
                    {{ __('Potongan & Beasiswa') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="swatch" :href="route('financial.categories')"
                    :current="request()->routeIs('financial.categories')" wire:navigate.hover>
                    {{ __('Kategori Biaya') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="tag" :href="route('financial.budget-categories')"
                    :current="request()->routeIs('financial.budget-categories')" wire:navigate.hover>
                    {{ __('Kategori Anggaran') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="shopping-cart" :href="route('financial.standard-items')"
                    :current="request()->routeIs('financial.standard-items')" wire:navigate.hover>
                    {{ __('Item Standar') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="document-currency-dollar" :href="route('financial.budget-plans')"
                    :current="request()->routeIs('financial.budget-plans')" wire:navigate.hover>
                    {{ __('RAB / Anggaran') }}
                </flux:sidebar.item>
            </flux:sidebar.group>


            <!-- Konten Web - Moved to horizontal header navigation -->
            <flux:sidebar.item icon="globe-alt" :href="route('admin.school-profile.edit')"
                :current="request()->routeIs('admin.school-profile.*') || request()->routeIs('admin.news.*') || request()->routeIs('admin.gallery.*') || request()->routeIs('admin.programs.*') || request()->routeIs('admin.contact-inquiries.*')"
                wire:navigate.hover>
                {{ __('Konten Web') }}
            </flux:sidebar.item>


            <!-- Laporan -->
            <flux:sidebar.item icon="chart-bar" :href="route('reports')" :current="request()->routeIs('reports')"
                wire:navigate.hover class="mt-3">
                {{ __('Laporan') }}
            </flux:sidebar.item>
        @endif
    </flux:sidebar.nav>

    <flux:spacer />

    <x-admin.desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
</flux:sidebar>