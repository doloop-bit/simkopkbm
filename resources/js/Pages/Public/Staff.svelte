<script>
    import PublicLayout from '../../Layouts/PublicLayout.svelte';
    let { schoolProfile, staffMembers } = $props();
</script>

<PublicLayout {schoolProfile} currentRoute="Staff">
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
        <h1 class="text-4xl font-bold font-heading mb-12 text-center">Struktur Organisasi</h1>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
            {#each staffMembers as member}
                <div class="bg-white rounded-2xl shadow-sm border border-zinc-100 overflow-hidden text-center p-6">
                    <img src={member.photo_path ? `/storage/${member.photo_path}` : 'https://placehold.co/400x400?text=Staff'} alt={member.name} class="w-32 h-32 rounded-full mx-auto mb-4 object-cover">
                    <h3 class="font-bold text-lg text-zinc-900">{member.name}</h3>
                    <p class="text-amber-600 text-sm font-semibold uppercase tracking-wider">{member.position}</p>
                </div>
            {/each}
        </div>
    </section>
</PublicLayout>
