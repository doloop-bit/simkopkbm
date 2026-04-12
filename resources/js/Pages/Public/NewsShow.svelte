<script context="module">
    import PublicLayout from "../../Layouts/PublicLayout.svelte";
    export const layout = PublicLayout;
</script>

<script>
    import PageHeader from "../../Components/PageHeader.svelte";
    let { schoolProfile, article } = $props();
</script>


    <PageHeader 
        title={article.title} 
        description={`${new Date(article.published_at).toLocaleDateString("id-ID", { day: 'numeric', month: 'long', year: 'numeric' })} • ${article.author?.name || 'Administrator'}`}
        breadcrumb="Detail Berita"
    />

    <article class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
        {#if article.featured_image_path}
            <img
                src={`/storage/${article.featured_image_path}`}
                alt={article.title}
                class="w-full h-auto aspect-video object-cover rounded-3xl shadow-xl mb-12"
            />
        {/if}

        <div class="prose max-w-none text-zinc-600 text-lg leading-relaxed">
            {@html article.content}
        </div>
    </article>

