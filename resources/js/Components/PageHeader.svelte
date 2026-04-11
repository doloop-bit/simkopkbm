<script>
    import { Link } from "@inertiajs/svelte";
    let { title, description, breadcrumb } = $props();
</script>

<section class="relative bg-slate-900 pt-32 pb-12 overflow-hidden border-b border-slate-800">
    <!-- Dynamic Background -->
    <div class="absolute inset-0 z-0">
        <div class="absolute inset-0 bg-linear-to-tr from-slate-950 via-slate-900 to-amber-900/10 opacity-90"></div>
        
        <!-- Animated Orbs -->
        <div class="absolute top-1/4 -left-20 w-72 h-72 bg-amber-500/10 rounded-full blur-[100px] animate-pulse"></div>
        <div class="absolute -bottom-20 -right-20 w-72 h-72 bg-slate-800/20 rounded-full blur-[80px]"></div>
        
        <!-- Grid Pattern -->
        <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/dark-matter.png')] opacity-10"></div>
    </div>

    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col items-center text-center">
            {#if breadcrumb}
                <nav class="flex items-center space-x-3 text-amber-500/80 text-[9px] font-bold uppercase tracking-[0.2em] mb-4 animate-in fade-in slide-in-from-bottom-2 duration-700">
                    <Link href="/" class="hover:text-amber-400 transition-colors uppercase">Beranda</Link>
                    <svg class="w-2.5 h-2.5 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                    <span class="text-slate-500 uppercase">{breadcrumb}</span>
                </nav>
            {/if}

            <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold font-heading text-white tracking-tight leading-tight mb-3 animate-in fade-in slide-in-from-bottom-4 duration-1000">
                {title}
            </h1>

            {#if description}
                <p class="text-sm sm:text-base text-slate-400 max-w-2xl mx-auto leading-relaxed font-medium animate-in fade-in slide-in-from-bottom-6 duration-1000 [animation-delay:200ms]">
                    {description}
                </p>
            {/if}

            <div class="mt-8 animate-in fade-in zoom-in duration-1000 [animation-delay:400ms]">
                <div class="h-1 w-20 bg-linear-to-r from-amber-500 to-amber-600 rounded-full shadow-lg shadow-amber-500/20"></div>
            </div>
        </div>
    </div>
</section>
