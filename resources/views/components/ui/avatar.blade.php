@props([
    'image' => null,
])

@php
    $sizeClasses = 'w-8 h-8';
@endphp

@if($image)
    <img src="{{ $image }}" {{ $attributes->class([$sizeClasses, 'rounded-full object-cover']) }} alt="" />
@else
    <div {{ $attributes->class([$sizeClasses, 'rounded-full bg-primary/10 flex items-center justify-center']) }}>
        <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
        </svg>
    </div>
@endif
