@props([
    'image' => null,
    'icon' => 'o-user',
])

@php
    $sizeClasses = 'w-8 h-8';
@endphp

@if($image)
    <img src="{{ $image }}" {{ $attributes->class([$sizeClasses, 'object-cover']) }} alt="" />
@else
    <div {{ $attributes->merge(['class' => $sizeClasses . ' bg-primary/10 grid place-items-center overflow-hidden']) }}>
        <x-ui.icon :name="$icon" class="w-1/2 h-1/2 text-primary" />
    </div>
@endif
