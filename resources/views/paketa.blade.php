<x-layouts.spa-shell 
    :title="($program->name ?? 'Paket A (Setara SD)') . ' - Eksplorasi & Belajar'"
    :program-name="$program->name ?? 'Paket A (Setara SD)'"
    :program-logo="$program->image_path ? Storage::url($program->image_path) : null"
    entry-point="resources/js/paketa.js"
>
    <div id="paketa-app"></div>
</x-layouts.spa-shell>
