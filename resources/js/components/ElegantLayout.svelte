<script>
    import { onMount } from 'svelte';
    import { fade, fly } from 'svelte/transition';

    let { children, programName = 'Academic Elegance', programLogo = null } = $props();

    let isScrolled = $state(false);
    let mobileMenuOpen = $state(false);

    onMount(() => {
        const handleScroll = () => {
            isScrolled = window.scrollY > 20;
        };
        window.addEventListener('scroll', handleScroll);
        return () => window.removeEventListener('scroll', handleScroll);
    });

    const navLinks = [
        { name: 'Home', href: '#' },
        { name: 'Profile', href: '#' },
        { name: 'Programs', href: '#' },
        { name: 'Facilities', href: '#' },
        { name: 'News', href: '#' },
        { name: 'Contact', href: '#' },
    ];
</script>

<div class="min-h-screen bg-elegant-surface text-elegant-on-surface font-body selection:bg-elegant-accent selection:text-white islamic-pattern pt-20">
    <!-- Top Navigation Bar -->
    <nav class="fixed top-0 w-full z-50 transition-all duration-300 font-headline tracking-tight {isScrolled ? 'bg-white/95 dark:bg-slate-900/95 py-3 shadow-md backdrop-blur-lg' : 'bg-transparent py-5'}">
        <div class="max-w-7xl mx-auto px-6 md:px-12 flex justify-between items-center">
            <div class="text-2xl font-extrabold text-elegant-primary dark:text-white uppercase tracking-tighter flex items-center gap-2">
                {#if programLogo}
                    <div class="w-10 h-10 rounded-full bg-elegant-accent-light flex items-center justify-center overflow-hidden border border-elegant-accent/20">
                        <img src={programLogo} alt={programName} class="w-full h-full object-contain p-1" />
                    </div>
                {/if}
                <span class="hidden sm:inline">{programName.split(' ')[0]} <span class="text-elegant-accent">{programName.split(' ').slice(1).join(' ')}</span></span>
                <span class="sm:hidden text-elegant-primary text-lg">Academic <span class="text-elegant-accent">Elegance</span></span>
            </div>

            <div class="hidden lg:flex items-center gap-8">
                {#each navLinks as link}
                    <a 
                        class="text-slate-600 dark:text-slate-300 font-medium hover:text-elegant-accent dark:hover:text-elegant-accent transition-all duration-300 relative group" 
                        href={link.href}
                    >
                        {link.name}
                        <span class="absolute -bottom-1 left-0 w-0 h-0.5 bg-elegant-accent transition-all duration-300 group-hover:w-full"></span>
                    </a>
                {/each}
            </div>

            <div class="flex items-center gap-4">
                <button class="hidden sm:block elegant-btn-primary !px-6 !py-2.5 !text-sm">
                    Register
                </button>
                <button 
                    class="p-2 -mr-2 text-elegant-primary dark:text-white lg:hidden"
                    onclick={() => mobileMenuOpen = !mobileMenuOpen}
                >
                    <span class="material-symbols-outlined text-3xl">menu</span>
                </button>
            </div>
        </div>
    </nav>

    <!-- Mobile Menu Overlay -->
    {#if mobileMenuOpen}
        <div 
            class="fixed inset-0 z-[100] bg-white dark:bg-slate-950 p-6 flex flex-col items-center justify-center gap-8 lg:hidden"
            transition:fade={{ duration: 300 }}
        >
            <button 
                class="absolute top-6 right-6 text-elegant-primary dark:text-white p-2"
                onclick={() => mobileMenuOpen = false}
            >
                <span class="material-symbols-outlined text-4xl">close</span>
            </button>
            <div class="text-2xl font-extrabold text-elegant-primary dark:text-white uppercase mb-8">
                Academic <span class="text-elegant-accent">Elegance</span>
            </div>
            {#each navLinks as link}
                <a 
                    class="text-3xl font-headline font-bold text-elegant-primary dark:text-white hover:text-elegant-accent transition-colors" 
                    href={link.href}
                    onclick={() => mobileMenuOpen = false}
                >
                    {link.name}
                </a>
            {/each}
            <button class="elegant-btn-primary w-full max-w-xs mt-8 py-4 text-xl">
                Register Now
            </button>
        </div>
    {/if}

    <main>
        {@render children()}
    </main>

    <!-- FAB for Admission -->
    <div class="fixed bottom-8 right-6 z-50">
        <button class="bg-elegant-accent text-white w-14 h-14 rounded-full shadow-[0_20px_40px_rgba(197,160,89,0.4)] flex items-center justify-center active:scale-90 transition-transform border-2 border-white/20 hover:bg-elegant-accent-dark group">
            <span class="material-symbols-outlined scale-110" style="font-variation-settings: 'FILL' 1;">forum</span>
            <span class="absolute right-16 bg-white text-elegant-primary px-4 py-2 rounded-xl text-sm font-bold shadow-lg opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none whitespace-nowrap border border-elegant-accent-light">
                Hubungi Kami
            </span>
        </button>
    </div>

    <!-- Footer -->
    <footer class="bg-[#00210c] dark:bg-slate-950 text-white pt-20 pb-10 font-body leading-relaxed border-t-8 border-elegant-accent mt-20">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 px-6 md:px-12 max-w-7xl mx-auto">
            <div class="space-y-6">
                <div class="text-xl font-headline font-bold text-white uppercase tracking-tighter">
                    {programName.split(' ')[0]} <span class="text-elegant-accent">{programName.split(' ').slice(1).join(' ')}</span>
                </div>
                <p class="text-emerald-100/70 text-sm">Mendedikasikan diri untuk masa depan pendidikan yang berlandaskan iman, inovasi, dan karakter mulia.</p>
                <div class="flex gap-4">
                    <a class="w-10 h-10 rounded-full border border-elegant-accent/30 flex items-center justify-center hover:bg-elegant-accent transition-colors" href="#">
                        <span class="material-symbols-outlined text-sm">share</span>
                    </a>
                    <a class="w-10 h-10 rounded-full border border-elegant-accent/30 flex items-center justify-center hover:bg-elegant-accent transition-colors" href="#">
                        <span class="material-symbols-outlined text-sm">public</span>
                    </a>
                </div>
            </div>
            
            <div>
                <h4 class="font-headline font-bold mb-6 text-elegant-accent">Tautan Langsung</h4>
                <ul class="space-y-4 text-sm text-emerald-100/80">
                    <li><a class="hover:text-elegant-accent transition-colors duration-200" href="#">Tentang Kami</a></li>
                    <li><a class="hover:text-elegant-accent transition-colors duration-200" href="#">Program Akademik</a></li>
                    <li><a class="hover:text-elegant-accent transition-colors duration-200" href="#">Pendaftaran Online</a></li>
                    <li><a class="hover:text-elegant-accent transition-colors duration-200" href="#">Program Tahfizh</a></li>
                </ul>
            </div>

            <div>
                <h4 class="font-headline font-bold mb-6 text-elegant-accent">Informasi</h4>
                <ul class="space-y-4 text-sm text-emerald-100/80">
                    <li><a class="hover:text-elegant-accent transition-colors duration-200" href="#">Privacy Policy</a></li>
                    <li><a class="hover:text-elegant-accent transition-colors duration-200" href="#">Terms of Service</a></li>
                    <li><a class="hover:text-elegant-accent transition-colors duration-200" href="#">Campus Map</a></li>
                    <li><a class="hover:text-elegant-accent transition-colors duration-200" href="#">Adab & Policy</a></li>
                </ul>
            </div>

            <div>
                <h4 class="font-headline font-bold mb-6 text-elegant-accent">Newsletter</h4>
                <p class="text-xs text-emerald-200/60 mb-4">Dapatkan update terbaru mengenai kegiatan dakwah dan pendidikan sekolah.</p>
                <div class="flex">
                    <input class="bg-[#003822] border-none rounded-l-lg text-sm w-full focus:ring-1 focus:ring-elegant-accent placeholder-emerald-100/30 px-4" placeholder="Email Anda" type="email"/>
                    <button class="bg-elegant-accent px-4 py-2 rounded-r-lg hover:bg-elegant-accent-dark transition-colors">
                        <span class="material-symbols-outlined text-white">send</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="mt-16 pt-8 border-t border-emerald-900/50 px-6 md:px-12 max-w-7xl mx-auto flex flex-col md:row justify-between items-center gap-4 text-[10px] text-emerald-500/80">
            <p>© 2026 {programName}. All rights reserved.</p>
            <div class="flex gap-8">
                <a class="hover:text-elegant-accent transition-colors" href="#">Instagram</a>
                <a class="hover:text-elegant-accent transition-colors" href="#">Youtube</a>
                <a class="hover:text-elegant-accent transition-colors" href="#">Linkedin</a>
            </div>
        </div>
    </footer>
</div>
