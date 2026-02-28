@props([
    'label' => null,
    'options' => [],
    'placeholder' => null,
    'optionValue' => 'id',
    'optionLabel' => 'name',
    'sm' => false,
])

@php
    $wireModel = $attributes->wire('model')->value();
    $name = $attributes->get('name') ?? $wireModel;
    $selectClasses = 'w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm text-slate-900 dark:text-slate-100 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all duration-200 appearance-none';
    $sizeClasses = $sm ? 'px-2.5 py-1.5 text-xs' : 'px-3 py-2.5';
@endphp

<div>
    @if($label)
        <label @if($name) for="{{ $name }}" @endif class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
            {{ $label }}
        </label>
    @endif

    <div class="relative">
        <select
            @if($name) id="{{ $name }}" name="{{ $name }}" @endif
            {{ $attributes->except(['label', 'options', 'placeholder', 'optionValue', 'optionLabel', 'sm'])->class([$selectClasses, $sizeClasses]) }}
        >
            @if($slot->isNotEmpty())
                {{ $slot }}
            @else
                @if($placeholder)
                    <option value="">{{ $placeholder }}</option>
                @endif
                
                @foreach($options as $option)
                    @php
                        $value = data_get($option, $optionValue, $option);
                        $text = data_get($option, $optionLabel, $value);
                    @endphp
                    <option value="{{ $value }}">{{ $text }}</option>
                @endforeach
            @endif
        </select>

        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
            <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </div>
    </div>

    @if($name)
        @error($name)
            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
        @enderror
    @endif
</div>
