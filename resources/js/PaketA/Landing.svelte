<script>
    import { onMount } from 'svelte';
    import { fade, fly, scale } from 'svelte/transition';
    import { bounceOut, elasticOut } from 'svelte/easing';

    let { programName = 'Paket A (Setara SD)', programLogo = null } = $props();

    let visible = $state(false);
    onMount(() => {
        visible = true;
    });

    // Core Values - 3 columns with playful icons
    const coreValues = [
        { 
            title: 'Akhlakul Karimah & Karakter', 
            icon: '🌿', 
            desc: 'Pendidikan berlandaskan adab harian, empati, dan kemandirian sejak dini.' 
        },
        { 
            title: 'Pembelajaran Menyenangkan (Fun Learning)', 
            icon: '🧩', 
            desc: 'Bebas stres! Belajar melalui proyek partisipatif, eksplorasi alam, dan gaya belajar hands-on.' 
        },
        { 
            title: 'Guru sebagai Sahabat (Mentor)', 
            icon: '🤝', 
            desc: 'Pendekatan personal dan ramah. Kelas berskala kecil memungkinkan mentor benar-benar mengenali potensi unik tiap anak.' 
        }
    ];

    // Activities for gallery/carousel
    const activities = [
        { title: 'Circle Time', desc: 'Doa, cerita pagi, dan membangun kedekatan', image: '🌅' },
        { title: 'Project-Based Learning', desc: 'Membuat karya, eksperimen sains sederhana', image: '🔬' },
        { title: 'Outdoor & Nature Activities', desc: 'Pembelajaran menyatu dengan alam', image: '🌳' }
    ];

    // Testimonials from parents
    const testimonials = [
        { 
            name: 'Ibu Siti', 
            child: 'Ahmad (8 tahun)',
            text: 'Alhamdulillah, Ahmad jadi lebih mandiri dan sopan. Dia juga senang sekali belajar di sini!', 
            rating: 5 
        },
        { 
            name: 'Bapak Rizki', 
            child: 'Aisyah (7 tahun)',
            text: 'Pendekatan guru yang ramah membuat Aisyah betah belajar. Perkembangan akhlaknya sangat terlihat.', 
            rating: 5 
        },
        { 
            name: 'Ibu Nur', 
            child: 'Yusuf (9 tahun)',
            text: 'Kurikulum yang seimbang antara akademis dan karakter. Yusuf jadi lebih percaya diri.', 
            rating: 5 
        }
    ];

    // FAQ items
    const faqs = [
        {
            question: 'Apakah ijazah Paket A diakui negara?',
            answer: 'Ya, ijazah Paket A diakui negara dan dapat digunakan untuk melanjutkan ke jenjang SMP formal maupun non-formal.'
        },
        {
            question: 'Bagaimana dengan jadwal pembelajarannya?',
            answer: 'Jadwal pembelajaran fleksibel dan dapat disesuaikan dengan kebutuhan peserta didik. Kami menawarkan berbagai pilihan waktu belajar.'
        },
        {
            question: 'Seperti apa kurikulum yang digunakan?',
            answer: 'Kami menggunakan Kurikulum Merdeka Plus dengan penguatan Karakter dan Keislaman, yang disesuaikan dengan kebutuhan anak.'
        }
    ];

    let openFaq = $state(null);

    function toggleFaq(index) {
        openFaq = openFaq === index ? null : index;
    }
</script>

<style>
    :global(body) {
        background: linear-gradient(180deg, #fef9e7 0%, #f0fdf4 50%, #eff6ff 100%);
        font-family: 'Nunito', 'Quicksand', sans-serif;
    }

    .blob {
        position: absolute;
        width: 300px;
        height: 300px;
        filter: blur(60px);
        border-radius: 50%;
        z-index: -1;
        animation: float 8s ease-in-out infinite;
    }

    @keyframes float {
        0%, 100% { transform: translateY(0px) scale(1); }
        50% { transform: translateY(-20px) scale(1.05); }
    }

    .card-hover {
        transition: all 0.3s ease;
    }

    .card-hover:hover {
        transform: scale(1.05) translateY(-8px);
        box-shadow: 0 20px 40px rgba(0,0,0,0.1);
    }

    .organic-shape {
        border-radius: 60% 40% 30% 70% / 60% 30% 70% 40%;
    }

    .wave-container {
        position: relative;
        height: 80px;
        overflow: hidden;
    }

    .floating-whatsapp {
        position: fixed;
        bottom: 24px;
        right: 24px;
        z-index: 1000;
        animation: pulse-whatsapp 2s ease-in-out infinite;
    }

    @keyframes pulse-whatsapp {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.1); }
    }

    .testimonial-card {
        background: linear-gradient(135deg, #fef3c7 0%, #fef9e7 100%);
        border-radius: 30px;
    }

    .cta-gradient {
        background: linear-gradient(135deg, #10b981 0%, #34d399 50%, #6ee7b7 100%);
    }
</style>

{#if visible}
<div class="min-h-screen overflow-hidden relative">
    <!-- Decorative Blobs with warm, cheerful colors -->
    <div class="blob top-0 -left-20 bg-emerald-300/30"></div>
    <div class="blob bottom-0 -right-20 bg-amber-300/30"></div>
    <div class="blob top-1/2 left-1/2 -translate-x-1/2 bg-sky-300/30"></div>
    <div class="blob top-1/4 right-0 bg-rose-200/20"></div>

    <!-- Navigation -->
    <nav class="p-6 flex justify-between items-center" in:fly={{ y: -20, duration: 1000 }}>
        <div class="flex items-center gap-3">
            <div class="w-14 h-14 bg-white/70 backdrop-blur-sm rounded-3xl shadow-lg flex items-center justify-center overflow-hidden animate-pulse border-2 border-emerald-200 organic-shape">
                {#if programLogo}
                    <img src={programLogo} alt={programName} class="w-full h-full object-contain p-1" />
                {:else}
                    <span class="text-2xl">🌱</span>
                {/if}
            </div>
            <span class="text-2xl font-bold text-emerald-700 tracking-tight">{programName}</span>
        </div>
        <div class="flex gap-3">
            <button class="px-5 py-2 rounded-full bg-white text-emerald-600 font-bold shadow-md hover:shadow-lg hover:scale-105 transition-all text-sm md:text-base">
                Program
            </button>
            <button class="px-5 py-2 rounded-full bg-emerald-500 text-white font-bold shadow-lg shadow-emerald-200 hover:bg-emerald-600 hover:scale-105 transition-all text-sm md:text-base">
                Daftar Sekarang
            </button>
        </div>
    </nav>

    <!-- Hero Section -->
    <header class="container mx-auto px-6 py-12 text-center lg:py-20">
        <div in:scale={{ duration: 1000, easing: elasticOut }}>
            {#if programLogo}
                <div class="mb-6 flex justify-center">
                    <div class="w-28 h-28 md:w-40 md:h-40 bg-white rounded-[40px] shadow-2xl p-4 flex items-center justify-center -rotate-3 hover:rotate-0 transition-all duration-500 border-4 border-white organic-shape">
                        <img src={programLogo} alt={programName} class="w-full h-full object-contain" />
                    </div>
                </div>
            {/if}
            
            <span class="px-5 py-2 rounded-full bg-emerald-100 text-emerald-700 text-sm font-bold uppercase tracking-wider mb-6 inline-block shadow-sm">
                🌟 Tumbuh Bersama, Mendidik Sepenuh Hati
            </span>
            
            <h1 class="text-4xl md:text-5xl lg:text-7xl font-black text-slate-800 mb-6 leading-tight">
                Belajar Menyenangkan,<br/>
                <span class="text-emerald-600">Karakter Cemerlang</span>
            </h1>
            
            <p class="text-lg md:text-xl text-slate-600 max-w-3xl mx-auto mb-10 leading-relaxed">
                Program Paket A (Setara SD) yang tidak hanya mengejar nilai akademis, tetapi juga menumbuhkan kemandirian, adab, dan akhlakul karimah melalui interaksi erat dengan guru sebagai Sahabat & Mentor.
            </p>

            <div class="flex flex-wrap justify-center gap-4 mb-12">
                <button class="px-10 py-4 bg-gradient-to-r from-orange-400 to-amber-400 text-white text-lg font-bold rounded-full shadow-xl shadow-orange-200 hover:shadow-2xl hover:-translate-y-1 hover:scale-105 transition-all">
                    Konsultasi Sekarang 💬
                </button>
                <button class="px-10 py-4 bg-white text-emerald-600 text-lg font-bold rounded-full shadow-lg border-2 border-emerald-100 hover:bg-emerald-50 hover:-translate-y-1 transition-all">
                    Daftar Tahun Ajaran Baru 📝
                </button>
            </div>

            <!-- Hero Visual: Teacher & Child Interaction -->
            <div class="relative max-w-4xl mx-auto">
                <div class="bg-white rounded-[50px] shadow-2xl p-8 border-4 border-emerald-100 organic-shape">
                    <div class="grid md:grid-cols-2 gap-6 items-center">
                        <div class="text-center">
                            <div class="text-8xl mb-4">👩‍🏫</div>
                            <p class="text-emerald-600 font-bold">Guru Sahabat</p>
                        </div>
                        <div class="text-center">
                            <div class="text-8xl mb-4">👧</div>
                            <p class="text-amber-600 font-bold">Anak Bahagia</p>
                        </div>
                    </div>
                    <p class="text-center text-slate-500 mt-6 text-sm">
                        Interaksi hangat antara anak dan guru dalam suasana belajar yang riang dan menyenangkan
                    </p>
                </div>
            </div>
        </div>
    </header>

    <!-- Wave Transition -->
    <div class="wave-container -mb-1">
        <svg viewBox="0 0 1440 320" class="absolute bottom-0 w-full">
            <path fill="#ffffff" fill-opacity="1" d="M0,160L48,176C96,192,192,224,288,224C384,224,480,192,576,165.3C672,139,768,117,864,128C960,139,1056,181,1152,192C1248,203,1344,181,1392,170.7L1440,160L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path>
        </svg>
    </div>

    <!-- Core Values Section (Nilai Inti) -->
    <section class="bg-white py-20 px-6">
        <div class="container mx-auto">
            <div class="text-center mb-16">
                <span class="px-4 py-1 rounded-full bg-amber-100 text-amber-700 text-sm font-bold mb-4 inline-block">
                    Mengapa Memilih Kami?
                </span>
                <h2 class="text-4xl md:text-5xl font-black text-slate-800 mb-4">Nilai Inti Kami</h2>
                <div class="w-20 h-2 bg-gradient-to-r from-emerald-400 to-amber-400 mx-auto rounded-full"></div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-6xl mx-auto">
                {#each coreValues as value, i}
                <div
                    in:fly={{ y: 60, delay: i * 150, duration: 800 }}
                    class="p-8 rounded-[40px] text-center transition-all duration-300 card-hover bg-gradient-to-br from-emerald-50 to-sky-50 border-2 border-emerald-100 hover:border-emerald-300"
                >
                    <div class="text-6xl mb-6 animate-bounce">{value.icon}</div>
                    <h3 class="text-xl md:text-2xl font-bold text-slate-800 mb-4">{value.title}</h3>
                    <p class="text-slate-600 leading-relaxed">{value.desc}</p>
                </div>
                {/each}
            </div>
        </div>
    </section>

    <!-- Activities Section (Metode & Aktivitas) -->
    <section class="bg-gradient-to-b from-sky-50 to-emerald-50 py-20 px-6">
        <div class="container mx-auto">
            <div class="text-center mb-16">
                <span class="px-4 py-1 rounded-full bg-sky-100 text-sky-700 text-sm font-bold mb-4 inline-block">
                    Lebih dari Sekadar Kelas
                </span>
                <h2 class="text-4xl md:text-5xl font-black text-slate-800 mb-4">Metode & Aktivitas</h2>
                <p class="text-lg text-slate-600 max-w-2xl mx-auto mt-4">
                    Kami percaya anak belajar paling efektif saat mereka bahagia dan merasa aman. Di sini, tidak ada anak yang tertinggal, semuanya dituntun sesuai ritme mereka.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-5xl mx-auto">
                {#each activities as activity, i}
                <div
                    in:scale={{ delay: i * 200, duration: 600 }}
                    class="group relative overflow-hidden rounded-[35px] bg-white shadow-xl hover:shadow-2xl transition-all duration-300 hover:-translate-y-2"
                >
                    <div class="h-48 bg-gradient-to-br from-amber-100 to-rose-100 flex items-center justify-center">
                        <span class="text-7xl group-hover:scale-110 transition-transform duration-300">{activity.image}</span>
                    </div>
                    <div class="p-6">
                        <h3 class="text-xl font-bold text-slate-800 mb-2">{activity.title}</h3>
                        <p class="text-slate-600">{activity.desc}</p>
                    </div>
                </div>
                {/each}
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="bg-white py-20 px-6">
        <div class="container mx-auto">
            <div class="text-center mb-16">
                <span class="px-4 py-1 rounded-full bg-rose-100 text-rose-700 text-sm font-bold mb-4 inline-block">
                    Kata Orang Tua
                </span>
                <h2 class="text-4xl md:text-5xl font-black text-slate-800 mb-4">Apa Kata Mereka?</h2>
                <div class="w-20 h-2 bg-gradient-to-r from-rose-400 to-amber-400 mx-auto rounded-full"></div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-6xl mx-auto">
                {#each testimonials as testimonial, i}
                <div
                    in:fly={{ y: 50, delay: i * 200, duration: 700 }}
                    class="testimonial-card p-8 shadow-lg hover:shadow-xl transition-all duration-300 card-hover"
                >
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-14 h-14 bg-white rounded-full flex items-center justify-center text-2xl shadow-md">
                            👤
                        </div>
                        <div>
                            <h4 class="font-bold text-slate-800">{testimonial.name}</h4>
                            <p class="text-sm text-slate-500">{testimonial.child}</p>
                        </div>
                    </div>
                    <div class="flex mb-3">
                        {#each Array(testimonial.rating) as _}
                            <span class="text-yellow-400 text-lg">⭐</span>
                        {/each}
                    </div>
                    <p class="text-slate-700 italic leading-relaxed">"{testimonial.text}"</p>
                </div>
                {/each}
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="bg-gradient-to-b from-emerald-50 to-amber-50 py-20 px-6">
        <div class="container mx-auto max-w-4xl">
            <div class="text-center mb-16">
                <span class="px-4 py-1 rounded-full bg-emerald-100 text-emerald-700 text-sm font-bold mb-4 inline-block">
                    Tanya Jawab
                </span>
                <h2 class="text-4xl md:text-5xl font-black text-slate-800 mb-4">FAQ</h2>
                <p class="text-slate-600">Pertanyaan yang sering diajukan</p>
            </div>

            <div class="space-y-4">
                {#each faqs as faq, i}
                <div
                    in:fly={{ y: 30, delay: i * 100, duration: 500 }}
                    class="bg-white rounded-3xl shadow-md overflow-hidden"
                >
                    <button 
                        onclick={() => toggleFaq(i)}
                        class="w-full px-6 py-5 text-left flex justify-between items-center hover:bg-emerald-50 transition-colors"
                    >
                        <span class="font-bold text-slate-800 text-lg">{faq.question}</span>
                        <span class="text-2xl text-emerald-500 transition-transform duration-300 {openFaq === i ? 'rotate-180' : ''}">
                            ▼
                        </span>
                    </button>
                    <div 
                        class="overflow-hidden transition-all duration-300"
                        style:height={openFaq === i ? 'auto' : '0px'}
                    >
                        <p class="px-6 pb-5 text-slate-600 leading-relaxed">{faq.answer}</p>
                    </div>
                </div>
                {/each}
            </div>
        </div>
    </section>

    <!-- Final CTA Section -->
    <section class="bg-white py-20 px-6">
        <div class="container mx-auto">
            <div class="cta-gradient rounded-[60px] p-12 lg:p-20 relative overflow-hidden shadow-2xl shadow-emerald-200">
                <!-- Decorative circles -->
                <div class="absolute -top-12 -right-12 w-48 h-48 bg-white/20 rounded-full"></div>
                <div class="absolute -bottom-12 -left-12 w-48 h-48 bg-white/20 rounded-full"></div>
                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>

                <div class="relative z-10 text-center text-white max-w-3xl mx-auto">
                    <h2 class="text-4xl lg:text-6xl font-black mb-6 leading-tight">
                        Siap Memulai Perjalanan Belajar yang Menyenangkan?
                    </h2>
                    <p class="text-xl text-white/90 mb-10 leading-relaxed">
                        Mari bergabung dengan keluarga besar kami. Kami membatasi kuota peserta untuk memastikan setiap anak mendapat perhatian terbaik.
                    </p>
                    <button class="px-12 py-5 bg-white text-emerald-600 text-xl font-black rounded-full shadow-xl hover:bg-amber-50 hover:scale-105 hover:-translate-y-1 transition-all">
                        Hubungi via WhatsApp 💚
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-slate-900 py-16 px-6">
        <div class="container mx-auto">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-12 max-w-6xl mx-auto">
                <!-- Brand -->
                <div class="md:col-span-2">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-12 h-12 bg-emerald-500 rounded-2xl flex items-center justify-center text-2xl">
                            🌱
                        </div>
                        <span class="text-2xl font-bold text-white">{programName}</span>
                    </div>
                    <p class="text-slate-400 leading-relaxed mb-6">
                        Program pendidikan kesetaraan yang fokus pada pembentukan karakter, nilai agama, dan pendekatan belajar yang menyenangkan dan personal.
                    </p>
                    <div class="flex gap-4">
                        <a href="#" class="w-10 h-10 bg-white/10 rounded-full flex items-center justify-center text-white hover:bg-emerald-500 transition-colors">📘</a>
                        <a href="#" class="w-10 h-10 bg-white/10 rounded-full flex items-center justify-center text-white hover:bg-emerald-500 transition-colors">📸</a>
                        <a href="#" class="w-10 h-10 bg-white/10 rounded-full flex items-center justify-center text-white hover:bg-emerald-500 transition-colors">📺</a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div>
                    <h4 class="text-white font-bold mb-4">Tautan</h4>
                    <ul class="space-y-2 text-slate-400">
                        <li><a href="#" class="hover:text-emerald-400 transition-colors">Beranda</a></li>
                        <li><a href="#" class="hover:text-emerald-400 transition-colors">Program</a></li>
                        <li><a href="#" class="hover:text-emerald-400 transition-colors">Tentang Kami</a></li>
                        <li><a href="#" class="hover:text-emerald-400 transition-colors">Kontak</a></li>
                    </ul>
                </div>

                <!-- Contact -->
                <div>
                    <h4 class="text-white font-bold mb-4">Kontak</h4>
                    <ul class="space-y-3 text-slate-400">
                        <li class="flex items-center gap-2">
                            <span>📍</span>
                            <span>Jl. Pendidikan No. 123</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <span>📧</span>
                            <span>info@paketa.sch.id</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <span>📱</span>
                            <span>+62 812-3456-7890</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-slate-800 mt-12 pt-8 text-center text-slate-500">
                <p class="font-medium">© 2026 {programName}. Tumbuh Bersama, Mendidik Sepenuh Hati.</p>
            </div>
        </div>
    </footer>

    <!-- Floating WhatsApp Button -->
    <a 
        href="https://wa.me/6281234567890" 
        target="_blank"
        class="floating-whatsapp flex items-center gap-3 px-5 py-4 bg-green-500 text-white rounded-full shadow-2xl hover:bg-green-600 transition-all"
    >
        <span class="text-3xl">💬</span>
        <span class="font-bold hidden md:inline">Konsultasi Gratis</span>
    </a>
</div>
{/if}
