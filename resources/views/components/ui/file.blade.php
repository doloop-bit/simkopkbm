@props([
    'label' => null,
    'accept' => null,
    'hint' => null,
])

@php
    $wireModel = $attributes->wire('model')->value();
    $name = $attributes->get('name') ?? $wireModel;
    
    $classes = [
        'block w-full text-sm text-slate-500 dark:text-slate-400 cursor-pointer overflow-hidden p-0',
        'bg-slate-100 dark:bg-slate-800/50 rounded-xl shadow-inner ring-1 ring-slate-200 dark:ring-slate-700',
        'file:mr-4 file:py-2.5 file:px-6 file:rounded-none file:border-0 file:text-[10px] file:font-bold',
        'file:bg-primary file:text-white hover:file:bg-primary/90 file:cursor-pointer file:transition-all file:uppercase file:tracking-widest'
    ];
@endphp

<div>
    @if($label)
        <label @if($name) for="{{ $name }}" @endif class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
            {{ $label }}
        </label>
    @endif

    <input type="file"
        @if($accept) accept="{{ $accept }}" @endif
        {{ $attributes->merge(['id' => $name, 'name' => $name])->except(['label', 'accept', 'hint'])->class($classes) }}
    />

    @if($hint)
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $hint }}</p>
    @endif

    @if($name)
        @error($name)
            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
        @enderror
    @endif
</div>
