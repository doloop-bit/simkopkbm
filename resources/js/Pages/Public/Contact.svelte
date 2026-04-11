<script>
    import PublicLayout from '../../Layouts/PublicLayout.svelte';
    let { schoolProfile } = $props();
    
    const phone = $derived(schoolProfile?.phone || '6281234567890');
    const email = $derived(schoolProfile?.email || 'info@simkopkbm.com');
    const address = $derived(schoolProfile?.address || 'Jakarta, Indonesia');
    const mapUrl = $derived(schoolProfile?.map_embed_url || '');
</script>

<PublicLayout {schoolProfile} currentRoute="Contact">
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
        <h1 class="text-4xl font-bold font-heading mb-12 text-center">Hubungi Kami</h1>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
            <!-- Contact Info -->
            <div class="space-y-8">
                <div>
                    <h2 class="text-2xl font-bold text-zinc-900 mb-6 tracking-tight">Informasi Kontak</h2>
                    <p class="text-zinc-600 mb-8 leading-relaxed">Kami siap membantu Anda. Silakan hubungi kami melalui salah satu saluran berikut atau datang langsung ke kantor kami.</p>
                </div>

                <div class="flex items-start space-x-6">
                    <div class="w-12 h-12 bg-amber-500 rounded-2xl flex items-center justify-center text-white shrink-0 shadow-lg shadow-amber-500/20">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-zinc-900">Telepon / WhatsApp</h3>
                        <p class="text-zinc-600">+{phone.replace(/^0/, '62')}</p>
                    </div>
                </div>

                <div class="flex items-start space-x-6">
                    <div class="w-12 h-12 bg-amber-500 rounded-2xl flex items-center justify-center text-white shrink-0 shadow-lg shadow-amber-500/20">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-zinc-900">Email</h3>
                        <p class="text-zinc-600">{email}</p>
                    </div>
                </div>

                <div class="flex items-start space-x-6">
                    <div class="w-12 h-12 bg-amber-500 rounded-2xl flex items-center justify-center text-white shrink-0 shadow-lg shadow-amber-500/20">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-zinc-900">Alamat</h3>
                        <p class="text-zinc-600 max-w-sm">{address}</p>
                    </div>
                </div>
            </div>

            <!-- Map -->
            <div class="bg-zinc-100 rounded-3xl overflow-hidden shadow-sm h-96 lg:h-full min-h-[400px]">
                {#if mapUrl}
                    <iframe 
                        src={mapUrl} 
                        width="100%" 
                        height="100%" 
                        style="border:0;" 
                        allowfullscreen="" 
                        loading="lazy" 
                        title="Peta Lokasi"
                    ></iframe>
                {:else}
                    <div class="w-full h-full flex flex-col items-center justify-center text-zinc-400 p-12 text-center">
                        <svg class="w-16 h-16 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7l5-2.5 5.553 2.776a1 1 0 01.447.894v10.764a1 1 0 01-1.447.894L15 17l-6 3z"/></svg>
                        <p class="font-medium">Peta lokasi belum tersedia</p>
                    </div>
                {/if}
            </div>
        </div>
    </section>
</PublicLayout>
