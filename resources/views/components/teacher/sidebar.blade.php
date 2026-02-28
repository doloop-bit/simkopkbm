<div class="flex flex-col h-full bg-base-200">
    <div class="px-6 py-8 overflow-hidden">
        <a href="{{ route('teacher.dashboard') }}" wire:navigate class="flex items-center gap-3 group px-1 min-w-0">
            <div class="flex aspect-square size-10 items-center justify-center rounded-xl bg-primary text-primary-content shadow-lg group-hover:scale-105 transition-transform shrink-0 overflow-hidden">
                <x-global.app-logo-icon class="size-6 fill-current object-contain" />
            </div>
            <div class="flex flex-col overflow-hidden">
                <span class="text-sm font-black tracking-tight whitespace-nowrap overflow-hidden leading-none uppercase">{{ config('app.name') }}</span>
                <span class="text-[9px] font-bold uppercase tracking-widest opacity-50 whitespace-nowrap overflow-hidden">Portal Guru</span>
            </div>
        </a>
    </div>

    <x-ui.menu activate-by-route class="flex-1 px-4 py-6">
        <x-ui.menu-item
            title="Dashboard"
            icon="o-home"
            link="{{ route('teacher.dashboard') }}"
        />

        <x-ui.menu-sub title="Penilaian & Raport" icon="o-pencil-square">
            <x-ui.menu-item
                title="Raport Kesetaraan"
                icon="o-document-chart-bar"
                link="{{ route('teacher.assessments.grading') }}"
            />

            @if(auth()->user()->teachesPaudLevel())
                <x-ui.menu-item
                    title="Nilai PAUD"
                    icon="o-face-smile"
                    link="{{ route('teacher.assessments.paud') }}"
                />
            @endif
        </x-ui.menu-sub>
    </x-ui.menu>
</div>