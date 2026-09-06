<x-filament-panels::page>

    {{-- Header with Greeting --}}
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">Halo, {{ $this->getUserName() }}!</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Berikut ringkasan penjualan toko kamu</p>
        </div>
        <div class="text-right">
            {{-- Date Range Buttons --}}
            <div class="flex items-center gap-1 rounded-lg bg-gray-100 p-1 dark:bg-gray-800">
                <button
                    type="button"
                    wire:click="$set('days', 7)"
                    class="rounded-md px-3 py-1.5 text-xs font-medium transition-colors {{ $days === 7 ? 'bg-white text-gray-900 shadow-sm dark:bg-gray-700 dark:text-white' : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white' }}"
                >
                    7 Hari
                </button>
                <button
                    type="button"
                    wire:click="$set('days', 14)"
                    class="rounded-md px-3 py-1.5 text-xs font-medium transition-colors {{ $days === 14 ? 'bg-white text-gray-900 shadow-sm dark:bg-gray-700 dark:text-white' : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white' }}"
                >
                    14 Hari
                </button>
                <button
                    type="button"
                    wire:click="$set('days', 30)"
                    class="rounded-md px-3 py-1.5 text-xs font-medium transition-colors {{ $days === 30 ? 'bg-white text-gray-900 shadow-sm dark:bg-gray-700 dark:text-white' : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white' }}"
                >
                    30 Hari
                </button>
            </div>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                {{ now()->subDays($days)->format('d M') }} - {{ now()->format('d M Y') }}
            </p>
        </div>
    </div>

    {{-- Summary Stats --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {{-- Total Penjualan --}}
        <div class="card-hover p-5">
            <div class="flex items-center gap-4">
                <div class="stat-icon gradient-bg text-white">
                    <x-heroicon-o-currency-dollar class="h-6 w-6" />
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Penjualan</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">Rp {{ number_format($this->totalSales30Days, 0, ',', '.') }}</p>
                </div>
            </div>
        </div>

        {{-- Total Transaksi --}}
        <div class="card-hover p-5">
            <div class="flex items-center gap-4">
                <div class="stat-icon bg-blue-100 text-blue-600 dark:bg-blue-500/20 dark:text-blue-400">
                    <x-heroicon-o-shopping-bag class="h-6 w-6" />
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Transaksi</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">{{ number_format($this->totalTransactions30Days) }}</p>
                </div>
            </div>
        </div>

        {{-- Rata-rata Transaksi --}}
        <div class="card-hover p-5">
            <div class="flex items-center gap-4">
                <div class="stat-icon bg-purple-100 text-purple-600 dark:bg-purple-500/20 dark:text-purple-400">
                    <x-heroicon-o-chart-bar class="h-6 w-6" />
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Rata-rata</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">Rp {{ number_format($this->averageTransaction30Days, 0, ',', '.') }}</p>
                </div>
            </div>
        </div>

        {{-- Total Items Terjual --}}
        <div class="card-hover p-5">
            <div class="flex items-center gap-4">
                <div class="stat-icon bg-amber-100 text-amber-600 dark:bg-amber-500/20 dark:text-amber-400">
                    <x-heroicon-o-cube class="h-6 w-6" />
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Items Terjual</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">{{ number_format($this->topProducts->sum('total_qty')) }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts Grid --}}
    <div class="mb-6 grid grid-cols-1 gap-5 lg:grid-cols-2">
        {{-- Sales Line Chart Widget --}}
        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
            <div class="section-header">
                <div class="flex items-center gap-2">
                    <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-red-100 text-red-600 dark:bg-red-500/20 dark:text-red-400">
                        <x-heroicon-o-chart-bar-square class="h-4 w-4" />
                    </div>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Grafik Penjualan</h2>
                </div>
            </div>
            <div class="p-4">
                @livewire(\App\Filament\Widgets\SalesLineChart::class)
            </div>
        </div>

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
                                    <p class="text-xs text-gray-500">{{ (int) $product->total_qty }} terjual</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-bold text-red-500">Rp {{ number_format($product->total_sales, 0, ',', '.') }}</p>
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
    </div>

    {{-- Payment Methods --}}
    <div class="mb-6 grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="card-hover p-4 flex items-center gap-4">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-500 text-white">
                <x-heroicon-o-banknotes class="h-6 w-6" />
            </div>
            <div class="flex-1">
                <p class="text-xs text-gray-500">Tunai</p>
                <p class="text-xl font-bold text-gray-900">Rp {{ number_format($this->paymentSummary['cash'] ?? 0, 0, ',', '.') }}</p>
            </div>
        </div>

        <div class="card-hover p-4 flex items-center gap-4">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-500 text-white">
                <x-heroicon-o-qr-code class="h-6 w-6" />
            </div>
            <div class="flex-1">
                <p class="text-xs text-gray-500">QRIS</p>
                <p class="text-xl font-bold text-gray-900">Rp {{ number_format($this->paymentSummary['qris'] ?? 0, 0, ',', '.') }}</p>
            </div>
        </div>

        <div class="card-hover p-4 flex items-center gap-4">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-purple-500 text-white">
                <x-heroicon-o-building-office class="h-6 w-6" />
            </div>
            <div class="flex-1">
                <p class="text-xs text-gray-500">Transfer</p>
                <p class="text-xl font-bold text-gray-900">Rp {{ number_format($this->paymentSummary['transfer'] ?? 0, 0, ',', '.') }}</p>
            </div>
        </div>
    </div>
</x-filament-panels::page>
