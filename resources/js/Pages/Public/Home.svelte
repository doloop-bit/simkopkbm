<script>
    import PublicLayout from '../../Layouts/PublicLayout.svelte';
    
    let { schoolProfile, latestNews, programs, featuredPhotos } = $props();
    
    let activeSlide = $state(0);
    
    // Auto slide for hero carousel
    if (programs.length > 1) {
        setInterval(() => {
            activeSlide = (activeSlide + 1) % programs.length;
        }, 5000);
    }

    const schoolName = schoolProfile?.name || 'SIMKOPKBM';
    const vision = schoolProfile?.vision || '';
    const logoUrl = schoolProfile?.logo_path ? `/storage/${schoolProfile.logo_path}` : null;
</script>

<PublicLayout {schoolProfile} currentRoute="Home">
    <!-- Hero Section -->
    <div class="relative bg-zinc-950 text-zinc-50 overflow-hidden">
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-zinc-800/40 via-zinc-950 to-zinc-950"></div>
        
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 sm:py-28 lg:py-32">
            <div class="text-center max-w-4xl mx-auto">
                {#if logoUrl}
                    <div class="mb-8">
                        <img src={logoUrl} alt={schoolName} class="h-20 sm:h-24 lg:h-28 mx-auto drop-shadow-xl hover:scale-105 transition-transform duration-300">
                    </div>
                {/if}
                <h1 class="text-4xl sm:text-5xl md:text-6xl lg:text-7xl font-bold font-heading mb-6 tracking-tight leading-tight">
                    <span class="bg-gradient-to-br from-white via-zinc-200 to-zinc-400 bg-clip-text text-transparent">
                        {schoolName}
                    </span>
                </h1>
                <p class="text-lg sm:text-xl md:text-2xl mb-8 text-zinc-400 font-medium tracking-wide">
                    Pusat Kegiatan Belajar Masyarakat
                </p>
                {#if vision}
                    <p class="text-base sm:text-lg text-zinc-400 leading-relaxed mb-10 px-4 max-w-3xl mx-auto font-light">
                        {vision}
                    </p>
                {/if}
                <div class="flex flex-col sm:flex-row gap-4 justify-center items-center px-4">
                    <a href="/program-pendidikan" class="group inline-flex items-center justify-center px-8 py-4 bg-zinc-100 text-zinc-900 font-semibold rounded-full shadow-lg hover:shadow-xl hover:bg-white transition-all duration-300 transform hover:-translate-y-0.5 w-full sm:w-auto">
                        Lihat Program
                        <svg class="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                        </svg>
                    </a>
                    <a href="/pendaftaran" class="group inline-flex items-center justify-center px-8 py-4 bg-amber-500 text-white font-semibold rounded-full shadow-lg shadow-amber-500/20 hover:shadow-amber-500/40 hover:bg-amber-400 transition-all duration-300 transform hover:-translate-y-0.5 w-full sm:w-auto">
                        Pendaftaran Siswa
                        <svg class="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </a>
                </div>
            </div>
        </div>
        <div class="absolute bottom-0 left-0 right-0 h-px bg-gradient-to-r from-transparent via-zinc-700 to-transparent"></div>
    </div>

    <!-- Programs Carousel/Grid -->
    {#if programs.length > 0}
    <div class="bg-zinc-900 overflow-hidden border-b border-zinc-200/50">
        <div class="relative w-full aspect-[16/9] sm:aspect-[21/9] lg:aspect-[3/1]">
            {#each programs as program, index}
                {#if activeSlide === index}
                <div class="absolute inset-0 w-full h-full transition-opacity duration-1000">
                    <img src={program.image_path ? `/storage/${program.image_path}` : 'https://placehold.co/1920x600/27272a/ffffff.png'} class="object-cover w-full h-full opacity-90" alt={program.name}>
                    <div class="absolute inset-0 bg-gradient-to-t from-zinc-950/95 via-zinc-950/50 to-transparent"></div>
                    <div class="absolute bottom-0 left-0 w-full px-6 py-12 sm:p-16 text-center sm:text-left text-white">
                        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                            <h3 class="text-3xl sm:text-4xl lg:text-5xl font-bold font-heading mb-4 drop-shadow-lg tracking-tight">{program.name}</h3>
                            <a href="/pendaftaran" class="inline-flex items-center justify-center px-6 py-3 bg-amber-500 text-white font-semibold rounded-full hover:bg-amber-400 transition-colors shadow-lg">
                                Daftar Sekarang
                                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                            </a>
                        </div>
                    </div>
                </div>
                {/if}
            {/each}
        </div>
    </div>
    {/if}

    <!-- Latest News -->
    {#if latestNews.length > 0}
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
        <div class="flex flex-col md:flex-row md:items-end justify-between mb-12 gap-6">
            <div class="max-w-2xl">
                <h2 class="text-3xl md:text-4xl font-bold font-heading text-zinc-900 tracking-tight">Berita Terbaru</h2>
                <p class="text-zinc-500 mt-4 text-lg">Ikuti perkembangan terbaru dan kegiatan menarik di PKBM kami.</p>
            </div>
            <a href="/berita" class="inline-flex items-center text-zinc-900 font-semibold hover:text-amber-600 transition-colors group">
                Lihat Semua
                <svg class="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                </svg>
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            {#each latestNews as article}
            <article class="group relative flex flex-col bg-white rounded-2xl ring-1 ring-zinc-200/50 shadow-sm hover:shadow-lg transition-all duration-300 overflow-hidden">
                <div class="aspect-[16/9] bg-zinc-100 overflow-hidden">
                    {#if article.featured_image_path}
                        <img src={`/storage/${article.featured_image_path}`} alt={article.title} class="object-cover w-full h-full group-hover:scale-105 transition-transform duration-500">
                    {:else}
                        <div class="w-full h-full flex items-center justify-center text-zinc-300">
                            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        </div>
                    {/if}
                </div>
                <div class="p-6 md:p-8 flex-1 flex flex-col">
                    <h3 class="text-xl font-bold font-heading leading-tight text-zinc-900 group-hover:text-amber-600 transition-colors line-clamp-2">
                        <a href={`/berita/${article.slug}`}>
                            <span class="absolute inset-0"></span>
                            {article.title}
                        </a>
                    </h3>
                    <p class="mt-4 text-zinc-600 line-clamp-3 text-sm leading-relaxed">
                        {article.excerpt || ''}
                    </p>
                </div>
            </article>
            {/each}
        </div>
    </section>
    {/if}

    <!-- CTA Section -->
    <div class="relative bg-zinc-950 text-white overflow-hidden py-24 sm:py-32 mt-auto">
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl sm:text-4xl md:text-5xl font-bold font-heading mb-6 tracking-tight">Bergabunglah Bersama Kami</h2>
            <p class="text-lg sm:text-xl text-zinc-400 mb-10 leading-relaxed max-w-2xl mx-auto font-light">
                Daftarkan diri Anda atau keluarga untuk mendapatkan pendidikan berkualitas dan terjangkau.
            </p>
            <a href="/pendaftaran" class="group inline-flex items-center justify-center px-8 py-4 bg-amber-500 text-white font-semibold rounded-full shadow-xl hover:bg-amber-400 transition-all duration-300 transform hover:-translate-y-0.5">
                Daftar Sekarang
                <svg class="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                </svg>
            </a>
        </div>
    </div>
</PublicLayout>
