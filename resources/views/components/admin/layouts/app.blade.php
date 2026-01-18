<x-admin.layouts.sidebar :title="$title ?? null">
    <flux:main>
        {{ $slot }}
    </flux:main>
</x-admin.layouts.sidebar>
