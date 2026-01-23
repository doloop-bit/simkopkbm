<?php
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('components.admin.layouts.app')] class extends Component {
}; ?>

<div>
    <flux:modal name="test-modal">
        Test Content
    </flux:modal>
    <flux:modal.trigger name="test-modal">
        <flux:button>Open Modal</flux:button>
    </flux:modal.trigger>
</div>
