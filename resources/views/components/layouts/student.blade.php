@props([
    'title' => null,
])

<x-layouts.dashboard
    :title="$title"
    :dashboard-route="route('student.dashboard')"
>
    <x-slot:sidebarMenu>
        <x-layouts.student-sidebar />
    </x-slot:sidebarMenu>

    {{ $slot }}
</x-layouts.dashboard>
