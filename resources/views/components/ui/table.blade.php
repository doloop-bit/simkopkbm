@props([
    'headers' => [],
    'rows' => [],
    'withPagination' => false,
    'perPage' => null,
    'perPageValues' => null,
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
    <div class="p-3 border-t border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900 flex items-center justify-end gap-6 rounded-b-xl">
        
        @if(!empty($perPageValues) && $perPage)
            @php
                $perPageOptions = collect($perPageValues)->map(fn($val) => [
                    'id' => $val, 
                    'name' => $val . ' ' . __('halaman')
                ])->toArray();
            @endphp
            <x-ui.select 
                wire:model.live="{{ $perPage }}" 
                :options="$perPageOptions" 
                option-value="id" 
                option-label="name"
                sm
                dropup
                class="w-36"
            />
        @endif

        <div class="flex items-center gap-4">
            @if($rows instanceof \Illuminate\Pagination\LengthAwarePaginator)
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">
                    {{ $rows->firstItem() ?? 0 }}-{{ $rows->lastItem() ?? 0 }} {{ __('dari') }} {{ $rows->total() }}
                </span>
            @endif

            <div class="flex items-center gap-1 border border-slate-200 dark:border-slate-700 rounded-lg p-0.5 shadow-sm bg-white dark:bg-slate-900">
                <button 
                    wire:click="previousPage('{{ method_exists($rows, 'getPageName') ? $rows->getPageName() : 'page' }}')"
                    @if($rows->onFirstPage()) disabled @endif
                    class="p-1 rounded hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 dark:text-slate-400 disabled:opacity-30 disabled:hover:bg-transparent transition-colors"
                >
                    <x-ui.icon name="o-chevron-left" class="size-4" />
                </button>
                <button 
                    wire:click="nextPage('{{ method_exists($rows, 'getPageName') ? $rows->getPageName() : 'page' }}')"
                    @if(!$rows->hasMorePages()) disabled @endif
                    class="p-1 rounded hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 dark:text-slate-400 disabled:opacity-30 disabled:hover:bg-transparent transition-colors"
                >
                    <x-ui.icon name="o-chevron-right" class="size-4" />
                </button>
            </div>
        </div>
    </div>
@endif
