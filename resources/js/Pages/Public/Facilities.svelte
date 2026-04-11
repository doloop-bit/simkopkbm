<script>
    import PublicLayout from '../../Layouts/PublicLayout.svelte';
    import PageHeader from '../../Components/PageHeader.svelte';
    let { schoolProfile, facilities } = $props();
</script>

<PublicLayout {schoolProfile} currentRoute="Facilities">
    <PageHeader 
        title="Fasilitas Sekolah" 
        description="Lingkungan belajar yang nyaman dan fasilitas lengkap untuk menunjang kreativitas siswa."
        breadcrumb="Fasilitas"
    />

    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            {#each facilities as facility}
                <div class="bg-white rounded-2xl shadow-sm border border-zinc-100 overflow-hidden">
                    <img src={facility.image_path ? `/storage/${facility.image_path}` : 'https://placehold.co/600x400?text=Fasilitas'} alt={facility.name} class="w-full h-48 object-cover">
                    <div class="p-6">
                        <h3 class="font-bold text-xl text-zinc-900 mb-2">{facility.name}</h3>
                        <p class="text-zinc-600 text-sm leading-relaxed">{facility.description}</p>
                    </div>
                </div>
            {/each}
        </div>
    </section>
</PublicLayout>
