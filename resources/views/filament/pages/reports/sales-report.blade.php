<x-filament-panels::page>

    {{-- Header --}}
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Laporan Penjualan</h1>
            <div class="mt-1.5 flex items-center gap-3">
                <span class="badge-info">
                    🏪 {{ $this->tenant?->name ?? 'Tenant' }}
                </span>
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
                </span>
            </div>
        </div>
    </div>

    {{-- Date Range --}}
    <div class="mb-6 flex flex-wrap items-center gap-3">
        <div class="flex rounded-xl border border-gray-200 bg-white p-1 dark:border-gray-700 dark:bg-gray-800">
            @foreach(['today' => 'Hari Ini', 'yesterday' => 'Kemarin', 'week' => 'Minggu Ini', 'month' => 'Bulan Ini'] as $key => $label)
                <button type="button" wire:click="setDateRange('{{ $key }}')"
                    class="btn btn-sm {{ $dateRange === $key ? 'btn-primary' : 'btn-ghost' }}">
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

        <button type="button" wire:click="exportToCsv" wire:loading.attr="disabled" class="btn btn-secondary btn-md ml-auto">
            <x-heroicon-s-arrow-down-tray wire:target="exportToCsv" wire:loading class="h-4 w-4 animate-spin" />
            <x-heroicon-o-arrow-down-tray wire:target="exportToCsv" wire:loading.remove class="h-4 w-4" />
            <span wire:loading wire:target="exportToCsv">Mengekspor...</span>
            <span wire:loading.remove wire:target="exportToCsv">Export CSV</span>
        </button>
    </div>

    {{-- Stats Grid --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {{-- Total Revenue --}}
        <div class="card-hover p-5">
            <div class="stat-icon bg-green-100 text-green-600 dark:bg-green-500/20 dark:text-green-400">
                <x-heroicon-o-currency-dollar class="h-6 w-6" />
            </div>
            <div class="mt-4">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Penjualan</p>
                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">Rp {{ number_format($this->totalRevenue, 0, ',', '.') }}</p>
            </div>
        </div>

        {{-- Transaction Count --}}
        <div class="card-hover p-5">
            <div class="stat-icon bg-blue-100 text-blue-600 dark:bg-blue-500/20 dark:text-blue-400">
                <x-heroicon-o-document-text class="h-6 w-6" />
            </div>
            <div class="mt-4">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Jumlah Transaksi</p>
                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($this->totalSales, 0, ',', '.') }}</p>
            </div>
        </div>

        {{-- Average --}}
        <div class="card-hover p-5">
            <div class="stat-icon bg-purple-100 text-purple-600 dark:bg-purple-500/20 dark:text-purple-400">
                <x-heroicon-o-chart-bar class="h-6 w-6" />
            </div>
            <div class="mt-4">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Rata-rata Transaksi</p>
                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">Rp {{ number_format($this->averageTransaction, 0, ',', '.') }}</p>
            </div>
        </div>

        {{-- PPN --}}
        <div class="card-hover p-5">
            <div class="stat-icon bg-amber-100 text-amber-600 dark:bg-amber-500/20 dark:text-amber-400">
                <x-heroicon-o-receipt-percent class="h-6 w-6" />
            </div>
            <div class="mt-4">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total PPN</p>
                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">Rp {{ number_format($this->totalTax, 0, ',', '.') }}</p>
            </div>
        </div>
    </div>

    {{-- Low Stock Alert --}}
    @if($this->lowStockIngredients->count() > 0)
        <div class="alert-danger mb-6">
            <div class="flex items-start gap-4">
                <div class="stat-icon h-10 w-10 shrink-0 bg-red-100 text-red-600 dark:bg-red-500/20 dark:text-red-400">
                    <x-heroicon-o-exclamation-triangle class="h-5 w-5" />
                </div>
                <div class="flex-1">
                    <h3 class="text-sm font-semibold text-red-800 dark:text-red-300">Peringatan Stok Rendah</h3>
                    <p class="mt-1 text-xs text-red-700 dark:text-red-400">Bahan berikut sudah mencapai stok minimum:</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach($this->lowStockIngredients as $ingredient)
                            <span class="badge-danger">{{ $ingredient->name }}: {{ (int) $ingredient->stock }} {{ $ingredient->unit }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Two Column Layout --}}
    <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Top Products --}}
        <div class="card overflow-hidden">
            <div class="section-header">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">🏆 Produk Terlaris</h3>
            </div>
            <div class="p-5">
                @if($this->topProducts->count() > 0)
                    <div class="space-y-3">
                        @foreach($this->topProducts as $index => $product)
                            <div class="flex items-center justify-between rounded-xl border border-gray-100 bg-gray-50/50 p-4 transition-colors hover:bg-gray-100/50 dark:border-gray-700 dark:bg-gray-800/30 dark:hover:bg-gray-800/50">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-linear-to-br from-red-500 to-orange-500 text-xs font-bold text-white shadow-sm">
                                        {{ $index + 1 }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $product->product_name }}</p>
                                        <p class="text-xs text-gray-500">{{ number_format((float) $product->total_qty, 0) }} terjual</p>
                                    </div>
                                </div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">Rp {{ number_format((float) $product->total_sales, 0, ',', '.') }}</p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <x-heroicon-o-cube class="h-7 w-7" />
                        </div>
                        <h3 class="mt-3 text-sm font-semibold text-gray-900 dark:text-white">Belum ada data</h3>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Tidak ada produk terjual dalam periode ini</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Ingredient Usage --}}
        <div class="card overflow-hidden">
            <div class="section-header">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">📦 Penggunaan Bahan Baku</h3>
            </div>
            <div class="p-5">
                @if($this->ingredientUsage->count() > 0)
                    <div class="space-y-3">
                        @foreach($this->ingredientUsage as $usage)
                            <div class="flex items-center justify-between rounded-xl border border-gray-100 bg-gray-50/50 p-4 transition-colors hover:bg-gray-100/50 dark:border-gray-700 dark:bg-gray-800/30 dark:hover:bg-gray-800/50">
                                <div class="flex items-center gap-3">
                                    <div class="stat-icon h-9 w-9 bg-blue-100 text-blue-600 dark:bg-blue-500/20 dark:text-blue-400">
                                        <x-heroicon-o-cube class="h-4 w-4" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $usage['name'] }}</p>
                                        <p class="text-xs text-gray-500">Stok: {{ (int) $usage['stock'] }} {{ $usage['unit'] }}</p>
                                    </div>
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
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <x-heroicon-o-cube class="h-7 w-7" />
                        </div>
                        <h3 class="mt-3 text-sm font-semibold text-gray-900 dark:text-white">Belum ada penggunaan</h3>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Produk dengan bahan baku belum terjual</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Payment Methods --}}
    <div class="mb-6 grid grid-cols-1 gap-4 lg:grid-cols-3">
        @foreach($this->salesByPaymentMethod as $method => $data)
            @php
                $colors = match($method) {
                    'cash' => ['bg' => 'bg-green-100 dark:bg-green-500/20', 'text' => 'text-green-600 dark:text-green-400', 'icon' => 'banknotes'],
                    'qris' => ['bg' => 'bg-blue-100 dark:bg-blue-500/20', 'text' => 'text-blue-600 dark:text-blue-400', 'icon' => 'qr'],
                    default => ['bg' => 'bg-purple-100 dark:bg-purple-500/20', 'text' => 'text-purple-600 dark:text-purple-400', 'icon' => 'transfer'],
                };
            @endphp
            <div class="card-hover p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $data['label'] }}</p>
                        <p class="mt-2 text-xl font-bold text-gray-900 dark:text-white">Rp {{ number_format($data['amount'], 0, ',', '.') }}</p>
                        <p class="mt-1 text-xs text-gray-500">{{ $data['count'] }} transaksi</p>
                    </div>
                    <div class="stat-icon {{ $colors['bg'] }} {{ $colors['text'] }}">
                        @if($method === 'cash')
                            <x-heroicon-o-banknotes class="h-6 w-6" />
                        @elseif($method === 'qris')
                            <x-heroicon-o-qr-code class="h-6 w-6" />
                        @else
                            <x-heroicon-o-arrows-right-left class="h-6 w-6" />
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Transactions Table --}}
    <div class="card overflow-hidden">
        <div class="section-header">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">📋 Detail Transaksi</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="table-header">
                    <tr>
                        <th>Invoice</th>
                        <th>Tanggal</th>
                        <th>Kasir</th>
                        <th>Customer</th>
                        <th class="text-right">Subtotal</th>
                        <th class="text-right">PPN</th>
                        <th class="text-right">Total</th>
                        <th>Metode</th>
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
                                    $badges = [
                                        'cash' => 'badge-success',
                                        'qris' => 'badge-info',
                                        'transfer' => 'bg-purple-100 text-purple-700 dark:bg-purple-500/20 dark:text-purple-400',
                                    ];
                                    $labels = [
                                        'cash' => 'Tunai',
                                        'qris' => 'QRIS',
                                        'transfer' => 'Transfer',
                                    ];
                                @endphp
                                <span class="badge {{ $badges[$sale->payment_method] ?? 'badge-warning' }}">
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
