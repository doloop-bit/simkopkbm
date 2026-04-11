<script>
    import PublicLayout from '../../Layouts/PublicLayout.svelte';
    import PageHeader from '../../Components/PageHeader.svelte';
    let { schoolProfile, photos } = $props();
</script>

<PublicLayout {schoolProfile} currentRoute="Gallery">
    <PageHeader 
        title="Galeri Kegiatan" 
        description="Dokumentasi berbagai aktivitas dan momen berharga dalam perjalanan pendidikan di sekolah kami."
        breadcrumb="Galeri"
    />

    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
            {#each photos as photo}
                <div class="group relative aspect-square bg-zinc-100 rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition-all duration-500">
                    <img src={`/storage/${photo.image_path}`} alt={photo.title} class="object-cover w-full h-full group-hover:scale-110 transition-transform duration-700">
                    <div class="absolute inset-0 bg-linear-to-t from-zinc-950/80 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex flex-col justify-end p-6">
                        <p class="text-white font-bold text-sm tracking-tight">{photo.title}</p>
                    </div>
                </div>
            {/each}
        </div>
    </section>
</PublicLayout>
