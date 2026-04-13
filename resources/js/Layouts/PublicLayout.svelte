<script>
    import Navbar from "../Components/Navbar.svelte";
    import Footer from "../Components/Footer.svelte";
    import WhatsAppButton from "../Components/WhatsAppButton.svelte";
    import { onMount } from "svelte";
    import { usePage } from "@inertiajs/svelte";

    let { children, schoolProfile, currentRoute } = $props();
    const page = usePage();

    // Automatically determine currentRoute from page component name if not provided
    // e.g. "Public/Home" -> "Home"
    const activeRoute = $derived(
        currentRoute || page.component.split("/").pop(),
    );

    onMount(() => {
        // Enforce light mode on public site
        document.documentElement.classList.remove("dark");
    });

    // Hide Navbar/Footer for special landing pages
    const isCustomLanding = $derived(
        page.props.program?.slug === "paud" ||
            page.props.program?.slug === "paket-a",
    );
</script>

<div class="min-h-screen flex flex-col">
    {#if !isCustomLanding}
        <Navbar {schoolProfile} currentRoute={activeRoute} />
    {/if}

    <main class="grow relative z-10 {isCustomLanding ? '' : 'bg-sky-50'}">
        {@render children()}
    </main>

    {#if !isCustomLanding}
        <Footer {schoolProfile} />
        <WhatsAppButton {schoolProfile} />
    {/if}
</div>
