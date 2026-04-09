<script>
    import PublicLayout from '../../Layouts/PublicLayout.svelte';
    let { schoolProfile, program } = $props();
</script>

<PublicLayout {schoolProfile} currentRoute="ProgramsShow">
    <section class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-24 text-center">
        <div class="w-20 h-20 rounded-3xl bg-zinc-900 text-white flex items-center justify-center mb-8 shadow-xl mx-auto">
            {#if program.image_path}
                <img src={`/storage/${program.image_path}`} alt={program.name} class="w-full h-full object-cover rounded-3xl">
            {:else}
                <span class="text-4xl font-bold font-heading">{program.name[0]}</span>
            {/if}
        </div>
        <h1 class="text-4xl md:text-5xl font-bold font-heading mb-6 tracking-tight">{program.name}</h1>
        <div class="inline-flex items-center rounded-full bg-amber-500/10 text-amber-600 px-4 py-1.5 text-sm font-bold mb-10 ring-1 ring-inset ring-amber-500/20">
            {program.level?.name || 'Program Unggulan'}
        </div>
        <div class="prose max-w-none text-zinc-600 text-lg leading-relaxed text-left">
            {program.description}
        </div>
        <div class="mt-12">
            <a href="/pendaftaran" class="inline-flex items-center px-8 py-4 bg-amber-500 text-white font-bold rounded-full shadow-lg hover:bg-amber-400 transition-all duration-300 transform hover:-translate-y-1">
                Daftar Program Ini Sekarang
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
            </a>
        </div>
    </section>
</PublicLayout>
