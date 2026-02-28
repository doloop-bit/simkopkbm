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
            <x-ui.radio 
                wire:model="appearance" 
                x-model="appearance"
                @change="updateTheme($event.target.value)"
                :options="[
                    ['id' => 'light', 'label' => __('Light')],
                    ['id' => 'dark', 'label' => __('Dark')],
                    ['id' => 'system', 'label' => __('System')],
                ]" 
            />
        </div>
    </x-admin.settings.layout>
</section>
