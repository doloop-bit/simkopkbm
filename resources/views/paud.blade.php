<x-layouts.spa-shell 
    :title="($program->name ?? 'PAUD Ceria') . ' - Belajar & Bermain'"
    :program-name="$program->name ?? 'PAUD Ceria'"
    :program-logo="$program->image_path ? Storage::url($program->image_path) : null"
    entry-point="resources/js/paud.js"
>
    <div id="paud-app"></div>
</x-layouts.spa-shell>
