@props([
    'activateByRoute' => false,
])

<nav {{ $attributes->class(['space-y-1']) }}>
    {{ $slot }}
</nav>
