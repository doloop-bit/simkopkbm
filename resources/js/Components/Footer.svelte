<script>
    import { Link, page } from '@inertiajs/svelte';

    let { schoolProfile } = $props();
    
    // Fallback to shared props if not passed directly
    const profile = $derived(schoolProfile || $page.props.schoolProfile || {});

    const name = $derived(profile?.name || 'SIMKOPKBM');
    const phone = $derived(profile?.phone || '6281234567890');
    const email = $derived(profile?.email || 'info@simkopkbm.com');
    const address = $derived(profile?.address || 'Jakarta, Indonesia');
    const facebook = $derived(profile?.facebook_url || '#');
    const twitter = $derived(profile?.twitter_url || '#');
    const instagram = $derived(profile?.instagram_url || '#');
    const youtube = $derived(profile?.youtube_url || '#');
    const currentYear = new Date().getFullYear();

    let logoSrc = $state('/img/logo.svg');
    let logoError = $state(false);

    function handleLogoError() {
        if (logoSrc === '/img/logo.svg') {
            logoSrc = '/img/logo.png';
        } else {
            logoError = true;
        }
    }
</script>

<footer class="bg-slate-900 text-white relative overflow-hidden border-t border-slate-800">
    <!-- Background Pattern -->
    <div class="absolute inset-0 opacity-5">
        <svg class="w-full h-full" viewBox="0 0 100 100" fill="none">
            <defs>
                <pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse">
                    <path d="M 10 0 L 0 0 0 10" fill="none" stroke="currentColor" stroke-width="0.5"/>
                </pattern>
            </defs>
            <rect width="100" height="100" fill="url(#grid)" />
        </svg>
    </div>
    
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
            <!-- About Section -->
            <div class="sm:col-span-2 lg:col-span-2">
                <div class="flex items-center space-x-3 mb-6">
                    <div class="flex items-center justify-center">
                        {#if logoError}
                            <div class="w-10 h-10 bg-amber-500 rounded-lg flex items-center justify-center text-white font-bold">
                                {name.charAt(0)}
                            </div>
                        {:else}
                            <img src={logoSrc} onerror={handleLogoError} alt="Logo" class="w-10 h-10 text-amber-500" />
                        {/if}
                    </div>
                    <div>
                        <h3 class="text-xl font-bold font-heading text-white tracking-tight">{name}</h3>
                        <p class="text-slate-400 text-sm uppercase tracking-wide">Pusat Kegiatan Belajar Masyarakat</p>
                    </div>
                </div>
                <p class="text-slate-400 text-sm leading-relaxed max-w-md mb-6">
                    Berkomitmen memberikan pendidikan berkualitas dan terjangkau untuk semua lapisan masyarakat. 
                    Kami hadir untuk mewujudkan impian pendidikan yang lebih baik.
                </p>
                <div class="flex flex-wrap gap-3">
                    <a href={facebook} class="w-10 h-10 bg-slate-800 hover:bg-amber-500 hover:text-white text-slate-400 rounded-lg flex items-center justify-center transition-all duration-200 shadow-lg hover:shadow-amber-500/20 hover:-translate-y-1">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                    </a>
                    <a href={instagram} class="w-10 h-10 bg-slate-800 hover:bg-amber-500 hover:text-white text-slate-400 rounded-lg flex items-center justify-center transition-all duration-200 shadow-lg hover:shadow-amber-500/20 hover:-translate-y-1">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                    </a>
                    <a href={youtube} class="w-10 h-10 bg-slate-800 hover:bg-amber-500 hover:text-white text-slate-400 rounded-lg flex items-center justify-center transition-all duration-200 shadow-lg hover:shadow-amber-500/20 hover:-translate-y-1">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Quick Links -->
            <div>
                <h3 class="text-lg font-bold font-heading mb-6 text-white border-b-2 border-amber-500 inline-block pb-1">Tautan Cepat</h3>
                <ul class="space-y-3">
                    <li><Link href="/tentang-kami" class="text-slate-400 hover:text-white transition-colors duration-200 flex items-center group text-sm">
                        <svg class="w-4 h-4 mr-2 group-hover:translate-x-1 group-hover:text-amber-400 transition-all duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                        Tentang Kami
                    </Link></li>
                    <li><Link href="/program-pendidikan" class="text-slate-400 hover:text-white transition-colors duration-200 flex items-center group text-sm">
                        <svg class="w-4 h-4 mr-2 group-hover:translate-x-1 group-hover:text-amber-400 transition-all duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                        Program Pendidikan
                    </Link></li>
                    <li><Link href="/berita" class="text-slate-400 hover:text-white transition-colors duration-200 flex items-center group text-sm">
                        <svg class="w-4 h-4 mr-2 group-hover:translate-x-1 group-hover:text-amber-400 transition-all duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                        Berita
                    </Link></li>
                    <li><Link href="/galeri" class="text-slate-400 hover:text-white transition-colors duration-200 flex items-center group text-sm">
                        <svg class="w-4 h-4 mr-2 group-hover:translate-x-1 group-hover:text-amber-400 transition-all duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                        Galeri
                    </Link></li>
                    <li><Link href="/kontak" class="text-slate-400 hover:text-white transition-colors duration-200 flex items-center group text-sm">
                        <svg class="w-4 h-4 mr-2 group-hover:translate-x-1 group-hover:text-amber-400 transition-all duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                        Kontak
                    </Link></li>
                </ul>
            </div>

            <!-- Contact Info -->
            <div>
                <h3 class="text-lg font-bold font-heading mb-6 text-white border-b-2 border-amber-500 inline-block pb-1">Hubungi Kami</h3>
                <ul class="space-y-4">
                    <li class="flex items-start">
                        <div class="w-10 h-10 bg-slate-800 rounded-lg flex items-center justify-center text-amber-500 mt-0.5 mr-3 shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-slate-400 text-xs">Email</p>
                            <p class="text-white text-sm font-medium">{email}</p>
                        </div>
                    </li>
                    <li class="flex items-start">
                        <div class="w-10 h-10 bg-slate-800 rounded-lg flex items-center justify-center text-amber-500 mt-0.5 mr-3 shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-slate-400 text-xs">Telepon</p>
                            <p class="text-white text-sm font-medium">{phone}</p>
                        </div>
                    </li>
                    <li class="flex items-start">
                        <div class="w-10 h-10 bg-slate-800 rounded-lg flex items-center justify-center text-amber-500 mt-0.5 mr-3 shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-slate-400 text-xs">Alamat</p>
                            <p class="text-white text-sm font-medium max-w-[200px]">{address}</p>
                        </div>
                    </li>
                </ul>
            </div>
        </div>

        <div class="border-t border-slate-800 mt-12 pt-8 flex flex-col sm:flex-row justify-between items-center space-y-4 sm:space-y-0">
            <p class="text-slate-400 text-sm text-center sm:text-left">
                &copy; {currentYear} {name}. Semua hak dilindungi.
            </p>
            <div class="flex items-center space-x-4">
                <a href="/privacy" class="text-slate-400 hover:text-white text-sm transition-colors duration-200 hover:underline">Kebijakan Privasi</a>
                <span class="text-slate-700">•</span>
                <a href="/terms" class="text-slate-400 hover:text-white text-sm transition-colors duration-200 hover:underline">Syarat & Ketentuan</a>
            </div>
        </div>
    </div>
</footer>
