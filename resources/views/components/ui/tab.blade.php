@props([
    'name' => null,
    'label' => null,
])

<button
    type="button"
    @click="activeTab = '{{ $name }}'"
    :class="activeTab === '{{ $name }}'
        ? 'border-primary text-primary font-semibold'
        : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 hover:border-slate-300'"
    {{ $attributes->class(['px-4 py-2.5 text-sm border-b-2 -mb-px whitespace-nowrap transition-colors']) }}
>
    {{ $label ?? $slot }}
</button>
