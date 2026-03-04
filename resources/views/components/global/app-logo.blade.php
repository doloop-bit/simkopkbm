<div class="flex items-center gap-3" {{ $attributes }}>
    <div class="flex aspect-square size-10 items-center justify-center rounded-xl bg-primary text-primary-content shadow-lg shadow-primary/20 shrink-0">
        <x-global.app-logo-icon class="size-7 fill-current" />
    </div>
    <div class="flex flex-col overflow-hidden">
        <span class="text-sm font-black tracking-tight leading-none uppercase whitespace-nowrap overflow-hidden">{{ config('app.name') }}</span>
        <span class="text-[10px] opacity-60 font-medium whitespace-nowrap overflow-hidden">Sistem Informasi Koperasi</span>
    </div>
</div>
