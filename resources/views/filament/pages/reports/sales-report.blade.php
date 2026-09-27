<x-filament-panels::page>

    {{-- Header --}}
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">Laporan Penjualan</h1>
            <div class="mt-1.5 flex items-center gap-3">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-red-100 px-2.5 py-1 text-xs font-medium text-red-600 dark:bg-red-500/20 dark:text-red-400">
                    <x-heroicon-o-building-storefront class="h-3.5 w-3.5" />
                    {{ $this->tenant?->name ?? 'Tenant' }}
                </span>
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
                </span>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('reports.sales.pdf', ['tenant' => $this->tenant?->slug ?? $this->tenant?->id, 'startDate' => $startDate, 'endDate' => $endDate, 'statusFilter' => $statusFilter]) }}" target="_blank" class="btn btn-secondary btn-md">
                <x-heroicon-o-document-arrow-down class="h-4 w-4" />
                PDF
            </a>
            <a href="{{ route('reports.sales.csv', ['tenant' => $this->tenant?->slug ?? $this->tenant?->id, 'startDate' => $startDate, 'endDate' => $endDate, 'statusFilter' => $statusFilter]) }}" target="_blank" class="btn btn-secondary btn-md">
                <x-heroicon-o-arrow-down-tray class="h-4 w-4" />
                CSV
            </a>
        </div>
    </div>

    {{-- Date Range & Filters --}}
    <div class="mb-6 flex flex-wrap items-center gap-3">
        <div class="flex items-center gap-1 rounded-lg bg-gray-100 p-1 dark:bg-gray-800">
            @foreach(['today' => 'Hari Ini', 'yesterday' => 'Kemarin', 'week' => 'Minggu Ini', 'month' => 'Bulan Ini'] as $key => $label)
                <button type="button" wire:click="setDateRange('{{ $key }}')"
                    class="rounded-md px-3 py-1.5 text-xs font-medium transition-colors {{ $dateRange === $key ? 'bg-white text-gray-900 shadow-sm dark:bg-gray-700 dark:text-white' : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        <div class="flex items-center gap-2">
            <x-filament::input.wrapper>
                <x-filament::input type="date" wire:model.live="startDate" />
            </x-filament::input.wrapper>
            <span class="text-gray-400">→</span>
            <x-filament::input.wrapper>
                <x-filament::input type="date" wire:model.live="endDate" />
            </x-filament::input.wrapper>
        </div>

        {{-- Status Filter --}}
        <x-filament::input.wrapper>
            <x-filament::input.select wire:model.live="statusFilter">
                <option value="">Semua Status</option>
                <option value="completed">Completed</option>
                <option value="pending">Pending</option>
                <option value="open">Open</option>
                <option value="cancelled">Cancelled</option>
            </x-filament::input.select>
        </x-filament::input.wrapper>
    </div>

    {{-- Stats Grid --}}
    <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
        {{-- Total Revenue --}}
        <div class="card-hover p-4">
            <div class="flex items-start gap-3">
                <div class="stat-icon gradient-bg text-white shrink-0">
                    <x-heroicon-o-currency-dollar class="h-5 w-5" />
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-[10px] font-medium text-gray-500 dark:text-gray-400">Total Penjualan</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white leading-tight truncate" title="Rp {{ number_format($this->totalRevenue, 0, ',', '.') }}">Rp {{ number_format($this->totalRevenue, 0, ',', '.') }}</p>
                </div>
            </div>
        </div>

        {{-- Transaction Count --}}
        <div class="card-hover p-4">
            <div class="flex items-start gap-3">
                <div class="stat-icon bg-blue-100 text-blue-600 dark:bg-blue-500/20 dark:text-blue-400 shrink-0">
                    <x-heroicon-o-document-text class="h-5 w-5" />
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-[10px] font-medium text-gray-500 dark:text-gray-400">Transaksi</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white leading-tight">{{ number_format($this->totalSales, 0, ',', '.') }}</p>
                </div>
            </div>
        </div>

        {{-- Profit --}}
        <div class="card-hover p-4">
            <div class="flex items-start gap-3">
                <div class="stat-icon bg-emerald-100 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-400 shrink-0">
                    <x-heroicon-o-chart-bar class="h-5 w-5" />
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-[10px] font-medium text-gray-500 dark:text-gray-400">Profit</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white leading-tight truncate" title="Rp {{ number_format($this->totalProfit, 0, ',', '.') }}">Rp {{ number_format($this->totalProfit, 0, ',', '.') }}</p>
                    <p class="text-[10px] text-gray-500 leading-tight">Margin: {{ number_format($this->profitMargin, 1) }}%</p>
                </div>
            </div>
        </div>

        {{-- Average --}}
        <div class="card-hover p-4">
            <div class="flex items-start gap-3">
                <div class="stat-icon bg-purple-100 text-purple-600 dark:bg-purple-500/20 dark:text-purple-400 shrink-0">
                    <x-heroicon-o-calculator class="h-5 w-5" />
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-[10px] font-medium text-gray-500 dark:text-gray-400">Rata-rata</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white leading-tight truncate" title="Rp {{ number_format($this->averageTransaction, 0, ',', '.') }}">Rp {{ number_format($this->averageTransaction, 0, ',', '.') }}</p>
                </div>
            </div>
        </div>

        {{-- PPN --}}
        <div class="card-hover p-4">
            <div class="flex items-start gap-3">
                <div class="stat-icon bg-amber-100 text-amber-600 dark:bg-amber-500/20 dark:text-amber-400 shrink-0">
                    <x-heroicon-o-receipt-percent class="h-5 w-5" />
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-[10px] font-medium text-gray-500 dark:text-gray-400">Total PPN</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white leading-tight truncate" title="Rp {{ number_format($this->totalTax, 0, ',', '.') }}">Rp {{ number_format($this->totalTax, 0, ',', '.') }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Low Stock Alert --}}
    @if($this->lowStockIngredients->count() > 0)
        <div class="mb-6 overflow-hidden rounded-xl border border-red-200 bg-red-50 dark:border-red-500/30 dark:bg-red-500/10">
            <div class="flex items-start gap-4 p-4">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-red-100 text-red-600 dark:bg-red-500/20 dark:text-red-400">
                    <x-heroicon-o-exclamation-triangle class="h-5 w-5" />
                </div>
                <div class="flex-1">
                    <h3 class="text-sm font-semibold text-red-800 dark:text-red-300">Peringatan Stok Rendah</h3>
                    <p class="mt-1 text-xs text-red-700 dark:text-red-400">Bahan berikut sudah mencapai stok minimum:</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach($this->lowStockIngredients as $ingredient)
                            <span class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2.5 py-1 text-xs font-medium text-red-700 dark:bg-red-500/20 dark:text-red-400">
                                {{ $ingredient->name }}: {{ (int) $ingredient->stock }} {{ $ingredient->unit }}
                            </span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Two Column Layout --}}
    <div class="mb-6 grid grid-cols-1 gap-5 lg:grid-cols-2">
        {{-- Top Products --}}
        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
            <div class="section-header">
                <div class="flex items-center gap-2">
                    <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-amber-100 text-amber-600 dark:bg-amber-500/20 dark:text-amber-400">
                        <x-heroicon-o-trophy class="h-4 w-4" />
                    </div>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Produk Terlaris</h2>
                </div>
            </div>
            <div class="p-4">
                @if($this->topProducts->count() > 0)
                    <div class="space-y-3">
                        @foreach($this->topProducts as $index => $product)
                            <div class="flex items-center gap-3">
                                <div class="flex h-8 w-8 items-center justify-center rounded-full {{ $index === 0 ? 'bg-amber-100 text-amber-600 dark:bg-amber-500/20' : 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400' }}">
                                    @if($index === 0)
                                        <x-heroicon-o-trophy class="h-4 w-4" />
                                    @else
                                        <span class="text-xs font-bold">{{ $index + 1 }}</span>
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="truncate text-sm font-medium text-gray-900 dark:text-white">{{ $product->product_name }}</p>
                                    <p class="text-xs text-gray-500">{{ number_format((float) $product->total_qty, 0) }} terjual</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-bold text-red-500">Rp {{ number_format((float) $product->total_sales, 0, ',', '.') }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="py-12 text-center">
                        <x-heroicon-o-trophy class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600" />
                        <p class="mt-2 text-sm text-gray-500">Belum ada produk terjual</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Ingredient Usage --}}
        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
            <div class="section-header">
                <div class="flex items-center gap-2">
                    <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-100 text-blue-600 dark:bg-blue-500/20 dark:text-blue-400">
                        <x-heroicon-o-cube class="h-4 w-4" />
                    </div>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Penggunaan Bahan Baku</h2>
                </div>
            </div>
            <div class="p-4">
                @if($this->ingredientUsage->count() > 0)
                    <div class="space-y-3">
                        @foreach($this->ingredientUsage as $usage)
                            <div class="flex items-center gap-3">
                                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-100 text-blue-600 dark:bg-blue-500/20 dark:text-blue-400">
                                    <x-heroicon-o-cube class="h-4 w-4" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="truncate text-sm font-medium text-gray-900 dark:text-white">{{ $usage['name'] }}</p>
                                    <p class="text-xs text-gray-500">Stok: {{ (int) $usage['stock'] }} {{ $usage['unit'] }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-semibold text-red-600 dark:text-red-400">
                                        -{{ number_format($usage['quantity'], 0) }} {{ $usage['unit'] }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="py-12 text-center">
                        <x-heroicon-o-cube class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600" />
                        <p class="mt-2 text-sm text-gray-500">Belum ada penggunaan bahan</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Payment Methods --}}
    <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
        @foreach($this->salesByPaymentMethod as $method => $data)
            @php
                $colorClasses = match($data['color'] ?? 'gray') {
                    'emerald' => 'bg-emerald-500 text-white',
                    'blue' => 'bg-blue-500 text-white',
                    'purple' => 'bg-purple-500 text-white',
                    'indigo' => 'bg-indigo-500 text-white',
                    'violet' => 'bg-violet-500 text-white',
                    'pink' => 'bg-pink-500 text-white',
                    default => 'bg-gray-500 text-white',
                };
            @endphp
            <div class="card-hover p-3 flex items-center gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg {{ $colorClasses }}">
                    @if($data['icon'] === 'banknotes')
                        <x-heroicon-o-banknotes class="h-5 w-5" />
                    @elseif($data['icon'] === 'qr-code')
                        <x-heroicon-o-qr-code class="h-5 w-5" />
                    @elseif($data['icon'] === 'building-office')
                        <x-heroicon-o-building-office class="h-5 w-5" />
                    @elseif($data['icon'] === 'building-library')
                        <x-heroicon-o-building-library class="h-5 w-5" />
                    @else
                        <x-heroicon-o-credit-card class="h-5 w-5" />
                    @endif
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-[10px] text-gray-500 dark:text-gray-400 truncate">{{ $data['label'] }}</p>
                    <p class="text-sm font-bold text-gray-900 dark:text-white truncate">Rp {{ number_format($data['amount'], 0, ',', '.') }}</p>
                    <p class="text-[10px] text-gray-500">{{ $data['count'] }} transaksi</p>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Transactions Table --}}
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
        <div class="section-header">
            <div class="flex items-center gap-2">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-red-100 text-red-600 dark:bg-red-500/20 dark:text-red-400">
                    <x-heroicon-o-document-text class="h-4 w-4" />
                </div>
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">Detail Transaksi</h2>
            </div>
            <div class="flex items-center gap-3">
                {{-- Search --}}
                <div class="relative">
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="tableSearch"
                        placeholder="    Cari invoice..."
                        class="h-8 rounded-lg border border-gray-200 bg-white px-3 pl-9 text-sm focus:border-red-500 focus:ring-1 focus:ring-red-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    />
                    <x-heroicon-o-magnifying-glass class="absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                </div>
            </div>
        </div>

        <div class="overflow-x-auto" style="max-height: 500px; overflow-y: auto;">
            <table class="w-full">
                <thead class="sticky top-0 z-10">
                    <tr class="table-header">
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Invoice</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Tanggal</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Kasir</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Customer</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Subtotal</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">PPN</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Metode</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($this->sales as $sale)
                        <tr class="table-row">
                            <td class="px-4 py-3">
                                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $sale->invoice_number }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-sm text-gray-600 dark:text-gray-400">{{ $sale->created_at->format('d/m/Y H:i') }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-sm text-gray-600 dark:text-gray-400">{{ $sale->user?->name ?? '-' }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-sm text-gray-600 dark:text-gray-400">{{ $sale->customer?->name ?? 'Walk-in' }}</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Rp {{ number_format((float) $sale->subtotal, 0, ',', '.') }}</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <span class="text-sm text-green-600 dark:text-green-400">+ Rp {{ number_format((float) $sale->tax, 0, ',', '.') }}</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <span class="text-sm font-semibold text-gray-900 dark:text-white">Rp {{ number_format((float) $sale->grand_total, 0, ',', '.') }}</span>
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $badgeClass = match($sale->payment_method) {
                                        'cash' => 'badge-success',
                                        'qris' => 'badge-info',
                                        'qris_manual' => 'badge-violet',
                                        'va' => 'badge-indigo',
                                        'transfer' => 'badge-transfer',
                                        'transfer_manual' => 'badge-transfer',
                                        default => 'badge-warning',
                                    };
                                    $labels = [
                                        'cash' => 'Tunai',
                                        'qris' => 'QRIS Auto',
                                        'qris_manual' => 'QRIS Manual',
                                        'va' => 'VA',
                                        'transfer' => 'Transfer',
                                        'transfer_manual' => 'Transfer Manual',
                                    ];
                                @endphp
                                <span class="badge {{ $badgeClass }}">
                                    {{ $labels[$sale->payment_method] ?? ucfirst($sale->payment_method) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12">
                                <div class="empty-state">
                                    <div class="empty-state-icon">
                                        <x-heroicon-o-document-text class="h-7 w-7" />
                                    </div>
                                    <h3 class="mt-3 text-sm font-semibold text-gray-900 dark:text-white">Tidak ada transaksi</h3>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Tidak ada transaksi dalam periode ini</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>

</x-filament-panels::page>
