@if($tab === 'attendance')
    <x-ui.card shadow padding="false" class="border-none ring-1 ring-slate-100 dark:ring-slate-800 overflow-hidden">
        <x-ui.table :headers="[
            ['key' => 'date', 'label' => __('Tanggal')],
            ['key' => 'classroom.name', 'label' => __('Kelas')],
            ['key' => 'subject_name', 'label' => __('Materi')],
            ['key' => 'percentage', 'label' => __('Kehadiran'), 'class' => 'text-center']
        ]" :rows="$attendanceData">
            @scope('cell_date', $att)
                <span class="text-xs font-medium text-slate-500 font-mono italic">{{ $att->date->format('d/m/Y') }}</span>
            @endscope

            @scope('cell_classroom_name', $att)
                 <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $att->classroom?->name }}</span>
            @endscope

            @scope('cell_subject_name', $att)
                <span class="text-xs text-slate-500">{{ $att->subject?->name ?? __('Presensi Harian') }}</span>
            @endscope

            @scope('cell_percentage', $att)
                @php 
                    $items = $att->items;
                    $present = $items->filter(fn($i) => $i->status === 'h')->count();
                    $total = $items->count();
                    $percent = $total > 0 ? round(($present / $total) * 100) : 0;
                    $barColor = $percent >= 80 ? 'bg-emerald-500' : ($percent >= 60 ? 'bg-amber-500' : 'bg-rose-500');
                @endphp
                <div class="flex flex-col items-center gap-1.5">
                    <div class="flex items-baseline gap-1">
                        <span class="text-base font-bold text-slate-900 dark:text-white">{{ $percent }}%</span>
                        <span class="text-[10px] font-medium text-slate-400">({{ $present }}/{{ $total }})</span>
                    </div>
                    <div class="w-24 h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden shadow-inner flex">
                        <div class="h-full {{ $barColor }} transition-all duration-1000 ease-out" style="width: {{ $percent }}%"></div>
                    </div>
                </div>
            @endscope
        </x-ui.table>
    </x-ui.card>
@endif
