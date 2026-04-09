<x-ui.card shadow padding="false" class="border-none ring-1 ring-slate-100 dark:ring-slate-800 bg-slate-50/30 dark:bg-slate-900/10">
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-end">
            @if($tab === 'financial' || $tab === 'arrears')
                <div class="md:col-span-4">
                    <x-ui.select 
                        wire:model.live="level_id" 
                        :label="__('Jenjang')" 
                        :placeholder="(auth()->user()->isAdmin() || auth()->user()->isYayasan()) ? __('Seluruh Jenjang') : null"
                        :options="$levels"
                        :readonly="auth()->user()->isTreasurer()"
                    />
                </div>
            @endif

            @if($tab === 'financial')
                <div class="md:col-span-3">
                    <x-ui.input wire:model.live="start_date" type="date" :label="__('Rentang Awal')" />
                </div>
                <div class="md:col-span-3">
                    <x-ui.input wire:model.live="end_date" type="date" :label="__('Rentang Akhir')" />
                </div>
            @endif

            @if($tab === 'attendance')
                <div class="md:col-span-5">
                    <x-ui.select 
                        wire:model.live="academic_year_id" 
                        :label="__('Tahun Ajaran')" 
                        :options="$years"
                    />
                </div>
                <div class="md:col-span-5">
                    <x-ui.select 
                        wire:model.live="classroom_id" 
                        :label="__('Kelas / Rombel')" 
                        :placeholder="__('Seluruh Kelas')"
                        :options="$classrooms"
                    />
                </div>
            @endif
            
            <div class="{{ $tab === 'attendance' ? 'md:col-span-2' : 'md:col-span-2' }}">
                {{-- Export button removed --}}
            </div>
        </div>
    </div>
</x-ui.card>
