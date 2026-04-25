@props([
    'label' => null,
    'icon' => null,
    'options' => [],
    'placeholder' => null,
    'optionValue' => 'id',
    'optionLabel' => 'name',
    'sm' => false,
    'variant' => 'default', // default, subtle
])

@php
    $wireModel = $attributes->wire('model')->value();
    $name = $attributes->get('name') ?? $wireModel;
    
    $baseClasses = 'flex items-center justify-between w-full rounded-xl border transition-all duration-200 focus:outline-none cursor-pointer';
    
    $variantClasses = match($variant) {
        'subtle' => 'bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 focus:border-primary/50 focus:ring-4 focus:ring-primary/10',
        default => 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 focus:border-primary focus:ring-4 focus:ring-primary/10',
    };

    $sizeClasses = $sm ? 'px-2.5 py-1 h-8 text-xs' : 'px-3 py-1.5 text-sm';
    $iconPadding = $icon ? 'pl-10' : '';
@endphp

<div 
    x-data="{ 
        open: false, 
        value: @entangle($attributes->wire('model')),
    }"
    x-on:keydown.escape.window="open = false"
    class="w-full"
>
    @if($label)
        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5 ml-0.5">
            {{ $label }}
        </label>
    @endif

    <div class="relative">
        @if($icon)
            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400 dark:text-slate-500">
                <x-ui.icon :name="$icon" class="w-3.5 h-3.5" />
            </div>
        @endif

        <!-- Trigger Button -->
        <div 
            tabindex="0"
            @click="open = !open"
            @click.away="open = false"
            {{ $attributes->except(['label', 'icon', 'options', 'placeholder', 'optionValue', 'optionLabel', 'sm', 'variant'])->class(['ui-select', $baseClasses, $variantClasses, $sizeClasses, $iconPadding]) }}
        >
            <span class="truncate transition-colors duration-200" 
                :class="value ? 'text-slate-900 dark:text-slate-100' : 'text-slate-400 dark:text-slate-500'" 
                x-text="
                    ({
                        @foreach($options as $option)
                            '{{ (string) data_get($option, $optionValue, $option) }}': '{{ (string) data_get($option, $optionLabel, data_get($option, $optionValue, $option)) }}',
                        @endforeach
                    })[String(value)] || '{{ $placeholder ?? __('-- Pilih --') }}'
                "></span>
            
            <div class="flex items-center gap-3">
                <div class="h-4 w-px bg-slate-200 dark:bg-slate-700 opacity-50"></div>
                <svg class="h-4 w-4 text-slate-400 transition-transform duration-300" :class="{ 'rotate-180 text-primary': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path>
                </svg>
            </div>
        </div>

        <!-- Dropdown Panel -->
        <div 
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-1 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-1 scale-95"
            class="absolute z-[100] mt-3 w-full bg-white dark:bg-slate-900 rounded-2xl shadow-[0_20px_50px_rgba(0,0,0,0.15)] dark:shadow-[0_20px_50px_rgba(0,0,0,0.4)] border border-slate-100 dark:border-slate-800 py-2 ring-1 ring-black/5 overflow-hidden"
            style="display: none;"
        >
            <div class="max-h-64 overflow-y-auto px-1.5 space-y-0.5 scrollbar-thin scrollbar-thumb-slate-200 dark:scrollbar-thumb-slate-800">
                @if($placeholder)
                    <div 
                        @click="value = null; open = false"
                        class="flex items-center px-3 py-2.5 text-sm text-slate-400 dark:text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800/50 rounded-xl cursor-pointer transition-colors"
                    >
                        {{ $placeholder }}
                    </div>
                @endif

                @foreach($options as $option)
                    @php
                        $val = (string) data_get($option, $optionValue, $option);
                        $lbl = (string) data_get($option, $optionLabel, $val);
                    @endphp
                    <div 
                        @click="value = '{{ $val }}'; open = false"
                        class="flex items-center justify-between px-3 py-2 text-sm rounded-xl cursor-pointer transition-all duration-200 hover:bg-slate-50 dark:hover:bg-slate-800/50 group/item"
                        :class="String(value) === '{{ $val }}' ? 'bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white font-bold' : 'text-slate-700 dark:text-slate-300'"
                    >
                        <span class="truncate" :class="{ 'translate-x-1': String(value) === '{{ $val }}' }">{{ $lbl }}</span>
                        
                        <div x-show="String(value) === '{{ $val }}'" class="text-primary">
                            <x-ui.icon name="o-check" class="w-4 h-4 stroke-[3]" />
                        </div>
                    </div>
                @endforeach
                
                @if($slot->isNotEmpty())
                    <div class="pt-1 border-t border-slate-100 dark:border-slate-800 mt-1">
                        {{ $slot }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if($name)
        @error($name)
            <p class="mt-2 ml-1 text-[11px] font-semibold text-rose-500 flex items-center gap-1.5">
                <x-ui.icon name="o-exclamation-circle" class="w-3.5 h-3.5" />
                {{ $message }}
            </p>
        @enderror
    @endif
</div>
