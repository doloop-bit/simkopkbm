@props([
    'headers' => [],
    'rows' => [],
    'withPagination' => false,
    'striped' => false,
])

<div {{ $attributes->class(['overflow-x-auto']) }}>
    <table class="w-full text-sm text-left">
        <thead class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider border-b border-slate-200 dark:border-slate-700">
            <tr>
                @foreach($headers as $header)
                    <th class="px-4 py-3 {{ $header['class'] ?? '' }}">
                        {{ $header['label'] ?? '' }}
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
            @forelse($rows as $row)
                <tr @class([
                    'hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors',
                    'bg-slate-50/50 dark:bg-slate-800/25' => $striped && $loop->odd,
                ])>
                    @foreach($headers as $header)
                        @php
                            $key = $header['key'] ?? '';
                            $cellSlotName = 'cell_' . str_replace('.', '_', $key);
                        @endphp
                        <td class="px-4 py-3 {{ $header['class'] ?? '' }}">
                            @if(isset($$cellSlotName))
                                {{ $$cellSlotName($row) }}
                            @else
                                {{ data_get($row, $key) }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($headers) }}" class="px-4 py-8 text-center text-slate-400 dark:text-slate-500">
                        Tidak ada data.
                    </td>
                </tr>
            @endforelse
        </tbody>

        @if(isset($append))
            <tfoot>
                {{ $append }}
            </tfoot>
        @endif
    </table>
</div>

@if($withPagination && method_exists($rows, 'links'))
    <div class="mt-4 px-4">
        {{ $rows->links() }}
    </div>
@endif
