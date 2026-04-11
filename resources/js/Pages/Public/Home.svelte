<script>
    import PublicLayout from "../../Layouts/PublicLayout.svelte";
    import Button from "../../Components/Button.svelte";
    import { onMount } from "svelte";
    import { router, Link } from "@inertiajs/svelte";

    let {
        schoolProfile,
        latestNews = [],
        programs = [],
        featuredPhotos = [],
    } = $props();

    let activeSlide = $state(0);
    let scrolled = $state(false);

    onMount(() => {
        window.addEventListener("scroll", () => {
            scrolled = window.scrollY > 50;
        });

        if (programs.length > 1) {
            const interval = setInterval(() => {
                activeSlide = (activeSlide + 1) % programs.length;
            }, 6000);
            return () => clearInterval(interval);
        }
    });

    const schoolName = $derived(schoolProfile?.name || "SIMKOPKBM");
    const vision = $derived(
        schoolProfile?.vision ||
            "Mewujudkan masyarakat cerdas, terampil, dan mandiri melalui pendidikan berkualitas.",
    );
    const logoUrl = $derived(
        schoolProfile?.logo_path ? `/storage/${schoolProfile.logo_path}` : null,
    );

    const stats = [
        {
            label: "Siswa Aktif",
            value: "180+",
            icon: "M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z",
        },
        {
            label: "Alumni Sukses",
            value: "50+",
            icon: "M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4",
        },
        {
            label: "Program Unggulan",
            value: "4+",
            icon: "M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253",
        },
        {
            label: "Tahun Berdiri",
            value: "2022",
            icon: "M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z",
        },
    ];
</script>

<PublicLayout {schoolProfile} currentRoute="Home">
    <!-- Hero Section with Motion -->
    <section
        class="relative min-h-[90vh] flex items-center overflow-hidden bg-slate-950"
    >
        <!-- Dynamic Background -->
        <div class="absolute inset-0 z-0">
            <div
                class="absolute inset-0 bg-linear-to-tr from-slate-950 via-slate-900 to-amber-900/20 opacity-90"
            ></div>
            {#if programs.length > 0 && programs[activeSlide]?.image_path}
                <img
                    src={`/storage/${programs[activeSlide].image_path}`}
                    class="w-full h-full object-cover transition-opacity duration-1000 blur-sm opacity-30 scale-105"
                    alt="Background"
                />
            {:else}
                <div
                    class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/dark-matter.png')] opacity-20"
                ></div>
            {/if}

            <!-- Animated Orbs -->
            <div
                class="absolute top-1/4 -left-20 w-96 h-96 bg-amber-500/10 rounded-full blur-[120px] animate-pulse"
            ></div>
            <div
                class="absolute bottom-1/4 -right-20 w-96 h-96 bg-amber-500/5 rounded-full blur-[120px] animate-pulse [animation-delay:2s]"
            ></div>
        </div>

        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
            <div class="grid lg:grid-cols-2 gap-16 items-center">
                <div class="space-y-8 text-center lg:text-left">
                    <div
                        class="inline-flex items-center px-4 py-2 rounded-full bg-amber-500/10 border border-amber-500/20 text-amber-500 text-xs font-bold tracking-widest uppercase animate-in fade-in slide-in-from-left-4 duration-1000"
                    >
                        Selamat Datang di {schoolName}
                    </div>

                    <h1
                        class="text-5xl sm:text-6xl lg:text-7xl font-bold font-heading text-white tracking-tight leading-[1.1] animate-in fade-in slide-in-from-left-8 duration-1000 [animation-delay:200ms]"
                    >
                        Beriman, Cerdas, <br />
                        <span
                            class="text-transparent bg-clip-text bg-linear-to-r from-amber-400 to-amber-600"
                            >Berkarakter dan Mandiri</span
                        >
                    </h1>

                    <p
                        class="text-xl text-slate-400 leading-relaxed max-w-xl mx-auto lg:mx-0 animate-in fade-in slide-in-from-left-10 duration-1000 [animation-delay:400ms]"
                    >
                        {vision}
                    </p>

                    <div
                        class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start pt-4 animate-in fade-in slide-in-from-left-12 duration-1000 [animation-delay:600ms]"
                    >
                        <Button
                            onclick={() => router.visit("/pendaftaran")}
                            size="lg"
                            class="px-12 group"
                        >
                            Mulai Pendaftaran
                            <svg
                                class="size-5 group-hover:translate-x-1 transition-transform"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                                ><path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M17 8l4 4m0 0l-4 4m4-4H3"
                                /></svg
                            >
                        </Button>
                        <Button
                            variant="outline"
                            onclick={() => router.visit("/program-pendidikan")}
                            size="lg"
                            class="border-slate-700 text-white hover:border-amber-500 hover:bg-amber-500/10"
                        >
                            Jelajahi Program
                        </Button>
                    </div>
                </div>

                <!-- Floating Featured Card -->
                <div
                    class="hidden lg:block relative animate-in fade-in zoom-in duration-1000 [animation-delay:800ms]"
                >
                    <div
                        class="relative z-20 bg-slate-900/40 backdrop-blur-3xl border border-white/10 p-2 rounded-[2.5rem] shadow-2xl"
                    >
                        <div
                            class="overflow-hidden rounded-4xl aspect-4/5 relative group"
                        >
                            {#if programs.length > 0}
                                <img
                                    src={programs[activeSlide]?.image_path
                                        ? `/storage/${programs[activeSlide].image_path}`
                                        : "https://placehold.co/800x1000/1e293b/ffffff"}
                                    class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110"
                                    alt="Featured"
                                />
                                <div
                                    class="absolute inset-x-0 bottom-0 p-8 bg-linear-to-t from-slate-950 via-slate-900/40 to-transparent"
                                >
                                    <h3
                                        class="text-2xl font-bold text-white mb-2"
                                    >
                                        {programs[activeSlide]?.name}
                                    </h3>
                                    <p
                                        class="text-amber-500 text-sm font-bold uppercase tracking-widest"
                                    >
                                        Program Unggulan
                                    </p>
                                </div>
                            {/if}
                        </div>
                    </div>
                    <!-- Decorative back layers -->
                    <div
                        class="absolute top-10 -right-10 w-full h-full bg-amber-500/5 rounded-[2.5rem] -z-10 animate-pulse"
                    ></div>
                    <div
                        class="absolute -bottom-10 -left-10 w-full h-full border border-white/5 rounded-[2.5rem] -z-20"
                    ></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Bar -->
    <div class="relative z-20 -mt-16 max-w-6xl mx-auto px-4">
        <div
            class="grid grid-cols-2 lg:grid-cols-4 bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-2xl border border-slate-100 dark:border-slate-800 divide-x divide-slate-100 dark:divide-slate-800 overflow-hidden"
        >
            {#each stats as stat}
                <div
                    class="p-8 text-center hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors group"
                >
                    <div
                        class="w-12 h-12 bg-amber-500/10 rounded-2xl flex items-center justify-center text-amber-500 mx-auto mb-4 group-hover:scale-110 transition-transform"
                    >
                        <svg
                            class="size-6"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d={stat.icon}
                            />
                        </svg>
                    </div>
                    <div
                        class="text-2xl font-bold text-slate-900 dark:text-white mb-1 leading-none"
                    >
                        {stat.value}
                    </div>
                    <div
                        class="text-[10px] font-bold text-slate-400 uppercase tracking-widest"
                    >
                        {stat.label}
                    </div>
                </div>
            {/each}
        </div>
    </div>

    <!-- Featured Programs Grid -->
    {#if programs.length > 0}
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-32">
            <div
                class="flex flex-col md:flex-row items-end justify-between mb-16 gap-8"
            >
                <div class="max-w-2xl">
                    <div
                        class="text-amber-500 text-xs font-bold uppercase tracking-[0.3em] mb-4"
                    >
                        Kurikulum & Kompetensi
                    </div>
                    <h2
                        class="text-4xl font-bold font-heading text-slate-900 dark:text-white tracking-tight leading-tight"
                    >
                        Program Pendidikan <br /><span class="text-amber-500"
                            >Pilihan Terbaik</span
                        >
                    </h2>
                </div>
                <Button
                    variant="ghost"
                    onclick={() =>
                        (window.location.href = "/program-pendidikan")}
                    class="group"
                >
                    Lihat Semua
                    <svg
                        class="size-4 group-hover:translate-x-1 transition-transform"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                        ><path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M17 8l4 4m0 0l-4 4m4-4H3"
                        /></svg
                    >
                </Button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                {#each programs.slice(0, 3) as program}
                    <div
                        class="group relative bg-white dark:bg-slate-900 rounded-4xl overflow-hidden border border-slate-100 dark:border-slate-800 shadow-sm hover:shadow-2xl transition-all duration-500 hover:-translate-y-2"
                    >
                        <div
                            class="aspect-4/3 overflow-hidden grayscale group-hover:grayscale-0 transition-all duration-700"
                        >
                            <img
                                src={program.image_path
                                    ? `/storage/${program.image_path}`
                                    : "https://placehold.co/600x400/f8fafc/cbd5e1"}
                                class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110"
                                alt={program.name}
                            />
                        </div>
                        <div class="p-8">
                            <h3
                                class="text-xl font-bold text-slate-900 dark:text-white mb-3 tracking-tight group-hover:text-amber-600 transition-colors uppercase leading-none"
                            >
                                {program.name}
                            </h3>
                            <div
                                class="w-10 h-1 bg-amber-500/20 mb-6 group-hover:w-20 transition-all"
                            ></div>
                            <Button
                                variant="outline"
                                size="sm"
                                class="w-full"
                                onclick={() =>
                                    router.visit(
                                        `/program-pendidikan#${program.id}`,
                                    )}
                            >
                                Detail Program
                            </Button>
                        </div>
                    </div>
                {/each}
            </div>
        </section>
    {/if}

    <!-- Latest News Masonry -->
    {#if latestNews.length > 0}
        <section
            class="bg-slate-50 dark:bg-slate-950 py-32 border-y border-slate-100 dark:border-slate-900"
        >
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-20">
                    <div
                        class="text-amber-500 text-xs font-bold uppercase tracking-[0.3em] mb-4"
                    >
                        Update & Wawasan
                    </div>
                    <h2
                        class="text-4xl font-bold font-heading text-slate-900 dark:text-white tracking-tight"
                    >
                        Kabar Terbaru
                    </h2>
                </div>

                <div
                    class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-10"
                >
                    {#each latestNews.slice(0, 3) as article}
                        <article class="group flex flex-col">
                            <div
                                class="aspect-video rounded-3xl overflow-hidden mb-6 shadow-lg"
                            >
                                <img
                                    src={article.featured_image_path
                                        ? `/storage/${article.featured_image_path}`
                                        : "https://placehold.co/1280x720/e2e8f0/64748b"}
                                    class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105"
                                    alt={article.title}
                                />
                            </div>
                            <div class="space-y-4">
                                <div
                                    class="flex items-center gap-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest leading-none"
                                >
                                    <span class="text-amber-500">Aktivitas</span
                                    >
                                    <span
                                        class="w-1 h-1 bg-slate-300 rounded-full"
                                    ></span>
                                    <span
                                        >{new Date().toLocaleDateString(
                                            "id-ID",
                                            {
                                                year: "numeric",
                                                month: "long",
                                                day: "numeric",
                                            },
                                        )}</span
                                    >
                                </div>
                                <h3
                                    class="text-xl font-bold text-slate-900 dark:text-white tracking-tight leading-tight group-hover:text-amber-600 transition-colors"
                                >
                                    <Link href={`/berita/${article.slug}`}
                                        >{article.title}</Link
                                    >
                                </h3>
                                <p
                                    class="text-slate-500 text-sm line-clamp-2 leading-relaxed font-medium"
                                >
                                    {article.excerpt ||
                                        "Pelajari selengkapnya tentang berita dan kegiatan terbaru di sekolah kami melalui artikel ini."}
                                </p>
                            </div>
                        </article>
                    {/each}
                </div>
            </div>
        </section>
    {/if}

    <!-- Premium CTA -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-32">
        <div
            class="relative rounded-[3rem] overflow-hidden bg-slate-900 p-12 sm:p-24 text-center"
        >
            <div class="absolute inset-0 z-0">
                <div
                    class="absolute inset-0 bg-linear-to-br from-amber-600/20 to-slate-900/60 mix-blend-overlay"
                ></div>
                <div class="absolute top-0 right-0 p-20 opacity-10">
                    <svg
                        class="size-64 text-amber-500"
                        fill="currentColor"
                        viewBox="0 0 24 24"
                        ><path
                            d="M12 2L4.5 20.29l.71.71L12 18l6.79 3 .71-.71L12 2z"
                        /></svg
                    >
                </div>
            </div>

            <div class="relative z-10 max-w-2xl mx-auto">
                <h2
                    class="text-3xl sm:text-4xl md:text-5xl font-bold font-heading text-white mb-8 leading-tight tracking-tight"
                >
                    Siap Membangun <br />Masa Depan Lebih Cerah?
                </h2>
                <p class="text-slate-400 text-lg mb-12 font-medium">
                    Dapatkan pengalaman belajar yang transformatif. Pendaftaran
                    untuk tahun ajaran baru telah dibuka.
                </p>
                <Button
                    variant="primary"
                    size="lg"
                    onclick={() => router.visit("/pendaftaran")}
                    class="px-12"
                >
                    Daftar Sekarang
                </Button>
            </div>
        </div>
    </section>
</PublicLayout>
