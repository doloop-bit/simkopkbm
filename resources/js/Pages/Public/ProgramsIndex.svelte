<script context="module">
    import PublicLayout from "../../Layouts/PublicLayout.svelte";
    export const layout = PublicLayout;
</script>

<script>
    import PageHeader from "../../Components/PageHeader.svelte";
    import { Link } from "@inertiajs/svelte";
    let { schoolProfile, programs } = $props();
</script>


    <PageHeader 
        title="Program Pendidikan" 
        description="Pilih jalur pendidikan yang tepat untuk masa depan Anda. Kami menyediakan berbagai tingkatan program berkualitas."
        breadcrumb="Program"
    />

    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-x-10 gap-y-24">
            {#each programs as program}
                <div
                    class="group relative bg-white rounded-4xl shadow-[0_20px_50px_rgba(0,0,0,0.04)] border border-white/50 hover:shadow-2xl transition-all duration-500 hover:-translate-y-2 flex flex-col"
                >
                    <!-- Image Wrapper -->
                    <div
                        class="relative aspect-4/3 overflow-hidden bg-zinc-100 rounded-t-4xl"
                    >
                        {#if program.image_path}
                            <img
                                src={`/storage/${program.image_path}`}
                                alt={program.name}
                                class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110 grayscale group-hover:grayscale-0"
                            />
                        {:else}
                            <div
                                class="w-full h-full bg-linear-to-br from-zinc-100 to-zinc-200 flex items-center justify-center"
                            >
                                <span
                                    class="text-6xl font-bold text-zinc-300 font-heading"
                                    >{program.name[0]}</span
                                >
                            </div>
                        {/if}

                        <!-- Overlay Gradient -->
                        <div
                            class="absolute inset-0 bg-linear-to-t from-zinc-900/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"
                        ></div>

                        <!-- Floating Info (Bottom Left) -->
                        <div
                            class="absolute bottom-4 left-4 px-3 py-1 bg-white/20 backdrop-blur-md border border-white/30 rounded-full text-[10px] font-bold text-white uppercase tracking-widest opacity-0 group-hover:opacity-100 transition-opacity duration-300"
                        >
                            Pendaftaran Dibuka
                        </div>
                    </div>

                    <!-- Content Area -->
                    <div class="p-8 pt-10 flex-1 flex flex-col">
                        <div class="mb-4">
                            <h3
                                class="text-2xl font-bold text-zinc-900 font-heading tracking-tight group-hover:text-amber-600 transition-colors"
                            >
                                {program.name}
                            </h3>
                        </div>

                        <p
                            class="text-zinc-500 text-sm line-clamp-3 leading-relaxed mb-8 flex-1"
                        >
                            {program.description}
                        </p>

                        <Link
                            href={['paud', 'paket-a'].includes(program.slug) ? `/${program.slug}` : `/program-pendidikan/${program.slug}`}
                            class="inline-flex items-center justify-center w-full px-6 py-3.5 bg-zinc-900 text-white rounded-2xl font-bold text-sm tracking-wide group-hover:bg-amber-500 transition-all duration-300 shadow-lg group-hover:shadow-amber-500/25"
                        >
                            Lihat Detail Program
                            <svg
                                class="w-4 h-4 ml-2 transition-transform duration-300 group-hover:translate-x-1"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M14 5l7 7m0 0l-7 7m7-7H3"
                                />
                            </svg>
                        </Link>
                    </div>

                    <!-- Logo Badge (Top Overlap) -->
                    {#if program.logo_path}
                        <div
                            class="absolute -top-12 left-1/2 -translate-x-1/2 w-24 h-24 z-30"
                        >
                            <div
                                class="w-full h-full bg-white ring-white rounded-4xl p-4 shadow-[0_20px_50px_rgba(0,0,0,0.15)] transition-all duration-700 group-hover:scale-110 group-hover:shadow-amber-500/30"
                            >
                                <img
                                    src={`/storage/${program.logo_path}`}
                                    alt="Logo"
                                    class="w-full h-full object-contain"
                                />
                            </div>
                        </div>
                    {/if}
                </div>
            {/each}
        </div>
    </section>

