<x-layouts.spa-shell 
    :title="($program->name ?? 'Paket A (Setara SD)') . ' - Eksplorasi & Belajar'"
    :program-name="$program->name ?? 'Paket A (Setara SD)'"
    :program-logo="$program->image_path ? Storage::url($program->image_path) : null"
    entry-point="resources/js/paketa.js"
>
    @push('heads')
        <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
        <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
        <script src="https://unpkg.com/lucide@latest"></script>
    @endpush
    <div id="paketa-app"></div>
</x-layouts.spa-shell>
