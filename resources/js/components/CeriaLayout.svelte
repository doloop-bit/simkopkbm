<script>
    import { onMount } from 'svelte';
    import { fade, fly } from 'svelte/transition';

    let { children, programName = 'PAUD Ceria', programLogo = null } = $props();

    let isScrolled = $state(false);
    let mobileMenuOpen = $state(false);

    onMount(() => {
        const handleScroll = () => {
            isScrolled = window.scrollY > 20;
        };
        
        // PAUD Specific Font
        const originalFont = document.body.style.fontFamily;
        document.body.style.fontFamily = "'Quicksand', sans-serif";
        
        window.addEventListener('scroll', handleScroll);
        
        return () => {
            window.removeEventListener('scroll', handleScroll);
            document.body.style.fontFamily = originalFont;
        };
    });

    const navLinks = [
        { name: 'Tentang', href: '#about' },
        { name: 'Kurikulum', href: '#curriculum' },
        { name: 'Fasilitas', href: '#facilities' },
        { name: 'Kontak', href: '#contact' },
    ];
</script>

<div class="min-h-screen bg-sky-50 text-slate-800 font-body selection:bg-rose-400 selection:text-white overflow-x-hidden pt-20">
    <!-- Playful Background Blobs -->
    <div class="fixed top-0 -left-20 w-[400px] h-[400px] bg-yellow-400/10 blur-[80px] rounded-full pointer-events-none z-0"></div>
    <div class="fixed bottom-0 -right-20 w-[400px] h-[400px] bg-rose-400/10 blur-[80px] rounded-full pointer-events-none z-0"></div>
    <div class="fixed top-1/2 left-1/2 -translate-x-1/2 w-[600px] h-[600px] bg-sky-400/10 blur-[100px] rounded-full pointer-events-none z-0"></div>

    <!-- Navigation -->
    <nav class="fixed top-0 w-full z-50 transition-all duration-300 {isScrolled ? 'bg-white/80 backdrop-blur-md py-3 shadow-lg shadow-sky-100/50' : 'bg-transparent py-6'}">
        <div class="max-w-7xl mx-auto px-6 flex justify-between items-center">
            <div class="flex items-center gap-3 group cursor-pointer">
                <div class="w-12 h-12 bg-white rounded-2xl shadow-md flex items-center justify-center overflow-hidden border-2 border-white group-hover:rotate-6 transition-transform">
                    {#if programLogo}
                        <img src={programLogo} alt={programName} class="w-full h-full object-contain p-1" />
                    {:else}
                        <span class="text-2xl">🏫</span>
                    {/if}
                </div>
                <span class="text-2xl font-black text-sky-600 tracking-tight">{programName}</span>
            </div>

            <!-- Desktop Menu -->
            <div class="hidden lg:flex items-center gap-8">
                {#each navLinks as link}
                    <a href={link.href} class="font-bold text-slate-600 hover:text-sky-500 transition-colors uppercase text-sm tracking-widest">{link.name}</a>
                {/each}
                <button class="px-8 py-3 rounded-full bg-rose-500 text-white font-bold shadow-lg shadow-rose-200 hover:scale-105 active:scale-95 transition-all">
                    Daftar Sekarang
                </button>
            </div>

            <!-- Mobile Toggle -->
            <button 
                class="lg:hidden w-12 h-12 flex items-center justify-center text-sky-600"
                onclick={() => mobileMenuOpen = !mobileMenuOpen}
            >
                <span class="material-symbols-outlined text-3xl">menu</span>
            </button>
        </div>
    </nav>

    <!-- Mobile Menu -->
    {#if mobileMenuOpen}
        <div 
            class="fixed inset-0 z-[100] bg-white flex flex-col items-center justify-center gap-8 p-6 lg:hidden"
            transition:fade={{ duration: 300 }}
        >
            <button 
                class="absolute top-6 right-6 text-slate-400"
                onclick={() => mobileMenuOpen = false}
            >
                <span class="material-symbols-outlined text-4xl">close</span>
            </button>
            
            <div class="flex items-center gap-3 mb-8">
                <div class="w-16 h-16 bg-sky-50 rounded-2xl flex items-center justify-center">
                    <span class="text-3xl">⭐</span>
                </div>
                <span class="text-3xl font-black text-sky-600">{programName}</span>
            </div>

            {#each navLinks as link}
                <a 
                    href={link.href} 
                    class="text-3xl font-black text-slate-700 hover:text-sky-500"
                    onclick={() => mobileMenuOpen = false}
                >
                    {link.name}
                </a>
            {/each}

            <button class="w-full max-w-xs py-5 rounded-[32px] bg-rose-500 text-white text-xl font-bold shadow-xl shadow-rose-200 mt-4">
                Daftar Sekarang ✨
            </button>
        </div>
    {/if}

    <main class="relative z-10">
        {@render children()}
    </main>

    <!-- Footer -->
    <footer class="bg-white py-16 border-t border-sky-100 flex flex-col items-center gap-6 relative z-10">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-sky-50 rounded-xl flex items-center justify-center">
                <span class="text-xl">🏫</span>
            </div>
            <span class="text-xl font-black text-sky-600">{programName}</span>
        </div>
        <p class="font-bold text-slate-400">© 2026 {programName}. Built with 💖 for happy kids.</p>
        <div class="flex gap-6 mt-2">
            <a href="/" class="w-10 h-10 rounded-full bg-sky-50 flex items-center justify-center text-sky-400 hover:bg-sky-500 hover:text-white transition-all">
                <span class="material-symbols-outlined text-xl">share</span>
            </a>
            <a href="/" class="w-10 h-10 rounded-full bg-sky-50 flex items-center justify-center text-sky-400 hover:bg-sky-500 hover:text-white transition-all">
                <span class="material-symbols-outlined text-xl">language</span>
            </a>
        </div>
    </footer>
</div>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Quicksand:wght@300..700&display=swap');
    
    :global(body) {
        font-family: 'Quicksand', sans-serif;
    }
</style>
