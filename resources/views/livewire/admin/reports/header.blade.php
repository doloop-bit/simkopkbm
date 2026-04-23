<x-ui.header :title="__('Analitik & Pelaporan')" :subtitle="__('Pantau indikator performa utama keuangan dan tingkat partisipasi akademik secara komprehensif.')" separator />

{{-- Report Navigation Tabs --}}
<div class="flex items-center gap-1 p-1 bg-slate-100 dark:bg-slate-900 rounded-2xl w-fit shadow-inner">
    <x-ui.button 
        wire:click="$set('tab', 'financial')" 
        :label="__('Keuangan')" 
        icon="o-banknotes" 
        class="rounded-xl px-6 font-semibold py-2 h-auto {{ $tab === 'financial' ? 'bg-white text-primary shadow-sm border-none' : 'btn-ghost text-slate-500' }}" 
    />
    <x-ui.button 
        wire:click="$set('tab', 'attendance')" 
        :label="__('Presensi')" 
        icon="o-clipboard-document-check" 
        class="rounded-xl px-6 font-semibold py-2 h-auto {{ $tab === 'attendance' ? 'bg-white text-primary shadow-sm border-none' : 'btn-ghost text-slate-500' }}" 
    />
    <x-ui.button 
        wire:click="$set('tab', 'arrears')" 
        :label="__('Tunggakan')" 
        icon="o-exclamation-triangle" 
        class="rounded-xl px-6 font-semibold py-2 h-auto {{ $tab === 'arrears' ? 'bg-white text-primary shadow-sm border-none' : 'btn-ghost text-slate-500' }}" 
    />
</div>
