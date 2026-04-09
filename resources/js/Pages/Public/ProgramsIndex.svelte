<script>
    import PublicLayout from '../../Layouts/PublicLayout.svelte';
    let { schoolProfile, programs } = $props();
</script>

<PublicLayout {schoolProfile} currentRoute="ProgramsIndex">
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
        <h1 class="text-4xl font-bold font-heading mb-12 text-center">Program Pendidikan</h1>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            {#each programs as program}
                <div class="bg-white rounded-3xl p-8 ring-1 ring-zinc-200/50 shadow-sm hover:shadow-xl transition-all duration-300">
                    <div class="w-16 h-16 rounded-2xl bg-zinc-900 text-white flex items-center justify-center mb-6 shadow-md">
                        {#if program.image_path}
                            <img src={`/storage/${program.image_path}`} alt={program.name} class="w-full h-full object-cover rounded-2xl">
                        {:else}
                            <span class="text-3xl font-bold font-heading">{program.name[0]}</span>
                        {/if}
                    </div>
                    <h3 class="text-xl font-bold text-zinc-900 mb-2 font-heading tracking-tight">{program.name}</h3>
                    <p class="text-zinc-500 text-sm line-clamp-3 leading-relaxed mb-6">{program.description}</p>
                    <a href={`/program-pendidikan/${program.slug}`} class="text-amber-600 font-bold hover:text-amber-700 transition-colors inline-flex items-center">
                        Pelajari Selengkapnya
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    </a>
                </div>
            {/each}
        </div>
    </section>
</PublicLayout>
