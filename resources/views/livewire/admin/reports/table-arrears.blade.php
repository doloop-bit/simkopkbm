@if($tab === 'arrears')
    <x-ui.card shadow padding="false" class="border-none ring-1 ring-slate-100 dark:ring-slate-800 overflow-hidden">
        <div class="p-6 border-b border-slate-50 dark:border-slate-800 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-slate-50/50 dark:bg-slate-900/50">
            <div class="flex items-center gap-3">
                <x-ui.checkbox wire:model.live="selectAll" :label="__('Pilih Semua')" />
                <span class="text-xs font-black text-slate-400 uppercase tracking-widest">{{ count($selected_billings) }} {{ __('Terpilih') }}</span>
            </div>
            <div class="flex items-center gap-2">
                <x-ui.button 
                    :disabled="count($selected_billings) === 0" 
                    wire:click="$set('broadcastModal', true)" 
                    :label="__('Broadcast WhatsApp')" 
                    icon="o-chat-bubble-left-right" 
                    class="btn-primary btn-sm rounded-xl font-bold" 
                />
            </div>
        </div>

        <x-ui.table :headers="[
            ['key' => 'checkbox', 'label' => ''],
            ['key' => 'student_name', 'label' => __('Siswa')],
            ['key' => 'category', 'label' => __('Kategori')],
            ['key' => 'month_label', 'label' => __('Bulan')],
            ['key' => 'amount_label', 'label' => __('Tunggakan'), 'class' => 'text-right'],
        ]" :rows="$arrearsData" with-pagination per-page="perPage" :per-page-values="[10, 25, 50, 100]">
            @scope('cell_checkbox', $billing)
                <x-ui.checkbox wire:model.live="selected_billings" value="{{ $billing->id }}" />
            @endscope

            @scope('cell_student_name', $billing)
                <div class="flex flex-col">
                    <span class="font-bold text-slate-900 dark:text-white">{{ $billing->student?->name ?? __('Siswa Dihapus') }}</span>
                    @php $sp = $billing->student?->studentProfile; @endphp
                    <span class="text-[10px] text-slate-400 font-mono italic">
                        {{ $sp?->classroom?->name ?? '-' }} • {{ $sp?->guardian_phone ?: ($sp?->phone ?: __('No HP Kosong')) }}
                    </span>
                </div>
            @endscope

            @scope('cell_category', $billing)
                <span class="text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-tight">{{ $billing->feeCategory?->name }}</span>
            @endscope

            @scope('cell_month_label', $billing)
                <span class="text-xs font-bold text-slate-500 font-mono uppercase">{{ $billing->month ?? '-' }}</span>
            @endscope

            @scope('cell_amount_label', $billing)
                <div class="text-right flex flex-col">
                    <span class="font-black text-rose-600 font-mono">
                        Rp {{ number_format($billing->amount - $billing->paid_amount, 0, ',', '.') }}
                    </span>
                    @if($billing->paid_amount > 0)
                        <span class="text-[9px] text-slate-500 italic lowercase">{{ __('Dibayar:') }} Rp {{ number_format($billing->paid_amount, 0, ',', '.') }}</span>
                    @endif
                </div>
            @endscope
        </x-ui.table>

        @if($arrearsData->isEmpty())
            <div class="py-20 text-center space-y-4">
                <div class="size-16 mx-auto rounded-full bg-slate-50 dark:bg-slate-900 flex items-center justify-center text-slate-300">
                    <x-ui.icon name="o-check-circle" class="size-8" />
                </div>
                <p class="text-slate-400 italic text-sm font-medium">{{ __('Alhamdulillah, tidak ada tunggakan yang ditemukan untuk filter ini.') }}</p>
            </div>
        @endif
    </x-ui.card>
@endif
