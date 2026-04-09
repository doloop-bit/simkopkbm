<script>
    import Navbar from '../Components/Navbar.svelte';
    import Footer from '../Components/Footer.svelte';
    import WhatsAppButton from '../Components/WhatsAppButton.svelte';
    
    let { children, schoolProfile, currentRoute } = $props();
</script>

<div class="min-h-screen flex flex-col">
    <Navbar {schoolProfile} {currentRoute} />
    
    <main class="flex-grow relative z-10">
        {@render children()}
    </main>
    
    <Footer {schoolProfile} />
    
    <WhatsAppButton {schoolProfile} />
</div>
