<script module>
    import PublicLayout from "../../Layouts/PublicLayout.svelte";

    /**
     * Dynamically determine the layout.
     * For PAUD and Paket A, we use their internal standalone layouts (Ceria/Elegant).
     * For all others, we wrap them in the standard PublicLayout.
     */
    export const layout = (page) => {
        const standaloneSlugs = ["paud", "paketa"];

        if (
            page.props.program &&
            standaloneSlugs.includes(page.props.program.slug)
        ) {
            return page;
        }

        return [PublicLayout, page];
    };
</script>

<script>
    import PageHeader from "../../Components/PageHeader.svelte";
    import PAUDLanding from "./Landing/paud.svelte";
    import PaketALanding from "./Landing/paketa.svelte";

    let { schoolProfile, program } = $props();

    // Mapping slug to specific components
    const customLandings = {
        paud: PAUDLanding,
        paketa: PaketALanding,
    };

    // Determine which component to use
    const CustomLanding = $derived(customLandings[program.slug]);
    const logoUrl = $derived(
        program.logo_path ? `/storage/${program.logo_path}` : null,
    );
</script>

{#if CustomLanding}
    <!-- Render custom landing page for specific programs -->
    <CustomLanding programName={program.name} programLogo={logoUrl} />
{:else}
    <!-- Default fallback layout for all other programs -->

    <PageHeader
        title={program.name}
        description={program.level?.name || "Program Unggulan"}
        breadcrumb="Detail Program"
    />

    <section class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-24 text-center">
        <div
            class="w-32 h-32 rounded-3xl bg-zinc-900 text-white flex items-center justify-center mb-12 shadow-xl mx-auto overflow-hidden ring-4 ring-white"
        >
            {#if program.image_path}
                <img
                    src={`/storage/${program.image_path}`}
                    alt={program.name}
                    class="w-full h-full object-cover rounded-3xl"
                />
            {:else}
                <span class="text-4xl font-bold font-heading"
                    >{program.name[0]}</span
                >
            {/if}
        </div>
        <div
            class="prose max-w-none text-zinc-600 text-lg leading-relaxed text-left"
        >
            {program.description}
        </div>
        <div class="mt-12">
            <a
                href="/pendaftaran"
                class="inline-flex items-center px-8 py-4 bg-amber-500 text-white font-bold rounded-full shadow-lg hover:bg-amber-400 transition-all duration-300 transform hover:-translate-y-1"
            >
                Daftar Program Ini Sekarang
                <svg
                    class="w-4 h-4 ml-2"
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
            </a>
        </div>
    </section>
{/if}
