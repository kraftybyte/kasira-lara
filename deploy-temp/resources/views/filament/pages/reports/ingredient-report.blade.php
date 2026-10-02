<x-filament-panels::page>

    {{-- Header --}}
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Laporan Bahan Baku</h1>
            <div class="mt-1.5 flex items-center gap-3">
                <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-1 text-xs font-medium text-blue-700 dark:bg-blue-500/20 dark:text-blue-400">
                    {{ $this->tenant?->name ?? 'Tenant' }}
                </span>
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
                </span>
            </div>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <a href="{{ route('reports.ingredients.csv', ['tenant' => $this->tenant?->getRouteKey()]) }}" target="_blank" class="btn btn-secondary btn-md">
                <x-heroicon-o-arrow-down-tray class="h-4 w-4" />
                Export Stok
            </a>
            <a href="{{ route('reports.ingredients.usage.csv', ['tenant' => $this->tenant?->getRouteKey(), 'startDate' => $startDate, 'endDate' => $endDate]) }}" target="_blank" class="btn btn-secondary btn-md">
                <x-heroicon-o-arrow-down-tray class="h-4 w-4" />
                Export Penggunaan
            </a>
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

        <div class="flex-1 min-w-[200px]">
            <x-filament::input.wrapper>
                <x-filament::input type="search" wire:model.live.debounce.300ms="searchIngredient" placeholder="Cari bahan..." />
            </x-filament::input.wrapper>
        </div>
    </div>

    {{-- Stats Grid --}}
    <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-2 lg:grid-cols-4">
        {{-- Total Ingredients --}}
        <div class="card-hover rounded-2xl bg-white p-4 shadow-sm dark:bg-gray-800">
            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-600 dark:bg-blue-500/20 dark:text-blue-400">
                    <x-heroicon-o-cube class="h-5 w-5" />
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Bahan</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">{{ number_format($this->ingredients->count(), 0, ',', '.') }}</p>
                </div>
            </div>
        </div>

        {{-- Stock Value --}}
        <div class="card-hover rounded-2xl bg-white p-4 shadow-sm dark:bg-gray-800">
            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-green-100 text-green-600 dark:bg-green-500/20 dark:text-green-400">
                    <x-heroicon-o-currency-dollar class="h-5 w-5" />
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Nilai Stok</p>
                    <p class="text-sm font-bold text-green-600 dark:text-green-400">Rp {{ number_format($this->totalIngredientStockValue, 0, ',', '.') }}</p>
                </div>
            </div>
        </div>

        {{-- Low Stock --}}
        <div class="card-hover rounded-2xl bg-white p-4 shadow-sm dark:bg-gray-800">
            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-600 dark:bg-amber-500/20 dark:text-amber-400">
                    <x-heroicon-o-exclamation-triangle class="h-5 w-5" />
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Stok Rendah</p>
                    <p class="text-xl font-bold text-amber-600 dark:text-amber-400">{{ number_format($this->totalLowStockCount, 0, ',', '.') }}</p>
                </div>
            </div>
        </div>

        {{-- Out of Stock --}}
        <div class="card-hover rounded-2xl bg-white p-4 shadow-sm dark:bg-gray-800">
            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-red-100 text-red-600 dark:bg-red-500/20 dark:text-red-400">
                    <x-heroicon-o-x-circle class="h-5 w-5" />
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Stok Habis</p>
                    <p class="text-xl font-bold text-red-600 dark:text-red-400">{{ number_format($this->totalOutOfStockCount, 0, ',', '.') }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Alerts --}}
    @if($this->outOfStockIngredients->count() > 0)
        <div class="mb-6 overflow-hidden rounded-2xl border border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-500/10">
            <div class="flex items-start gap-3 p-4">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-red-100 text-red-600 dark:bg-red-500/30 dark:text-red-400">
                    <x-heroicon-o-exclamation-triangle class="h-5 w-5" />
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="text-sm font-semibold text-red-800 dark:text-red-300">Bahan Baku Habis</h3>
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">Segera restok bahan berikut:</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach($this->outOfStockIngredients as $ingredient)
                            <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-700 dark:bg-red-500/30 dark:text-red-300">
                                {{ $ingredient->name }}
                            </span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($this->lowStockIngredients->count() > 0)
        <div class="mb-6 overflow-hidden rounded-2xl border border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-500/10">
            <div class="flex items-start gap-3 p-4">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-600 dark:bg-amber-500/30 dark:text-amber-400">
                    <x-heroicon-o-exclamation-triangle class="h-5 w-5" />
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="text-sm font-semibold text-amber-800 dark:text-amber-300">Stok Rendah</h3>
                    <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">Bahan berikut sudah mencapai stok minimum:</p>
                    <div class="mt-3 space-y-2">
                        @foreach($this->lowStockIngredients as $ingredient)
                            <div class="flex items-center justify-between rounded-xl bg-white/80 px-4 py-2.5 dark:bg-gray-800/60">
                                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $ingredient->name }}</span>
                                <div class="flex items-center gap-3">
                                    <span class="text-xs font-medium text-amber-600 dark:text-amber-400">
                                        {{ (int) $ingredient->stock }} / {{ (int) $ingredient->minimum_stock }} {{ $ingredient->unit }}
                                    </span>
                                    <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-1 text-[10px] font-bold text-amber-700 dark:bg-amber-500/30 dark:text-amber-300">
                                        {{ round((float) $ingredient->stock / (float) $ingredient->minimum_stock * 100) }}%
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Two Column Layout --}}
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        {{-- Ingredient Usage --}}
        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b border-gray-100 px-4 py-3 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Penggunaan Bahan</h3>
                    <span class="text-xs font-medium text-red-600 dark:text-red-400">Rp {{ number_format($this->totalIngredientUsageValue, 0, ',', '.') }}</span>
                </div>
            </div>
            <div class="p-4">
                @if(count($this->ingredientUsage) > 0)
                    <div class="space-y-2">
                        @foreach($this->ingredientUsage as $usage)
                            <div class="flex items-center justify-between rounded-xl border border-gray-100 bg-gray-50/50 p-3 transition-colors hover:bg-gray-100/50 dark:border-gray-700 dark:bg-gray-700/30 dark:hover:bg-gray-700/50">
                                <div class="flex items-center gap-3 min-w-0 flex-1">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-red-100 text-red-600 dark:bg-red-500/20 dark:text-red-400">
                                        <x-heroicon-o-cube class="h-4 w-4" />
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-medium text-gray-900 dark:text-white">{{ $usage['name'] }}</p>
                                        <p class="truncate text-xs text-gray-500 dark:text-gray-400">
                                            {{ (int) $usage['stock'] }} {{ $usage['unit'] }}
                                            @if(!empty($usage['supplier']))
                                                <span class="text-blue-600 dark:text-blue-400">• {{ $usage['supplier'] }}</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                <div class="ml-3 text-right shrink-0">
                                    <p class="text-sm font-bold text-red-600 dark:text-red-400">
                                        -{{ number_format($usage['used'], 0) }} {{ $usage['unit'] }}
                                    </p>
                                    <p class="text-[10px] text-gray-500">
                                        Sisa: {{ number_format($usage['remaining'], 0) }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center py-8 text-center">
                        <x-heroicon-o-clipboard-document-list class="h-10 w-10 text-gray-300" />
                        <h3 class="mt-3 text-sm font-semibold text-gray-900 dark:text-white">Belum ada penjualan</h3>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Tidak ada penggunaan bahan dalam periode ini</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- All Ingredients --}}
        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b border-gray-100 px-4 py-3 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Stok Semua Bahan</h3>
            </div>
            <div class="max-h-96 overflow-y-auto p-4">
                @if($this->ingredients->count() > 0)
                    <div class="space-y-2">
                        @foreach($this->ingredients as $ingredient)
                            @php
                                $statusClass = 'bg-green-100 text-green-700 dark:bg-green-500/20 dark:text-green-400';
                                $statusLabel = 'Normal';
                                if ((float) $ingredient->stock <= 0) {
                                    $statusClass = 'bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-400';
                                    $statusLabel = 'Habis';
                                } elseif ($ingredient->minimum_stock && (float) $ingredient->stock <= (float) $ingredient->minimum_stock) {
                                    $statusClass = 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-400';
                                    $statusLabel = 'Rendah';
                                }
                            @endphp
                            <div class="flex items-center justify-between rounded-xl border border-gray-100 bg-gray-50/50 p-3 transition-colors hover:bg-gray-100/50 dark:border-gray-700 dark:bg-gray-700/30 dark:hover:bg-gray-700/50">
                                <div class="flex items-center gap-3 min-w-0 flex-1">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400">
                                        <x-heroicon-o-cube class="h-4 w-4" />
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-medium text-gray-900 dark:text-white">{{ $ingredient->name }}</p>
                                        <p class="truncate text-xs text-gray-500 dark:text-gray-400">
                                            {{ $ingredient->sku ?? 'Tanpa SKU' }} • Rp {{ number_format((float) $ingredient->cost_price, 0, ',', '.') }}/{{ $ingredient->unit }}
                                            @if($ingredient->supplier)
                                                <span class="text-blue-600 dark:text-blue-400">• {{ $ingredient->supplier->name }}</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                <div class="ml-3 flex items-center gap-2 shrink-0">
                                    <div class="text-right">
                                        <p class="text-sm font-bold text-gray-900 dark:text-white">
                                            {{ (int) $ingredient->stock }} {{ $ingredient->unit }}
                                        </p>
                                    </div>
                                    <span class="inline-flex items-center rounded-full px-2 py-1 text-[10px] font-semibold {{ $statusClass }}">
                                        {{ $statusLabel }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center py-8 text-center">
                        <x-heroicon-o-clipboard-document-list class="h-10 w-10 text-gray-300" />
                        <h3 class="mt-3 text-sm font-semibold text-gray-900 dark:text-white">Belum ada bahan baku</h3>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Tambahkan bahan baku terlebih dahulu</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

</x-filament-panels::page>
