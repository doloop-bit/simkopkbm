@props([
    'label' => null,
    'sm' => false,
    'variant' => 'default',
])

@php
    $wireModel = $attributes->wire('model');
    $name = $attributes->get('name') ?? ($wireModel ? $wireModel->value() : null);
@endphp

<div x-data="{ 
    raw: @entangle($attributes->wire('model')),
    display: '',
    
    init() {
        this.updateDisplay();
        this.$watch('raw', () => this.updateDisplay());
    },
    
    updateDisplay() {
        if (this.raw === null || this.raw === undefined || this.raw === '') {
            this.display = '';
            return;
        }
        
        // Strip decimals and format to Indonesian style: 1.000.000
        let val = Math.floor(parseFloat(this.raw));
        this.display = val.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    },
    
    onInput(e) {
        let val = e.target.value.replace(/[^0-9]/g, '');
        this.raw = val ? parseInt(val) : 0;
        this.updateDisplay();
    },

    onFocus() {
        if (this.raw == 0) {
            this.display = '';
        }
    },

    onBlur() {
        this.updateDisplay();
    }
}" class="w-full">
    <x-ui.input 
        x-model="display"
        x-on:input="onInput($event)"
        x-on:focus="onFocus()"
        x-on:blur="onBlur()"
        {{ $attributes->whereDoesntStartWith('wire:model')->except(['label', 'sm', 'variant'])->merge([
            'label' => $label,
            'sm' => $sm,
            'variant' => $variant,
            'type' => 'text',
        ]) }}
    />
</div>
