@if(auth()->user()?->isSiswa())
    <livewire:student.dashboard />
@elseif(auth()->user()?->isGuru())
    <livewire:teacher.dashboard />
@else
    <x-layouts.app :title="__('Dashboard')">
        <livewire:admin.dashboard />
    </x-layouts.app>
@endif
