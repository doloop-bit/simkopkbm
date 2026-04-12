<script context="module">
    import PublicLayout from "../../Layouts/PublicLayout.svelte";
    export const layout = PublicLayout;
</script>

<script>
    import PageHeader from "../../Components/PageHeader.svelte";
    import { Link } from "@inertiajs/svelte";
    let { schoolProfile, news } = $props();
</script>


    <PageHeader 
        title="Berita Terkini" 
        description="Ikuti terus perkembangan terbaru, pengumuman, dan artikel menarik seputar kegiatan sekolah."
        breadcrumb="Berita"
    />

    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            {#each news.data as article}
                <article
                    class="group relative flex flex-col bg-white rounded-2xl ring-1 ring-zinc-200/50 shadow-sm hover:shadow-lg transition-all duration-300 overflow-hidden"
                >
                    <div class="aspect-video bg-zinc-100 overflow-hidden">
                        {#if article.featured_image_path}
                            <img
                                src={`/storage/${article.featured_image_path}`}
                                alt={article.title}
                                class="object-cover w-full h-full group-hover:scale-105 transition-transform duration-500"
                            />
                        {/if}
                    </div>
                    <div class="p-6 md:p-8 flex-1 flex flex-col">
                        <h3
                            class="text-xl font-bold font-heading leading-tight text-zinc-900 group-hover:text-amber-600 transition-colors line-clamp-2"
                        >
                            <Link href={`/berita/${article.slug}`}>
                                <span class="absolute inset-0"></span>
                                {article.title}
                            </Link>
                        </h3>
                        <p
                            class="mt-4 text-zinc-600 line-clamp-3 text-sm leading-relaxed"
                        >
                            {article.excerpt || ""}
                        </p>
                    </div>
                </article>
            {/each}
        </div>
    </section>

