<script>
    import { onMount } from 'svelte';
    import { fade, fly, scale } from 'svelte/transition';
    import ElegantLayout from '../components/ElegantLayout.svelte';

    let { programName = 'Paket A (Setara SD)', programLogo = null } = $props();

    let visible = $state(false);
    onMount(() => {
        visible = true;
    });

    // We'll map the existing data to the new layout
    const features = [
        { 
            title: 'Keunggulan Akhlak', 
            icon: 'auto_awesome', 
            desc: 'Pendidikan berlandaskan adab harian, empati, dan kemandirian sejak dini.' 
        },
        { 
            title: 'Wawasan Global', 
            icon: 'language', 
            desc: 'Metode Fun Learning terintegrasi dengan teknologi dan bahasa asing.' 
        },
        { 
            title: 'Pendekatan Personal', 
            icon: 'groups', 
            desc: 'Guru sebagai sahabat & mentor yang mengenali potensi unik tiap anak.' 
        }
    ];

    const programs = [
        { 
            title: "Bilingual Islamic Class", 
            desc: "Metode pembelajaran dua bahasa terintegrasi dengan pemahaman agama.",
            image: "https://lh3.googleusercontent.com/aida-public/AB6AXuDFExyALIsdy7EEGb-rz5W9rfXfNwG_uNMUoysKVIk5rk2-Z7Ev-XsYOs1ReFqZOvi0-6_zSQCOI7Wh2WrU1PAx_lbKNv7Dq0uhKVZzH9AKUC-N9LYMPzMRl3lRC7HJ6ZLuiMj0Pd6OBO2gwZwM6O386CnD-HQHUacR8uwEN7kpc4eBADGXybD7GkejsuYjDrtmDUCDn1IPTkGfzB-MgYNl9sE4r2DcNfS9wVuKXmmLMx31mpsPYEXcgDxi1MGhQhPBSo-v9vbp2-Su"
        },
        { 
            title: "Program Tahfizh", 
            desc: "Menghafal Al-Qur'an dengan metode menyenangkan dan tartil yang benar.",
            icon: "menu_book",
            accent: true
        },
        { 
            title: "Islamic Science", 
            desc: "Menemukan kebesaran Allah melalui sains dan teknologi.",
            icon: "science"
        },
        { 
            title: "Adab & Karakter", 
            desc: "Pembinaan kepribadian Islami yang santun dan disiplin.",
            icon: "history_edu"
        }
    ];
</script>

<ElegantLayout {programName} {programLogo}>
    {#if visible}
        <!-- Hero Section -->
        <header class="relative min-h-[800px] flex items-center overflow-hidden bg-elegant-surface">
            <div class="absolute inset-0 z-0">
                <img class="w-full h-full object-cover opacity-20 scale-105" alt="Background" src="https://lh3.googleusercontent.com/aida-public/AB6AXuCdO8cP0243B64iV90_JzutnDPhNVHs8nCvY3utGulgeWCvaAxXYN_pLGbNbNaYqzjSMJOAEEGugS0m5PY-t6XZPnlzpyIWu5ew3JYgcEXPIUuf84SBK1aBLpCaJSJ_yuxWofQR3SutYnbHwJU8VGpJX_XtIuHsDpcYQBRLqmt4E2rZjICxdtbWcxJpdQrgNDpvB05fy56SIlQVTg-j2-hH6XGmMZ_z722UTPXRyrc-bQPcYnT8oScTeszIHvxrKtpkFKkehP8FM1xn"/>
                <div class="absolute inset-0 bg-gradient-to-r from-elegant-surface via-elegant-surface/80 to-transparent"></div>
            </div>
            
            <div class="relative z-10 max-w-7xl mx-auto px-6 md:px-12 grid grid-cols-1 lg:grid-cols-2 gap-12 items-center w-full">
                <div class="space-y-8" in:fly={{ x: -50, duration: 1000 }}>
                    <div class="inline-block px-4 py-1.5 rounded-full bg-elegant-accent-light text-elegant-accent-dark font-headline text-[0.75rem] uppercase tracking-[0.05em] font-bold border border-elegant-accent/20">
                        Penerimaan Siswa Baru 2024/2025
                    </div>
                    <h1 class="font-headline font-extrabold text-elegant-primary leading-tight -tracking-[0.02em] text-5xl md:text-7xl">
                        Membentuk Generasi <span class="text-elegant-accent">Beriman</span> & Berkarakter
                    </h1>
                    <p class="text-lg text-elegant-on-surface-variant max-w-xl leading-relaxed">
                        Mengintegrasikan kurikulum akademik unggulan dengan pembinaan akhlak mulia dan nilai-nilai Islami untuk mencetak pemimpin masa depan yang bertaqwa dan kompetitif.
                    </p>
                    <div class="flex flex-wrap gap-4 pt-4">
                        <button class="elegant-btn-accent !px-10 !py-4">
                            Daftar Sekarang
                        </button>
                        <button class="flex items-center gap-2 px-10 py-4 rounded-xl font-bold text-elegant-primary group relative overflow-hidden">
                            Pelajari Kurikulum
                            <span class="w-0 h-[2px] bg-elegant-accent group-hover:w-full transition-all duration-500 block absolute bottom-4 left-10"></span>
                            <span class="material-symbols-outlined text-elegant-accent group-hover:translate-x-1 transition-transform">arrow_forward</span>
                        </button>
                    </div>
                </div>
                
                <div class="relative hidden lg:block" in:fly={{ x: 50, duration: 1000 }}>
                    <div class="relative z-20 rounded-3xl overflow-hidden shadow-2xl border-4 border-white transform rotate-2 hover:rotate-0 transition-transform duration-700">
                        <img class="w-full aspect-[4/5] object-cover" alt="Student" src="https://lh3.googleusercontent.com/aida-public/AB6AXuD9Pt22lwHhfYC-jWJa6eU3ekLTEOActGd9nyDG4BPtkkMxcDgKTNR8-NRBwaFJQ2OYkUcGY0mQnLfICBDN3q9ksgWyKe2dqcPYvcbJGiyf12ECpRAx_V18CksyRWFjghdu9JunKBbosZAwKT44DFRPJFyKJWSurc9kw0Su2w2iS0XZtWbLpbSVrAjO2SucLFzIT3QrIGMe-yYdypeFnBSeGdMkhRFg3AeP1_MlrWAHudCo3ewdlA0E42XZsd135LBopdf7tau7xTwq"/>
                    </div>
                    <div class="absolute -bottom-10 -left-10 z-30 bg-white p-8 rounded-2xl shadow-xl max-w-[240px] border border-elegant-accent-light">
                        <div class="border-l-4 border-elegant-accent pl-4">
                            <div class="text-3xl font-headline font-bold text-elegant-accent">100%</div>
                            <div class="text-sm text-elegant-on-surface-variant font-medium text-balance">Fokus pada Pembentukan Akhlakul Karimah</div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Vision Section -->
        <section class="py-24 bg-[#f3f5f4] border-y border-elegant-accent-light/30">
            <div class="max-w-7xl mx-auto px-6 md:px-12">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
                    <div class="lg:col-span-1">
                        <h2 class="font-headline font-extrabold text-elegant-primary mb-6 text-3xl">Visi Kami</h2>
                        <p class="text-lg text-elegant-on-surface-variant leading-relaxed italic">
                            "Menjadi lembaga pendidikan terdepan yang mengharmonisasikan kecerdasan intelektual dengan kekuatan iman dan adab."
                        </p>
                        <div class="mt-8 h-1 w-24 bg-elegant-accent rounded-full"></div>
                    </div>
                    <div class="lg:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-8">
                        {#each features as feature}
                            <div class="elegant-card p-8 hover:border-elegant-accent/20">
                                <div class="w-12 h-12 bg-elegant-accent/10 text-elegant-accent rounded-xl flex items-center justify-center mb-6">
                                    <span class="material-symbols-outlined">{feature.icon}</span>
                                </div>
                                <h3 class="font-headline font-bold text-elegant-primary text-xl mb-3">{feature.title}</h3>
                                <p class="text-elegant-on-surface-variant text-sm leading-relaxed">{feature.desc}</p>
                            </div>
                        {/each}
                    </div>
                </div>
            </div>
        </section>

        <!-- Programs Grid -->
        <section class="py-24 bg-elegant-surface">
            <div class="max-w-7xl mx-auto px-6 md:px-12">
                <div class="text-center mb-16 space-y-4">
                    <span class="font-headline text-elegant-accent font-bold tracking-widest uppercase text-xs">Program Unggulan</span>
                    <h2 class="text-4xl font-headline font-extrabold text-elegant-primary">Kurikulum Holistik</h2>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-6 gap-6">
                    <!-- Featured Program -->
                    <div class="md:col-span-3 bg-elegant-primary rounded-3xl p-10 flex flex-col justify-end relative overflow-hidden group cursor-pointer border-b-8 border-elegant-accent min-h-[400px]">
                        <img class="absolute inset-0 w-full h-full object-cover opacity-40 group-hover:scale-110 transition-transform duration-700" alt={programs[0].title} src={programs[0].image}/>
                        <div class="relative z-10 text-white">
                            <h3 class="text-3xl font-headline font-bold mb-4">{programs[0].title}</h3>
                            <p class="text-emerald-50 mb-6 max-w-sm">{programs[0].desc}</p>
                            <div class="flex items-center gap-2 font-bold text-elegant-accent group-hover:gap-3 transition-all">
                                Pelajari Selengkapnya <span class="material-symbols-outlined">trending_flat</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- others in bento grid style -->
                    <div class="md:col-span-3 grid grid-rows-2 gap-6">
                        <div class="bg-elegant-secondary rounded-3xl p-8 flex items-center gap-6 group cursor-pointer relative overflow-hidden">
                            <div class="absolute inset-0 bg-gradient-to-r from-elegant-accent/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                            <div class="relative z-10 w-20 h-20 bg-white/20 rounded-2xl flex items-center justify-center shrink-0 border border-white/30">
                                <span class="material-symbols-outlined text-elegant-accent-light text-4xl">{programs[1].icon}</span>
                            </div>
                            <div class="relative z-10 text-white">
                                <h3 class="text-2xl font-headline font-bold mb-1">{programs[1].title}</h3>
                                <p class="text-emerald-100 text-sm">{programs[1].desc}</p>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-6">
                            {#each programs.slice(2) as program}
                                <div class="bg-elegant-accent-light/30 rounded-3xl p-8 flex flex-col justify-between group cursor-pointer border border-transparent hover:border-elegant-accent/40 transition-all">
                                    <span class="material-symbols-outlined text-elegant-accent text-3xl">{program.icon}</span>
                                    <div>
                                        <h3 class="text-xl font-headline font-bold text-elegant-primary mb-2 line-clamp-1">{program.title}</h3>
                                        <p class="text-elegant-on-surface-variant text-xs line-clamp-2">{program.desc}</p>
                                    </div>
                                </div>
                            {/each}
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Final CTA -->
        <section class="py-24 bg-[#f3f5f4]">
            <div class="max-w-5xl mx-auto px-6">
                <div class="bg-elegant-primary rounded-[3rem] p-12 text-center text-white relative overflow-hidden shadow-2xl border-b-8 border-elegant-accent">
                    <div class="absolute inset-0 opacity-10 islamic-pattern pointer-events-none"></div>
                    <div class="relative z-10 space-y-8">
                        <h2 class="text-4xl md:text-5xl font-headline font-extrabold leading-tight">
                            Siap Membentuk Masa Depan <br class="hidden md:block"/> Ananda yang Lebih Baik?
                        </h2>
                        <p class="text-emerald-100/80 text-lg max-w-2xl mx-auto">
                            Mari bergabung dengan keluarga besar kami. Kami membatasi kuota peserta untuk memastikan setiap anak mendapat perhatian terbaik.
                        </p>
                        <div class="flex flex-wrap justify-center gap-6">
                            <button class="elegant-btn-accent !px-12 !py-5 !text-lg !bg-white !text-elegant-primary !border-none hover:!bg-elegant-accent-light">
                                Hubungi Konsultan 💬
                            </button>
                            <button class="px-12 py-5 rounded-xl font-bold border-2 border-white/30 hover:bg-white/10 transition-all">
                                Download Brosur
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    {/if}
</ElegantLayout>

<style>
    /* Scoped styles if needed */
    :global(.islamic-pattern) {
        background-color: #fcfbf7;
        background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M30 0l2.5 7.5L40 10l-7.5 2.5L30 20l-2.5-7.5L20 10l7.5-2.5L30 0zm0 40l2.5 7.5L40 50l-7.5 2.5L30 60l-2.5-7.5L20 50l7.5-2.5L30 40zM0 30l2.5 7.5L10 40l-7.5 2.5L0 50l-2.5-7.5L-10 40l7.5-2.5L0 30zm40 0l2.5 7.5L50 40l-7.5 2.5L40 50l-2.5-7.5L30 40l7.5-2.5L40 30z' fill='%23C5A059' fill-opacity='0.05' fill-rule='evenodd'/%3E%3C/svg%3E");
    }
</style>
