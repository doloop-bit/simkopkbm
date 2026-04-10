<script>
    import PublicLayout from "../../Layouts/PublicLayout.svelte";
    let { schoolProfile, article } = $props();
</script>

<PublicLayout {schoolProfile} currentRoute="NewsShow">
    <article class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
        {#if article.featured_image_path}
            <img
                src={`/storage/${article.featured_image_path}`}
                alt={article.title}
                class="w-full h-auto aspect-video object-cover rounded-3xl shadow-xl mb-12"
            />
        {/if}

        <header class="mb-12">
            <h1
                class="text-4xl md:text-5xl font-bold font-heading text-zinc-900 tracking-tight leading-tight mb-4"
            >
                {article.title}
            </h1>
            <div class="flex items-center text-zinc-500 text-sm">
                <time datetime={article.published_at}
                    >{new Date(article.published_at).toLocaleDateString(
                        "id-ID",
                        { day: "numeric", month: "long", year: "numeric" },
                    )}</time
                >
                <span class="mx-2">•</span>
                <span>{article.author?.name || "Administrator"}</span>
            </div>
        </header>

        <div class="prose max-w-none text-zinc-600 text-lg leading-relaxed">
            {@html article.content}
        </div>
    </article>
</PublicLayout>
