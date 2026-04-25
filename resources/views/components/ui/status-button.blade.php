@props([
    'label' => null,
    'count' => null,
    'active' => false,
    'color' => 'slate',
])

@php
    $variantClasses = match($color) {
        'amber' => $active 
            ? 'bg-amber-500 text-white border-none shadow-lg shadow-amber-500/20' 
            : 'btn-ghost text-amber-600 hover:bg-amber-50 dark:text-amber-400 dark:hover:bg-amber-900/20',
        'sky' => $active 
            ? 'bg-sky-500 text-white border-none shadow-lg shadow-sky-500/20' 
            : 'btn-ghost text-sky-600 hover:bg-sky-50 dark:text-sky-400 dark:hover:bg-sky-900/20',
        'emerald' => $active 
            ? 'bg-emerald-500 text-white border-none shadow-lg shadow-emerald-500/20' 
            : 'btn-ghost text-emerald-600 hover:bg-emerald-50 dark:text-emerald-400 dark:hover:bg-emerald-900/20',
        'rose' => $active 
            ? 'bg-rose-500 text-white border-none shadow-lg shadow-rose-500/20' 
            : 'btn-ghost text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-900/20',
        default => $active 
            ? 'bg-slate-900 text-white border-none shadow-lg shadow-slate-900/20 dark:bg-slate-700' 
            : 'btn-ghost text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800',
    };
@endphp

<button 
    {{ $attributes->merge([
        'type' => 'button',
        'class' => 'inline-flex items-center justify-center rounded-full px-5 py-2 text-xs font-semibold tracking-tight transition-all duration-300 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed ' . $variantClasses
    ]) }}
>
    <span>{{ $label }}</span>
    
    @if($count !== null)
        <span class="ml-2 text-[10px] opacity-70 font-bold">({{ $count }})</span>
    @endif
    
    {{ $slot }}
</button>
