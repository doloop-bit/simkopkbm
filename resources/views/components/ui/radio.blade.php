@props([
    'options' => [],
    'optionValue' => 'id',
    'optionLabel' => 'label',
])

@php
    $wireModel = $attributes->wire('model')->value();
    $name = $attributes->get('name') ?? $wireModel;
@endphp

<div class="flex flex-col gap-2">
    @foreach($options as $option)
        @php
            $value = is_array($option) ? ($option[$optionValue] ?? '') : $option;
            $text = is_array($option) ? ($option[$optionLabel] ?? $option[$optionValue] ?? '') : $option;
        @endphp
        <label class="inline-flex items-center gap-2 cursor-pointer select-none">
            <input
                type="radio"
                value="{{ $value }}"
                {{ $attributes->except(['options', 'optionValue', 'optionLabel'])->class(['h-4 w-4 border-slate-300 dark:border-slate-600 text-primary focus:ring-primary/30 dark:bg-slate-800']) }}
            />
            <span class="text-sm text-slate-700 dark:text-slate-300">{{ $text }}</span>
        </label>
    @endforeach
</div>
