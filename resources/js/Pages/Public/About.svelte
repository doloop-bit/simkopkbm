<script>
    import PublicLayout from '../../Layouts/PublicLayout.svelte';
    let { schoolProfile } = $props();
</script>

<PublicLayout {schoolProfile} currentRoute="About">
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
        <h1 class="text-4xl font-bold font-heading mb-8">Tentang Kami</h1>
        <div class="prose max-w-none text-zinc-600">
            <h2 class="text-2xl font-bold text-zinc-900 mb-4">Profil Sekolah</h2>
            <p class="mb-6">{schoolProfile?.history || 'Sejarah sekolah...'}</p>
            
            <h2 class="text-2xl font-bold text-zinc-900 mb-4">Visi</h2>
            <p class="mb-6">{schoolProfile?.vision || 'Visi kami...'}</p>
            
            <h2 class="text-2xl font-bold text-zinc-900 mb-4">Misi</h2>
            <div class="whitespace-pre-line">{schoolProfile?.mission || 'Misi kami...'}</div>
        </div>
    </section>
</PublicLayout>
