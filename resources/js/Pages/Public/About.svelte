<script module>
    import PublicLayout from "../../Layouts/PublicLayout.svelte";
    export const layout = PublicLayout;
</script>

<script>
    import PageHeader from "../../Components/PageHeader.svelte";
    import { fade, fly } from "svelte/transition";
    import { onMount } from "svelte";

    let { schoolProfile } = $props();
    let visible = $state(false);

    onMount(() => {
        visible = true;
    });

    const missionPoints = $derived(
        schoolProfile?.mission
            ? schoolProfile.mission.split("\n").filter((p) => p.trim())
            : [],
    );
    const historyItems = $derived(
        Array.isArray(schoolProfile?.history) ? schoolProfile.history : [],
    );
</script>

<PageHeader
    title="Tentang Kami"
    description="Mengenal lebih dekat visi, misi, dan perjalanan panjang lembaga pendidikan kami."
    breadcrumb="Tentang Kami"
/>

<div class="bg-white overflow-hidden">
    {#if visible}
        <!-- Hero: Logo, Name & Vision -->
        <section class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
            <div
                class="flex flex-col lg:flex-row items-center gap-12 lg:gap-16"
            >
                <!-- School Identity: Logo & Name Centered Together -->
                <div
                    in:fly={{ x: -20, duration: 1000 }}
                    class="flex flex-col items-center text-center lg:w-1/3"
                >
                    {#if schoolProfile?.logo_path}
                        <div
                            class="w-24 h-24 md:w-32 md:h-32 rounded-2xl bg-white shadow-lg p-5 border border-zinc-100 mb-6 transform hover:scale-105 transition-transform duration-500"
                        >
                            <img
                                src="/storage/{schoolProfile.logo_path}"
                                alt={schoolProfile.name}
                                class="w-full h-full object-contain"
                            />
                        </div>
                    {/if}
                    <h1 class="text-2xl md:text-3xl font-black text-zinc-900 tracking-tight leading-tight uppercase font-headline">
                    {schoolProfile?.name || "Sekolah Kami"}
                </h1>
                    <div class="mt-4 h-0.5 w-8 bg-amber-500 rounded-full"></div>
                </div>

                <!-- Vision: Reduced Font Size -->
                <div
                    in:fly={{ x: 20, duration: 1000, delay: 200 }}
                    class="lg:flex-1 lg:pl-20 lg:border-l border-zinc-100"
                >
                    <span
                        class="text-amber-600 font-bold uppercase tracking-widest text-[10px] mb-4 block"
                        >VISI LEMBAGA</span
                    >
                    <p
                        class="text-xl md:text-2xl text-zinc-600 font-medium leading-relaxed italic"
                    >
                        "{schoolProfile?.vision ||
                            "Mencetak generasi cerdas, mandiri, dan berakhlak mulia."}"
                    </p>
                </div>
            </div>
        </section>

        <!-- Full Width Values Section (Simplified - No Icons) -->
        <section class="bg-zinc-950 py-24 relative overflow-hidden">
            <div
                class="absolute top-0 right-0 w-1/3 h-full bg-gradient-to-l from-amber-500/5 to-transparent"
            ></div>

            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                <div class="text-center mb-16">
                    <h2 class="text-3xl font-black text-white mb-4 font-headline">
                    Nilai-Nilai Utama
                </h2>
                    <div
                        class="w-12 h-1 bg-amber-500 mx-auto rounded-full mb-4"
                    ></div>
                    <p
                        class="text-zinc-500 uppercase tracking-[0.4em] text-[10px]"
                    >
                        Pilar Karakter & Filosofi
                    </p>
                </div>

                <div
                    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4"
                >
                    {#each [{ title: "Integritas", desc: "Kejujuran dan ketulusan dalam setiap tindakan dan kata." }, { title: "Inovasi", desc: "Berani dalam mencoba hal baru untuk kemajuan layanan." }, { title: "Kepedulian", desc: "Membangun empati dan rasa tanggung jawab sosial." }, { title: "Keunggulan", desc: "Senantiasa berusaha mencapai hasil maksimal." }] as value, i}
                        <div
                            in:fly={{ y: 20, delay: 400 + i * 100 }}
                            class="group bg-zinc-900 border border-zinc-800 p-10 rounded-2xl hover:bg-amber-500/5 hover:border-amber-500/30 transition-all duration-500"
                        >
                            <h3
                                class="text-lg font-bold text-white mb-3 group-hover:text-amber-500 transition-colors uppercase tracking-widest font-headline"
                            >
                                {value.title}
                            </h3>
                            <p
                                class="text-zinc-500 text-sm leading-relaxed font-medium"
                            >
                                {value.desc}
                            </p>
                        </div>
                    {/each}
                </div>
            </div>
        </section>

        <!-- Mission Section -->
        <section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
            <div class="flex flex-col lg:flex-row gap-16">
                <div class="lg:w-1/3">
                    <h2 class="text-4xl font-extrabold text-zinc-900 mb-6 font-headline">
                    Misi Kami
                </h2>
                    <p class="text-zinc-500 leading-relaxed">
                        Langkah strategis yang kami tempuh setiap harinya untuk
                        mewujudkan visi besar sekolah.
                    </p>
                    <div class="mt-8 space-y-4">
                        <div class="h-1 w-20 bg-amber-500 rounded-full"></div>
                        <p
                            class="text-xs font-bold text-zinc-400 uppercase tracking-tighter"
                        >
                            Action Oriented Educational Approach
                        </p>
                    </div>
                </div>
                <div class="lg:w-2/3 grid grid-cols-1 md:grid-cols-2 gap-6">
                    {#each missionPoints as point, i}
                        <div
                            class="bg-zinc-50 p-8 rounded-2xl border border-zinc-100 flex gap-6 group hover:bg-white hover:shadow-xl transition-all duration-300"
                        >
                            <span
                                class="text-3xl font-black text-zinc-200 group-hover:text-amber-500 transition-colors"
                                >{String(i + 1).padStart(2, "0")}</span
                            >
                            <p
                                class="text-zinc-600 leading-relaxed font-semibold pt-1"
                            >
                                {point}
                            </p>
                        </div>
                    {/each}
                </div>
            </div>
        </section>

        <!-- History Timeline -->
        <section class="bg-zinc-900 py-24 relative overflow-hidden">
            <!-- Abstract Background -->
            <div
                class="absolute top-0 left-0 w-full h-full opacity-10 pointer-events-none"
            >
                <div
                    class="absolute top-0 right-0 w-[500px] h-[500px] bg-amber-500 rounded-full blur-[120px]"
                ></div>
                <div
                    class="absolute bottom-0 left-0 w-[500px] h-[500px] bg-zinc-500 rounded-full blur-[120px]"
                ></div>
            </div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                <div class="text-center mb-20" in:fade={{ duration: 1000 }}>
                    <span
                        class="text-amber-500 font-bold uppercase tracking-widest text-sm mb-4 block"
                        >REKAM JEJAK</span
                    >
                    <h2 class="text-4xl font-extrabold text-white mb-4 font-headline">
                        Sejarah & Perjalanan
                    </h2>
                    <div
                        class="w-20 h-1 bg-amber-500 mx-auto rounded-full"
                    ></div>
                </div>

                <div class="relative">
                    <!-- Vertical Line -->
                    <div
                        class="absolute left-1/2 -translate-x-1/2 top-0 bottom-0 w-px bg-zinc-800 hidden lg:block"
                    ></div>

                    <div class="space-y-12 lg:space-y-0">
                        {#each historyItems as item, i}
                            <div
                                class="relative flex flex-col lg:flex-row items-center"
                                in:fly={{ y: 30, delay: i * 150 }}
                            >
                                <!-- Center Dot (Absolute on Desktop) -->
                                <div
                                    class="absolute left-1/2 -translate-x-1/2 top-1/2 -translate-y-1/2 z-30 hidden lg:block"
                                >
                                    <div
                                        class="w-10 h-10 rounded-full bg-zinc-900 border-4 border-amber-500 flex items-center justify-center shadow-[0_0_20px_rgba(245,158,11,0.3)]"
                                    >
                                        <div
                                            class="w-2 h-2 rounded-full bg-amber-500 animate-ping"
                                        ></div>
                                    </div>
                                </div>

                                {#if i % 2 === 0}
                                    <!-- Left Content -->
                                    <div
                                        class="w-full lg:w-1/2 lg:pr-20 lg:text-right"
                                    >
                                        <div
                                            class="bg-zinc-800/50 backdrop-blur-sm p-8 rounded-[32px] border border-zinc-700 hover:border-amber-500/50 transition-colors group/card"
                                        >
                                            {#if item.image_path}
                                                <div
                                                    class="mb-6 overflow-hidden rounded-2xl aspect-video lg:aspect-[16/6]"
                                                >
                                                    <img
                                                        src="/storage/{item.image_path}"
                                                        alt={item.title}
                                                        class="w-full h-full object-cover group-hover/card:scale-110 transition-transform duration-700"
                                                    />
                                                </div>
                                            {/if}
                                            <span
                                                class="text-amber-500 font-black text-2xl mb-2 block"
                                                >{item.year}</span
                                            >
                                            <h3
                                                class="text-xl font-bold text-white mb-4"
                                            >
                                                {item.title}
                                            </h3>
                                            <p
                                                class="text-zinc-400 leading-relaxed"
                                            >
                                                {item.description}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="hidden lg:block lg:w-1/2"></div>
                                {:else}
                                    <!-- Right Content -->
                                    <div class="hidden lg:block lg:w-1/2"></div>
                                    <div class="w-full lg:w-1/2 lg:pl-20">
                                        <div
                                            class="bg-zinc-800/50 backdrop-blur-sm p-8 rounded-2xl border border-zinc-700 hover:border-amber-500/50 transition-colors group/card"
                                        >
                                            {#if item.image_path}
                                                <div
                                                    class="mb-6 overflow-hidden rounded-2xl aspect-video lg:aspect-[16/6]"
                                                >
                                                    <img
                                                        src="/storage/{item.image_path}"
                                                        alt={item.title}
                                                        class="w-full h-full object-cover group-hover/card:scale-110 transition-transform duration-700"
                                                    />
                                                </div>
                                            {/if}
                                            <span
                                                class="text-amber-500 font-black text-2xl mb-2 block"
                                                >{item.year}</span
                                            >
                                            <h3
                                                class="text-xl font-bold text-white mb-4"
                                            >
                                                {item.title}
                                            </h3>
                                            <p
                                                class="text-zinc-400 leading-relaxed"
                                            >
                                                {item.description}
                                            </p>
                                        </div>
                                    </div>
                                {/if}
                            </div>
                        {:else}
                            <div class="text-center py-20 text-zinc-500 italic">
                                Data sejarah belum tersedia...
                            </div>
                        {/each}
                    </div>
                </div>
            </div>
        </section>
    {/if}
</div>
