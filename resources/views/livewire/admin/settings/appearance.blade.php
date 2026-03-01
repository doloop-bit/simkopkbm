<?php

declare(strict_types=1);

use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('components.admin.layouts.app')] class extends Component {
    public string $appearance = 'system';

    public function mount(): void
    {
        // This is primarily handled via Alpine/LocalStorage in the view for instant feedback
        // but we keep the state here for Livewire consistency.
    }
}; ?>

<section class="w-full p-6">
    @include('partials.settings-heading')

    <x-admin.settings.layout :heading="__('Appearance')" :subheading="__('Update the appearance settings for your account')">
        <div 
            x-data="{ 
                appearance: localStorage.getItem('theme') || 'system',
                updateTheme(theme) {
                    this.appearance = theme;
                    localStorage.setItem('theme', theme);
                    
                    if (theme === 'system') {
                        if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
                            document.documentElement.classList.add('dark');
                        } else {
                            document.documentElement.classList.remove('dark');
                        }
                    } else if (theme === 'dark') {
                        document.documentElement.classList.add('dark');
                    } else {
                        document.documentElement.classList.remove('dark');
                    }
                }
            }"
            class="mt-6"
        >
            <div class="inline-flex rounded-xl p-1 bg-slate-100 dark:bg-slate-900 w-auto border border-slate-200 dark:border-slate-800">
                <label class="relative flex cursor-pointer items-center justify-center gap-2 rounded-lg px-4 py-2 transition-all"
                       x-bind:class="appearance === 'light' ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-800 dark:text-white' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300'">
                    <input 
                        type="radio" 
                        name="appearance" 
                        value="light" 
                        wire:model="appearance"
                        x-model="appearance"
                        @change="updateTheme($event.target.value)"
                        class="sr-only"
                    >
                    <x-heroicon-o-sun class="h-5 w-5" />
                    <span class="text-sm font-medium">{{ __('Light') }}</span>
                </label>

                <label class="relative flex cursor-pointer items-center justify-center gap-2 rounded-lg px-4 py-2 transition-all"
                       x-bind:class="appearance === 'dark' ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-800 dark:text-white' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300'">
                    <input 
                        type="radio" 
                        name="appearance" 
                        value="dark" 
                        wire:model="appearance"
                        x-model="appearance"
                        @change="updateTheme($event.target.value)"
                        class="sr-only"
                    >
                    <x-heroicon-s-moon class="h-5 w-5" />
                    <span class="text-sm font-medium">{{ __('Dark') }}</span>
                </label>

                <label class="relative flex cursor-pointer items-center justify-center gap-2 rounded-lg px-4 py-2 transition-all"
                       x-bind:class="appearance === 'system' ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-800 dark:text-white' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300'">
                    <input 
                        type="radio" 
                        name="appearance" 
                        value="system" 
                        wire:model="appearance"
                        x-model="appearance"
                        @change="updateTheme($event.target.value)"
                        class="sr-only"
                    >
                    <x-heroicon-o-computer-desktop class="h-5 w-5" />
                    <span class="text-sm font-medium">{{ __('System') }}</span>
                </label>
            </div>
        </div>
    </x-admin.settings.layout>
</section>
