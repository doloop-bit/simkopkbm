<script>
    import { Link, usePage } from "@inertiajs/svelte";

    const page = usePage();

    let { schoolProfile, currentRoute } = $props();
    let mobileMenuOpen = $state(false);
    let aboutDropdownOpen = $state(false);

    // Fallback to shared props if not passed directly
    const profile = $derived(schoolProfile || page.props.schoolProfile || {});

    const schoolName = $derived(profile?.name || "SIMKOPKBM");
    const phone = $derived(profile?.phone || "6281234567890");
    const email = $derived(profile?.email || "info@simkopkbm.com");
    const facebook = $derived(profile?.facebook_url || "#");
    const instagram = $derived(profile?.instagram_url || "#");

    // Dynamic Logo handling
    const initialLogo = profile?.logo_path
        ? `/storage/${profile.logo_path}`
        : "/img/logo.svg";
    let logoSrc = $state(initialLogo);
    let logoError = $state(false);

    function handleLogoError() {
        if (logoSrc === "/img/logo.svg") {
            logoSrc = "/img/logo.png";
        } else if (
            profile?.logo_path &&
            logoSrc === `/storage/${profile.logo_path}`
        ) {
            logoSrc = "/img/logo.svg"; // Fallback to default if custom logo fails
        } else {
            logoError = true;
        }
    }
</script>

<!-- Top Bar -->
<div
    class="bg-slate-900 text-white py-2 hidden lg:block border-b border-white/10"
>
    <div
        class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex justify-between items-center text-xs font-medium"
    >
        <div class="flex items-center space-x-6">
            <a
                href="tel:+{phone.replace(/[^0-9]/g, '')}"
                class="flex items-center space-x-2 text-slate-300 hover:text-amber-400 transition-colors"
            >
                <svg
                    class="w-4 h-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"
                    />
                </svg>
                <span>+{phone.replace(/^0/, "62").replace(/[^0-9]/g, "")}</span>
            </a>
            <a
                href="mailto:{email}"
                class="flex items-center space-x-2 text-slate-300 hover:text-amber-400 transition-colors"
            >
                <svg
                    class="w-4 h-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                    />
                </svg>
                <span>{email}</span>
            </a>
        </div>
        <div class="flex items-center space-x-4">
            <a
                href={facebook}
                class="text-slate-300 hover:text-amber-400 transition-colors"
                aria-label="Facebook"
            >
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"
                    ><path
                        d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"
                    /></svg
                >
            </a>
            <a
                href={instagram}
                class="text-slate-300 hover:text-amber-400 transition-colors"
                aria-label="Instagram"
            >
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"
                    ><path
                        d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"
                    /></svg
                >
            </a>
        </div>
    </div>
</div>

<!-- Navigation -->
<nav
    class="bg-slate-900/95 backdrop-blur-md shadow-xl border-b border-white/10 sticky top-0 z-50"
>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-20">
            <!-- Logo -->
            <div class="shrink-0 flex items-center h-full">
                <Link href="/" class="flex items-center space-x-3 group">
                    {#if logoError}
                        <div
                            class="w-10 h-10 bg-amber-500 rounded-lg flex items-center justify-center text-white font-bold"
                        >
                            {schoolName.charAt(0)}
                        </div>
                    {:else}
                        <img
                            src={logoSrc}
                            onerror={handleLogoError}
                            alt="Logo"
                            class="w-10 h-10 rounded-lg shadow-lg group-hover:shadow-amber-500/50 transition-all duration-300 group-hover:scale-105 object-contain"
                        />
                    {/if}
                    <div class="hidden sm:block">
                        <h1
                            class="text-xl font-heading font-bold text-white tracking-tight group-hover:text-amber-400 transition-colors"
                        >
                            {schoolName}
                        </h1>
                        <p
                            class="text-[0.65rem] uppercase tracking-wider text-slate-400 font-semibold group-hover:text-white transition-colors"
                        >
                            Pusat Kegiatan Belajar Masyarakat
                        </p>
                    </div>
                </Link>
            </div>

            <!-- Desktop Navigation -->
            <div
                class="hidden lg:flex lg:space-x-8 lg:h-full lg:flex-1 lg:justify-center"
            >
                <Link
                    href="/"
                    class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-semibold transition-all duration-200 h-full {currentRoute ===
                    'Home'
                        ? 'border-amber-400 text-amber-400'
                        : 'border-transparent text-slate-300 hover:text-white hover:border-slate-300'}"
                >
                    Beranda
                </Link>

                <!-- About Dropdown -->
                <div class="relative h-full flex items-center">
                    <button
                        onmouseenter={() => (aboutDropdownOpen = true)}
                        onmouseleave={() => (aboutDropdownOpen = false)}
                        onclick={() => (aboutDropdownOpen = !aboutDropdownOpen)}
                        class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-semibold transition-all duration-200 h-full {[
                            'About',
                            'Staff',
                            'Facilities',
                        ].includes(currentRoute)
                            ? 'border-amber-400 text-amber-400'
                            : 'border-transparent text-slate-300 hover:text-white hover:border-slate-300'}"
                    >
                        Tentang Kami
                        <svg
                            class="ml-1 h-4 w-4 transition-transform duration-200"
                            class:rotate-180={aboutDropdownOpen}
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M19 9l-7 7-7-7"
                            />
                        </svg>
                    </button>
                    {#if aboutDropdownOpen}
                        <div
                            onmouseenter={() => (aboutDropdownOpen = true)}
                            onmouseleave={() => (aboutDropdownOpen = false)}
                            class="absolute left-1/2 -translate-x-1/2 top-full mt-1 w-56 rounded-xl bg-slate-800 border border-slate-700 shadow-xl z-9999"
                            role="menu"
                            tabindex="-1"
                        >
                            <div class="py-2">
                                <Link
                                    href="/tentang-kami"
                                    class="flex items-center px-4 py-3 text-sm text-slate-300 hover:bg-slate-700 hover:text-amber-400 transition-colors duration-150"
                                >
                                    <svg
                                        class="w-4 h-4 mr-3 text-amber-500"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                        />
                                    </svg>
                                    Profil Sekolah
                                </Link>
                                <Link
                                    href="/struktur-organisasi"
                                    class="flex items-center px-4 py-3 text-sm text-slate-300 hover:bg-slate-700 hover:text-amber-400 transition-colors duration-150"
                                >
                                    <svg
                                        class="w-4 h-4 mr-3 text-amber-500"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                                        />
                                    </svg>
                                    Struktur Organisasi
                                </Link>
                                <Link
                                    href="/fasilitas"
                                    class="flex items-center px-4 py-3 text-sm text-slate-300 hover:bg-slate-700 hover:text-amber-400 transition-colors duration-150"
                                >
                                    <svg
                                        class="w-4 h-4 mr-3 text-amber-500"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                                        />
                                    </svg>
                                    Fasilitas
                                </Link>
                            </div>
                        </div>
                    {/if}
                </div>

                <Link
                    href="/program-pendidikan"
                    class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-semibold transition-all duration-200 h-full {currentRoute &&
                    currentRoute.startsWith('Programs')
                        ? 'border-amber-400 text-amber-400'
                        : 'border-transparent text-slate-300 hover:text-white hover:border-slate-300'}"
                >
                    Program
                </Link>
                <Link
                    href="/berita"
                    class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-semibold transition-all duration-200 h-full {currentRoute &&
                    currentRoute.startsWith('News')
                        ? 'border-amber-400 text-amber-400'
                        : 'border-transparent text-slate-300 hover:text-white hover:border-slate-300'}"
                >
                    Berita
                </Link>
                <Link
                    href="/galeri"
                    class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-semibold transition-all duration-200 h-full {currentRoute ===
                    'Gallery'
                        ? 'border-amber-400 text-amber-400'
                        : 'border-transparent text-slate-300 hover:text-white hover:border-slate-300'}"
                >
                    Galeri
                </Link>
                <!-- <Link
                    href="/kontak"
                    class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-semibold transition-all duration-200 h-full {currentRoute ===
                    'Contact'
                        ? 'border-amber-400 text-amber-400'
                        : 'border-transparent text-slate-300 hover:text-white hover:border-slate-300'}"
                >
                    Kontak
                </Link> -->
            </div>

            <div class="hidden lg:flex lg:items-center lg:space-x-4 shrink-0">
                <a
                    href="/login"
                    class="inline-flex items-center px-6 py-2.5 rounded-full text-sm font-bold border-2 border-slate-700 text-slate-300 hover:bg-slate-800 hover:text-white hover:border-amber-500 transition-all duration-200 transform hover:-translate-y-0.5"
                >
                    Login Admin
                </a>
                <Link
                    href="/pendaftaran"
                    class="inline-flex items-center px-6 py-2.5 rounded-full text-sm font-bold bg-linear-to-r from-amber-500 to-amber-600 text-white shadow-lg hover:from-amber-600 hover:to-amber-700 transition-all duration-200 transform hover:-translate-y-0.5"
                >
                    Daftar Sekarang
                </Link>
            </div>

            <!-- Mobile menu button -->
            <div class="flex items-center lg:hidden">
                <button
                    onclick={() => (mobileMenuOpen = !mobileMenuOpen)}
                    type="button"
                    class="inline-flex items-center justify-center p-2 rounded-lg text-slate-300 hover:text-white hover:bg-white/10 focus:outline-none transition-colors duration-200"
                    aria-label="Toggle menu"
                >
                    <svg
                        class="h-6 w-6"
                        class:hidden={mobileMenuOpen}
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16"
                        />
                    </svg>
                    <svg
                        class="h-6 w-6"
                        class:hidden={!mobileMenuOpen}
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"
                        />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile menu -->
    {#if mobileMenuOpen}
        <div
            class="absolute top-full left-0 right-0 bg-slate-900 border-t border-slate-800 shadow-xl z-50 lg:hidden max-h-[90vh] overflow-y-auto"
        >
            <div class="px-4 pt-2 pb-6 space-y-1">
                <Link
                    href="/"
                    class="flex items-center px-3 py-3 rounded-lg text-base font-medium {currentRoute ===
                    'Home'
                        ? 'bg-white/10 text-amber-400'
                        : 'text-slate-300'}"
                >
                    Beranda
                </Link>
                <Link
                    href="/tentang-kami"
                    class="flex items-center px-3 py-3 rounded-lg text-base font-medium {currentRoute ===
                    'About'
                        ? 'bg-white/10 text-amber-400'
                        : 'text-slate-300'}"
                >
                    Profil Sekolah
                </Link>
                <Link
                    href="/struktur-organisasi"
                    class="flex items-center px-3 py-3 rounded-lg text-base font-medium {currentRoute ===
                    'Staff'
                        ? 'bg-white/10 text-amber-400'
                        : 'text-slate-300'}"
                >
                    Struktur Organisasi
                </Link>
                <Link
                    href="/fasilitas"
                    class="flex items-center px-3 py-3 rounded-lg text-base font-medium {currentRoute ===
                    'Facilities'
                        ? 'bg-white/10 text-amber-400'
                        : 'text-slate-300'}"
                >
                    Fasilitas
                </Link>
                <Link
                    href="/program-pendidikan"
                    class="flex items-center px-3 py-3 rounded-lg text-base font-medium {currentRoute &&
                    currentRoute.startsWith('Programs')
                        ? 'bg-white/10 text-amber-400'
                        : 'text-slate-300'}"
                >
                    Program Pendidikan
                </Link>
                <Link
                    href="/berita"
                    class="flex items-center px-3 py-3 rounded-lg text-base font-medium {currentRoute &&
                    currentRoute.startsWith('News')
                        ? 'bg-white/10 text-amber-400'
                        : 'text-slate-300'}"
                >
                    Berita
                </Link>
                <Link
                    href="/galeri"
                    class="flex items-center px-3 py-3 rounded-lg text-base font-medium {currentRoute ===
                    'Gallery'
                        ? 'bg-white/10 text-amber-400'
                        : 'text-slate-300'}"
                >
                    Galeri
                </Link>
                <div class="pt-4 mt-4 border-t border-slate-700 space-y-3">
                    <Link
                        href="/pendaftaran"
                        class="flex items-center justify-center px-3 py-3 rounded-full text-base font-bold bg-amber-500 text-white shadow-lg mx-3"
                    >
                        Daftar Sekarang
                    </Link>
                    <a
                        href="/login"
                        class="flex items-center justify-center px-3 py-3 rounded-full text-base font-bold border-2 border-slate-700 text-slate-300 mx-3"
                    >
                        Login Admin
                    </a>
                </div>
            </div>
        </div>
    {/if}
</nav>
