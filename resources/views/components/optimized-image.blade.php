@props([
    'src' => null,
    'webpSrc' => null,
    'srcset' => null,
    'webpSrcset' => null,
    'alt' => '',
    'class' => '',
    'lazy' => true,
    'sizes' => '100vw',
    'width' => null,
    'height' => null,
])

@php
    $lazyAttributes = $lazy ? 'loading="lazy" decoding="async"' : '';
    $dimensionAttributes = '';
    
    if ($width && $height) {
        $dimensionAttributes = "width=\"{$width}\" height=\"{$height}\"";
    }
@endphp

<picture>
    @if($webpSrcset)
        <source srcset="{{ $webpSrcset }}" sizes="{{ $sizes }}" type="image/webp">
    @elseif($webpSrc)
        <source srcset="{{ $webpSrc }}" type="image/webp">
    @endif
    
    @if($srcset)
        <source srcset="{{ $srcset }}" sizes="{{ $sizes }}">
    @endif
    
    <img 
        src="{{ $src }}" 
        alt="{{ $alt }}" 
        class="{{ $class }}"
        {!! $lazyAttributes !!}
        {!! $dimensionAttributes !!}
        {{ $attributes }}
    >
</picture>