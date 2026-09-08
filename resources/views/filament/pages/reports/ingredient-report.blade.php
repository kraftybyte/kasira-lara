<x-filament-panels::page>

    {{-- Header --}}
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Laporan Bahan Baku</h1>
            <div class="mt-1.5 flex items-center gap-3">
                <span class="badge-info">
                    {{ $this->tenant?->name ?? 'Tenant' }}
                </span>
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
                </span>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" wire:click="exportToCsv" wire:loading.attr="disabled" class="btn btn-secondary btn-md">
                <x-heroicon-s-arrow-down-tray wire:target="exportToCsv" wire:loading class="h-4 w-4 animate-spin" />
                <x-heroicon-o-arrow-down-tray wire:target="exportToCsv" wire:loading.remove class="h-4 w-4" />
                <span wire:loading wire:target="exportToCsv">Mengekspor...</span>
                <span wire:loading.remove wire:target="exportToCsv">Export Stok</span>
            </button>
            <button type="button" wire:click="exportUsageToCsv" wire:loading.attr="disabled" class="btn btn-secondary btn-md">
                <x-heroicon-s-arrow-down-tray wire:target="exportUsageToCsv" wire:loading class="h-4 w-4 animate-spin" />
                <x-heroicon-o-arrow-down-tray wire:target="exportUsageToCsv" wire:loading.remove class="h-4 w-4" />
                <span wire:loading wire:target="exportUsageToCsv">Mengekspor...</span>
                <span wire:loading.remove wire:target="exportUsageToCsv">Export Penggunaan</span>
            </button>
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

        <div class="flex-1 max-w-xs">
            <x-filament::input.wrapper>
                <x-filament::input type="search" wire:model.live.debounce.300ms="searchIngredient" placeholder="Cari bahan..." />
            </x-filament::input.wrapper>
        </div>
    </div>

    {{-- Stats Grid --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {{-- Total Ingredients --}}
        <div class="card-hover p-5">
            <div class="stat-icon bg-blue-100 text-blue-600 dark:bg-blue-500/20 dark:text-blue-400">
                <x-heroicon-o-cube class="h-6 w-6" />
            </div>
            <div class="mt-4">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Bahan</p>
                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($this->ingredients->count(), 0, ',', '.') }}</p>
            </div>
        </div>

        {{-- Stock Value --}}
        <div class="card-hover p-5">
            <div class="stat-icon bg-green-100 text-green-600 dark:bg-green-500/20 dark:text-green-400">
                <x-heroicon-o-currency-dollar class="h-6 w-6" />
            </div>
            <div class="mt-4">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Nilai Stok</p>
                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">Rp {{ number_format($this->totalIngredientStockValue, 0, ',', '.') }}</p>
            </div>
        </div>

        {{-- Low Stock --}}
        <div class="card-hover p-5">
            <div class="stat-icon bg-amber-100 text-amber-600 dark:bg-amber-500/20 dark:text-amber-400">
                <x-heroicon-o-exclamation-triangle class="h-6 w-6" />
            </div>
            <div class="mt-4">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Stok Rendah</p>
                <p class="mt-1 text-2xl font-bold text-amber-600 dark:text-amber-400">{{ number_format($this->totalLowStockCount, 0, ',', '.') }}</p>
            </div>
        </div>

        {{-- Out of Stock --}}
        <div class="card-hover p-5">
            <div class="stat-icon bg-red-100 text-red-600 dark:bg-red-500/20 dark:text-red-400">
                <x-heroicon-o-x-circle class="h-6 w-6" />
            </div>
            <div class="mt-4">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Stok Habis</p>
                <p class="mt-1 text-2xl font-bold text-red-600 dark:text-red-400">{{ number_format($this->totalOutOfStockCount, 0, ',', '.') }}</p>
            </div>
        </div>
    </div>

    {{-- Alerts --}}
    @if($this->outOfStockIngredients->count() > 0)
        <div class="alert-danger mb-6">
            <div class="flex items-start gap-4">
                <div class="stat-icon h-10 w-10 shrink-0 bg-red-100 text-red-600 dark:bg-red-500/20 dark:text-red-400">
                    <x-heroicon-o-exclamation-triangle class="h-5 w-5" />
                </div>
                <div class="flex-1">
                    <h3 class="text-sm font-semibold text-red-800 dark:text-red-300">Bahan Baku Habis</h3>
                    <p class="mt-1 text-xs text-red-700 dark:text-red-400">Segera restok bahan berikut:</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach($this->outOfStockIngredients as $ingredient)
                            <span class="badge-danger">{{ $ingredient->name }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($this->lowStockIngredients->count() > 0)
        <div class="alert-warning mb-6">
            <div class="flex items-start gap-4">
                <div class="stat-icon h-10 w-10 shrink-0 bg-amber-100 text-amber-600 dark:bg-amber-500/20 dark:text-amber-400">
                    <x-heroicon-o-exclamation-triangle class="h-5 w-5" />
                </div>
                <div class="flex-1">
                    <h3 class="text-sm font-semibold text-amber-800 dark:text-amber-300">Stok Rendah</h3>
                    <p class="mt-1 text-xs text-amber-700 dark:text-amber-400">Bahan berikut sudah mencapai stok minimum:</p>
                    <div class="mt-3 space-y-2">
                        @foreach($this->lowStockIngredients as $ingredient)
                            <div class="flex items-center justify-between rounded-xl bg-white/60 px-4 py-2.5 dark:bg-gray-800/60">
                                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $ingredient->name }}</span>
                                <div class="flex items-center gap-3">
                                    <span class="text-xs text-amber-600 dark:text-amber-400">
                                        {{ (int) $ingredient->stock }} / {{ (int) $ingredient->minimum_stock }} {{ $ingredient->unit }}
                                    </span>
                                    <span class="badge-warning text-[10px] font-bold">
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
    <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Ingredient Usage --}}
        <div class="card overflow-hidden">
            <div class="section-header">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">📊 Penggunaan Bahan</h3>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Total: <span class="font-semibold text-red-600 dark:text-red-400">Rp {{ number_format($this->totalIngredientUsageValue, 0, ',', '.') }}</span>
                </p>
            </div>
            <div class="p-5">
                @if(count($this->ingredientUsage) > 0)
                    <div class="space-y-3">
                        @foreach($this->ingredientUsage as $usage)
                            <div class="flex items-center justify-between rounded-xl border border-gray-100 bg-gray-50/50 p-4 transition-colors hover:bg-gray-100/50 dark:border-gray-700 dark:bg-gray-800/30 dark:hover:bg-gray-800/50">
                                <div class="flex items-center gap-3">
                                    <div class="stat-icon h-10 w-10 bg-red-100 text-red-600 dark:bg-red-500/20 dark:text-red-400">
                                        <x-heroicon-o-cube class="h-5 w-5" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $usage['name'] }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            Stok: {{ (int) $usage['stock'] }} {{ $usage['unit'] }}
                                            @if(!empty($usage['supplier']))
                                                • <span class="text-blue-600 dark:text-blue-400">{{ $usage['supplier'] }}</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right">
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
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <x-heroicon-o-clipboard-document-list class="h-7 w-7" />
                        </div>
                        <h3 class="mt-3 text-sm font-semibold text-gray-900 dark:text-white">Belum ada penjualan</h3>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Tidak ada penggunaan bahan dalam periode ini</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- All Ingredients --}}
        <div class="card overflow-hidden">
            <div class="section-header">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">📦 Stok Semua Bahan</h3>
            </div>
            <div class="max-h-125 overflow-y-auto p-5">
                @if($this->ingredients->count() > 0)
                    <div class="space-y-2">
                        @foreach($this->ingredients as $ingredient)
                            @php
                                $statusClass = 'badge-success';
                                $statusLabel = 'Normal';
                                if ((float) $ingredient->stock <= 0) {
                                    $statusClass = 'badge-danger';
                                    $statusLabel = 'Habis';
                                } elseif ($ingredient->minimum_stock && (float) $ingredient->stock <= (float) $ingredient->minimum_stock) {
                                    $statusClass = 'badge-warning';
                                    $statusLabel = 'Rendah';
                                }
                            @endphp
                            <div class="flex items-center justify-between rounded-xl border border-gray-100 bg-gray-50/50 p-4 transition-colors hover:bg-gray-100/50 dark:border-gray-700 dark:bg-gray-800/30 dark:hover:bg-gray-800/50">
                                <div class="flex items-center gap-3">
                                    <div class="stat-icon h-10 w-10 bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400">
                                        <x-heroicon-o-cube class="h-5 w-5" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $ingredient->name }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $ingredient->sku ?? 'Tanpa SKU' }} • Rp {{ number_format((float) $ingredient->cost_price, 0, ',', '.') }}/{{ $ingredient->unit }}
                                            @if($ingredient->supplier)
                                                • <span class="text-blue-600 dark:text-blue-400">{{ $ingredient->supplier->name }}</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-bold text-gray-900 dark:text-white">
                                        {{ (int) $ingredient->stock }} {{ $ingredient->unit }}
                                    </p>
                                    <span class="{{ $statusClass }}">{{ $statusLabel }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <x-heroicon-o-clipboard-document-list class="h-7 w-7" />
                        </div>
                        <h3 class="mt-3 text-sm font-semibold text-gray-900 dark:text-white">Belum ada bahan baku</h3>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Tambahkan bahan baku terlebih dahulu</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

</x-filament-panels::page>
