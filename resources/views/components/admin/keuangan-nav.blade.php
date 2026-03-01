@php
    $isTransaksi = request()->routeIs('financial.transactions') || request()->routeIs('financial.billings') || request()->routeIs('financial.discounts') || request()->routeIs('financial.categories');

    $isAnggaran = request()->routeIs('financial.budget-plans') || request()->routeIs('financial.budget-categories') || request()->routeIs('financial.standard-items');

    $tabs = [];
    if ($isTransaksi) {
        $tabs = [
            'payments' => [
                'label' => 'Transaksi',
                'label_short' => 'Transaksi',
                'icon' => 'o-wallet',
                'route' => 'financial.transactions',
                'route_pattern' => 'financial.transactions',
            ],
            'billings' => [
                'label' => 'Tagihan Siswa',
                'label_short' => 'Tagihan',
                'icon' => 'o-document-text',
                'route' => 'financial.billings',
                'route_pattern' => 'financial.billings',
            ],
            'discounts' => [
                'label' => 'Potongan & Beasiswa',
                'label_short' => 'Potongan',
                'icon' => 'o-gift',
                'route' => 'financial.discounts',
                'route_pattern' => 'financial.discounts',
            ],
            'categories' => [
                'label' => 'Kategori Biaya',
                'label_short' => 'Kategori',
                'icon' => 'o-swatch',
                'route' => 'financial.categories',
                'route_pattern' => 'financial.categories',
            ],
        ];
    } elseif ($isAnggaran) {
        $tabs = [
            'budget-plans' => [
                'label' => 'RAB / Anggaran',
                'label_short' => 'Anggaran',
                'icon' => 'o-document-currency-dollar',
                'route' => 'financial.budget-plans',
                'route_pattern' => 'financial.budget-plans',
            ],
            'budget-categories' => [
                'label' => 'Kategori Anggaran',
                'label_short' => 'Kategori',
                'icon' => 'o-tag',
                'route' => 'financial.budget-categories',
                'route_pattern' => 'financial.budget-categories',
            ],
            'standard-items' => [
                'label' => 'Item Standar',
                'label_short' => 'Item Standar',
                'icon' => 'o-shopping-cart',
                'route' => 'financial.standard-items',
                'route_pattern' => 'financial.standard-items',
            ],
        ];
    }
@endphp

<x-admin.sub-nav :tabs="$tabs" />
