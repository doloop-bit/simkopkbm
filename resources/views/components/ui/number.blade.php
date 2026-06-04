@props([
    'label' => null,
    'min' => 0,
    'max' => 9999,
    'step' => 1,
])

@php
    $wireModel = $attributes->wire('model');
    $name = $attributes->get('name') ?? $wireModel->value();
    
    $containerClasses = 'inline-flex items-center h-8 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-0.5 shadow-sm transition-all duration-200 focus-within:border-primary/50 focus-within:ring-2 focus-within:ring-primary/10';
    $buttonClasses = 'flex items-center justify-center size-7 rounded-lg text-slate-500 hover:text-primary hover:bg-white dark:hover:bg-slate-800 transition-all duration-200 disabled:opacity-30 disabled:cursor-not-allowed active:scale-90';
    $inputClasses = 'w-10 text-center bg-transparent border-none focus:ring-0 text-xs font-mono font-black text-slate-900 dark:text-white [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none p-0 selection:bg-primary/20';
@endphp

<div class="flex flex-col gap-1.5">
    @if($label)
        <label @if($name) for="{{ $name }}" @endif class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
            {{ $label }}
        </label>
    @endif

    <div x-data="{ 
            value: @entangle($attributes->wire('model')),
            min: {{ $min }},
            max: {{ $max }},
            step: {{ $step }},
            increment() {
                let newVal = parseInt(this.value || 0) + this.step;
                if (newVal <= this.max) this.value = newVal;
            },
            decrement() {
                let newVal = parseInt(this.value || 0) - this.step;
                if (newVal >= this.min) this.value = newVal;
            }
        }" 
        class="{{ $containerClasses }}"
    >
        <button 
            type="button" 
            @click="decrement" 
            :disabled="value <= min"
            class="{{ $buttonClasses }}"
        >
            <x-ui.icon name="o-minus" class="size-3.5" />
        </button>
        
        <input 
            type="number" 
            x-model.number="value"
            min="{{ $min }}"
            max="{{ $max }}"
            step="{{ $step }}"
            @if($name) id="{{ $name }}" name="{{ $name }}" @endif
            {{ $attributes->except(['label', 'min', 'max', 'step'])->class([$inputClasses]) }}
        />

        <button 
            type="button" 
            @click="increment" 
            :disabled="value >= max"
            class="{{ $buttonClasses }}"
        >
            <x-ui.icon name="o-plus" class="size-3.5" />
        </button>
    </div>

    @if($name)
        @error($name)
            <p class="text-[10px] text-rose-500 font-medium">{{ $message }}</p>
        @enderror
    @endif
</div>
