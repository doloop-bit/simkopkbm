@php
    $isTransaksi = request()->routeIs('financial.transactions') || request()->routeIs('financial.billings') || request()->routeIs('financial.discounts') || request()->routeIs('financial.categories');
    
    $isAnggaran = request()->routeIs('financial.budget-plans') || request()->routeIs('financial.budget-categories') || request()->routeIs('financial.standard-items');
    
    $tabs = [];
    if($isTransaksi) {
        $tabs = [
            'payments' => [
                'label' => 'Transaksi',
                'label_short' => 'Transaksi',
                'icon' => 'wallet',
                'route' => 'financial.transactions',
                'route_pattern' => 'financial.transactions',
            ],
            'billings' => [
                'label' => 'Tagihan Siswa',
                'label_short' => 'Tagihan',
                'icon' => 'document-text',
                'route' => 'financial.billings',
                'route_pattern' => 'financial.billings',
            ],
            'discounts' => [
                'label' => 'Potongan & Beasiswa',
                'label_short' => 'Potongan',
                'icon' => 'gift',
                'route' => 'financial.discounts',
                'route_pattern' => 'financial.discounts',
            ],
            'categories' => [
                'label' => 'Kategori Biaya',
                'label_short' => 'Kategori',
                'icon' => 'swatch',
                'route' => 'financial.categories',
                'route_pattern' => 'financial.categories',
            ],
        ];
    } elseif($isAnggaran) {
        $tabs = [
            'budget-plans' => [
                'label' => 'RAB / Anggaran',
                'label_short' => 'Anggaran',
                'icon' => 'document-currency-dollar',
                'route' => 'financial.budget-plans',
                'route_pattern' => 'financial.budget-plans',
            ],
            'budget-categories' => [
                'label' => 'Kategori Anggaran',
                'label_short' => 'Kategori',
                'icon' => 'tag',
                'route' => 'financial.budget-categories',
                'route_pattern' => 'financial.budget-categories',
            ],
            'standard-items' => [
                'label' => 'Item Standar',
                'label_short' => 'Item Standar',
                'icon' => 'shopping-cart',
                'route' => 'financial.standard-items',
                'route_pattern' => 'financial.standard-items',
            ],
        ];
    }
@endphp

@if(!empty($tabs))
    {{-- Desktop: Horizontal navbar below header --}}
    <div class="hidden lg:block sticky top-0 z-10 bg-base-100 border-b border-base-300 px-6 py-2">
        <div class="flex items-center gap-2 overflow-x-auto custom-scrollbar pb-1">
            @foreach($tabs as $key => $tab)
                <a 
                    href="{{ route($tab['route']) }}" 
                    wire:navigate 
                    class="flex items-center gap-2 px-4 py-2 rounded-lg transition-all whitespace-nowrap {{ request()->routeIs($tab['route_pattern']) ? 'bg-primary text-primary-content font-bold shadow-md' : 'hover:bg-base-200 opacity-70' }}"
                >
                    <x-ui.icon name="o-{{ $tab['icon'] }}" class="size-4" />
                    <span class="text-sm">{{ $tab['label'] }}</span>
                </a>
            @endforeach
        </div>
    </div>

    {{-- Mobile: Fixed bottom navigation with icons only --}}
    <div class="lg:hidden fixed bottom-16 left-0 right-0 z-40 bg-base-100 border-t border-base-300 safe-area-inset-bottom">
        <nav class="flex items-center justify-around px-2 py-2">
            @foreach($tabs as $key => $tab)
                <a 
                    href="{{ route($tab['route']) }}" 
                    wire:navigate
                    class="flex flex-col items-center justify-center gap-1 px-3 py-1 rounded-lg transition-colors {{ request()->routeIs($tab['route_pattern']) ? 'text-primary' : 'opacity-60' }}"
                >
                    <x-ui.icon name="o-{{ $tab['icon'] }}" class="size-6" />
                    <span class="text-[10px] uppercase font-bold tracking-tighter">{{ $tab['label_short'] }}</span>
                </a>
            @endforeach
        </nav>
    </div>

    {{-- Safe area for devices with notch/home indicator --}}
    <style>
        .safe-area-inset-bottom {
            padding-bottom: env(safe-area-inset-bottom, 0);
        }
    </style>
@endif
