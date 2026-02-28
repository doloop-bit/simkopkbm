@props([
    'value' => null,
    'label' => null,
])

<span {{ $attributes->class([
    'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold transition-colors',
]) }}>
    {{ $value ?? $label ?? $slot }}
</span>
