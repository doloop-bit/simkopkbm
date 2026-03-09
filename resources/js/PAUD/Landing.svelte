<script>
    import { onMount } from 'svelte';
    import { fade, fly, scale } from 'svelte/transition';
    import { bounceOut } from 'svelte/easing';

    let { programName = 'PAUD Ceria', programLogo = null } = $props();

    let visible = $state(false);
    onMount(() => {
        visible = true;
    });

    const colors = [
        'bg-yellow-400',
        'bg-sky-400',
        'bg-rose-400',
        'bg-emerald-400',
        'bg-violet-400'
    ];

    const features = [
        { title: 'Bermain & Belajar', icon: '🎨', desc: 'Metode belajar yang menyenangkan' },
        { title: 'Kreativitas', icon: '🧩', desc: 'Asah imajinasi si kecil' },
        { title: 'Lingkungan Aman', icon: '🏡', desc: 'Nyaman seperti di rumah' },
        { title: 'Guru Penyayang', icon: '👩‍🏫', desc: 'Didampingi tenaga ahli' }
    ];
</script>

<style>
    :global(body) {
        background-color: #f0f9ff;
        font-family: 'Quicksand', sans-serif;
    }

    .blob {
        position: absolute;
        width: 300px;
        height: 300px;
        background: rgba(251, 191, 36, 0.2);
        filter: blur(50px);
        border-radius: 50%;
        z-index: -1;
    }

    .card-hover:hover {
        transform: scale(1.05) rotate(2deg);
    }
</style>

{#if visible}
<div class="min-h-screen overflow-hidden relative">
    <!-- Decorative Blobs -->
    <div class="blob top-0 -left-20 bg-yellow-400/20"></div>
    <div class="blob bottom-0 -right-20 bg-rose-400/20"></div>
    <div class="blob top-1/2 left-1/2 -translate-x-1/2 bg-sky-400/20"></div>

    <!-- Navigation -->
    <nav class="p-6 flex justify-between items-center" in:fly={{ y: -20, duration: 1000 }}>
        <div class="flex items-center gap-3">
            <div class="w-14 h-14 bg-white/50 backdrop-blur-sm rounded-2xl shadow-lg flex items-center justify-center overflow-hidden animate-bounce border-2 border-white">
                {#if programLogo}
                    <img src={programLogo} alt={programName} class="w-full h-full object-contain p-1" />
                {:else}
                    <span class="text-2xl">🏫</span>
                {/if}
            </div>
            <span class="text-2xl font-bold text-sky-600 tracking-tight">{programName}</span>
        </div>
        <div class="flex gap-4">
            <button class="px-6 py-2 rounded-full bg-white text-sky-600 font-bold shadow-sm hover:shadow-md transition-all">Tentang</button>
            <button class="px-6 py-2 rounded-full bg-rose-500 text-white font-bold shadow-lg shadow-rose-200 hover:scale-105 transition-all">Daftar Sekarang</button>
        </div>
    </nav>

    <!-- Hero Section -->
    <header class="container mx-auto px-6 py-12 text-center lg:py-24">
        <div in:scale={{ duration: 1000, easing: bounceOut }}>
            {#if programLogo}
                <div class="mb-8 flex justify-center">
                    <div class="w-32 h-32 md:w-48 md:h-48 bg-white rounded-[40px] shadow-2xl p-4 flex items-center justify-center rotate-3 hover:rotate-0 transition-all duration-500 border-8 border-white">
                        <img src={programLogo} alt={programName} class="w-full h-full object-contain" />
                    </div>
                </div>
            {/if}
            <span class="px-4 py-1 rounded-full bg-yellow-100 text-yellow-700 text-sm font-bold uppercase tracking-widest mb-4 inline-block">
                Tempat Belajar Paling Seru!
            </span>
            <h1 class="text-5xl lg:text-7xl font-black text-sky-900 mb-6 leading-tight">
                Tumbuh <span class="text-rose-500 italic">Cerdas</span> & <br/>
                Berkarakter <span class="text-yellow-500 underline decoration-wavy decoration-emerald-400">Bahagia</span>
            </h1>
            <p class="text-xl text-slate-600 max-w-2xl mx-auto mb-10 leading-relaxed">
                Bersama kami, si kecil akan mengeksplorasi dunia dengan penuh warna, keceriaan, dan kasih sayang setiap harinya.
            </p>
            
            <div class="flex flex-wrap justify-center gap-4">
                <button class="px-10 py-4 bg-sky-500 text-white text-lg font-bold rounded-3xl shadow-xl shadow-sky-200 hover:bg-sky-600 hover:-translate-y-1 transition-all">
                    Ayo Mulai Petualangan! 🚀
                </button>
                <button class="px-10 py-4 bg-white text-slate-700 text-lg font-bold rounded-3xl shadow-lg border-b-4 border-slate-100 hover:bg-slate-50 transition-all">
                    Lihat Fasilitas 🎨
                </button>
            </div>
        </div>
    </header>

    <!-- Waves Section -->
    <div class="relative h-24 overflow-hidden -mb-1">
        <svg viewBox="0 0 1440 320" class="absolute bottom-0 w-full">
            <path fill="#ffffff" fill-opacity="1" d="M0,192L48,197.3C96,203,192,213,288,192C384,171,480,117,576,112C672,107,768,149,864,154.7C960,160,1056,128,1152,112C1248,96,1344,96,1392,96L1440,96L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path>
        </svg>
    </div>

    <!-- White Section -->
    <section class="bg-white py-20 px-6">
        <div class="container mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-slate-900 mb-4">Mengapa Memilih Kami?</h2>
                <div class="w-24 h-2 bg-yellow-400 mx-auto rounded-full"></div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                {#each features as feature, i}
                <div 
                    in:fly={{ y: 50, delay: i * 200, duration: 800 }}
                    class="p-8 rounded-[40px] text-center transition-all duration-300 card-hover bg-slate-50 border-2 border-transparent hover:border-sky-200"
                >
                    <div class="text-5xl mb-6">{feature.icon}</div>
                    <h3 class="text-2xl font-bold text-slate-800 mb-3">{feature.title}</h3>
                    <p class="text-slate-600">{feature.desc}</p>
                </div>
                {/each}
            </div>
        </div>
    </section>

    <!-- Gallery Preview / CTA -->
    <section class="bg-sky-50 py-20 px-6">
        <div class="container mx-auto rounded-[60px] bg-sky-500 p-12 lg:p-24 relative overflow-hidden shadow-2xl shadow-sky-200">
            <!-- Decorative circle -->
            <div class="absolute -top-12 -right-12 w-48 h-48 bg-sky-400 rounded-full opacity-50"></div>
            
            <div class="relative z-10 flex flex-col lg:flex-row items-center justify-between gap-12">
                <div class="text-center lg:text-left text-white max-w-xl">
                    <h2 class="text-4xl lg:text-6xl font-black mb-6 leading-tight">Siap Bergabung dengan Keluarga Kami?</h2>
                    <p class="text-xl text-sky-100">Dapatkan pengalaman belajar terbaik untuk masa depan gemilang anak Anda.</p>
                </div>
                <div class="shrink-0">
                    <button class="px-12 py-6 bg-yellow-400 text-yellow-900 text-2xl font-black rounded-3xl shadow-xl hover:bg-yellow-300 hover:scale-110 active:scale-95 transition-all">
                        Daftar Sekarang! ✨
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-white py-12 text-center text-slate-500 border-t border-slate-100">
        <p class="font-bold">© 2026 {programName}. Made with 💖 for Kids.</p>
    </footer>
</div>
{/if}
