@props([
    'name' => null,
])

@php
    // Mary UI uses o-icon-name for outline, s-icon-name for solid
    // blade-heroicons uses heroicon-o-icon-name / heroicon-s-icon-name
    $svgName = $name;
    if ($name && !str_starts_with($name, 'heroicon-')) {
        $svgName = 'heroicon-' . $name;
    }
@endphp

@if($svgName)
    @svg($svgName, $attributes->merge(['class' => 'w-5 h-5 shrink-0'])->get('class'))
@endif
