@props([
    'activateByRoute' => false,
])

<nav {{ $attributes->class(['space-y-1']) }} x-data="{ inFlyout: false }">
    {{ $slot }}
</nav>
