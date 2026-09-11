<x-filament-panels::page>
    <div class="mx-auto max-w-4xl">
        @php
            $summary = $this->getSummaryData();
            $formattedDate = \Carbon\Carbon::parse($summary['date'] ?? now())->locale('id')->format('d F Y');
            $recentSales = $this->getRecentSales();
        @endphp

        {{-- Header --}}
        <div class="mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">LAPORAN KAS HARIAN</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $formattedDate }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <input
                        type="date"
                        wire:model.live="summaryDate"
                        class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm text-gray-900 outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    >
                </div>
            </div>
        </div>

        {{-- Summary Cards --}}
        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            {{-- Total Sales --}}
            <div class="rounded-xl border-2 border-blue-500 bg-blue-50 p-5 dark:border-blue-400 dark:bg-blue-500/10">
                <div class="flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-500 text-white">
                        <x-heroicon-o-chart-bar class="h-6 w-6" />
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase text-blue-600 dark:text-blue-400">Total Penjualan</p>
                        <p class="text-xl font-bold text-blue-700 dark:text-blue-300">Rp {{ number_format($summary['total_sales'] ?? 0, 0, ',', '.') }}</p>
                    </div>
                </div>
            </div>

            {{-- Total Transactions --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                <div class="flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                        <x-heroicon-o-receipt-refund class="h-6 w-6" />
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Transaksi</p>
                        <p class="text-xl font-bold text-gray-900 dark:text-white">{{ $summary['total_transactions'] ?? 0 }}</p>
                    </div>
                </div>
            </div>

            {{-- Cash in Drawer --}}
            <div class="rounded-xl border border-green-200 bg-green-50 p-5 dark:border-green-500/30 dark:bg-green-500/10">
                <div class="flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-green-500 text-white">
                        <x-heroicon-o-banknotes class="h-6 w-6" />
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase text-green-600 dark:text-green-400">Tunai</p>
                        <p class="text-xl font-bold text-green-700 dark:text-green-300">Rp {{ number_format($summary['cash_total'] ?? 0, 0, ',', '.') }}</p>
                    </div>
                </div>
            </div>

            {{-- Non-Cash --}}
            <div class="rounded-xl border border-purple-200 bg-purple-50 p-5 dark:border-purple-500/30 dark:bg-purple-500/10">
                <div class="flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-purple-500 text-white">
                        <x-heroicon-o-credit-card class="h-6 w-6" />
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase text-purple-600 dark:text-purple-400">Non-Tunai</p>
                        <p class="text-xl font-bold text-purple-700 dark:text-purple-300">Rp {{ number_format(($summary['qris_total'] ?? 0) + ($summary['va_total'] ?? 0) + ($summary['other_total'] ?? 0), 0, ',', '.') }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Payment Breakdown --}}
        <div class="mb-6 grid grid-cols-1 gap-4 lg:grid-cols-2">
            {{-- Breakdown Table --}}
            <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
                <div class="border-b border-gray-200 bg-gray-50 px-5 py-3 dark:border-gray-700 dark:bg-gray-800">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Rincian Pembayaran</h3>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                    <div class="flex items-center justify-between px-5 py-3">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-yellow-100 text-yellow-600 dark:bg-yellow-500/20 dark:text-yellow-400">
                                <x-heroicon-o-banknotes class="h-5 w-5" />
                            </div>
                            <span class="font-medium text-gray-700 dark:text-gray-300">Tunai (Cash)</span>
                        </div>
                        <span class="font-semibold text-gray-900 dark:text-white">Rp {{ number_format($summary['cash_total'] ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex items-center justify-between px-5 py-3">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-green-100 text-green-600 dark:bg-green-500/20 dark:text-green-400">
                                <x-heroicon-o-qr-code class="h-5 w-5" />
                            </div>
                            <span class="font-medium text-gray-700 dark:text-gray-300">QRIS</span>
                        </div>
                        <span class="font-semibold text-gray-900 dark:text-white">Rp {{ number_format($summary['qris_total'] ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex items-center justify-between px-5 py-3">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-purple-100 text-purple-600 dark:bg-purple-500/20 dark:text-purple-400">
                                <x-heroicon-o-building-office class="h-5 w-5" />
                            </div>
                            <span class="font-medium text-gray-700 dark:text-gray-300">Virtual Account</span>
                        </div>
                        <span class="font-semibold text-gray-900 dark:text-white">Rp {{ number_format($summary['va_total'] ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex items-center justify-between px-5 py-3">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                <x-heroicon-o-credit-card class="h-5 w-5" />
                            </div>
                            <span class="font-medium text-gray-700 dark:text-gray-300">Lainnya (Transfer)</span>
                        </div>
                        <span class="font-semibold text-gray-900 dark:text-white">Rp {{ number_format($summary['other_total'] ?? 0, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            {{-- Stats --}}
            <div class="space-y-4">
                <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                    <h3 class="mb-4 text-sm font-semibold text-gray-900 dark:text-white">Statistik</h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-500 dark:text-gray-400">Rata-rata Transaksi</span>
                            <span class="font-semibold text-gray-900 dark:text-white">Rp {{ number_format($summary['average_transaction'] ?? 0, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-500 dark:text-gray-400">Persentase Tunai</span>
                            <span class="font-semibold text-gray-900 dark:text-white">
                                @if(($summary['total_sales'] ?? 0) > 0)
                                    {{ round((($summary['cash_total'] ?? 0) / ($summary['total_sales'] ?? 1)) * 100, 1) }}%
                                @else
                                    0%
                                @endif
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-500 dark:text-gray-400">Persentase Non-Tunai</span>
                            <span class="font-semibold text-gray-900 dark:text-white">
                                @if(($summary['total_sales'] ?? 0) > 0)
                                    {{ round(((($summary['qris_total'] ?? 0) + ($summary['va_total'] ?? 0) + ($summary['other_total'] ?? 0)) / ($summary['total_sales'] ?? 1)) * 100, 1) }}%
                                @else
                                    0%
                                @endif
                            </span>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 dark:border-amber-500/30 dark:bg-amber-500/10">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-amber-500 text-white">
                            <x-heroicon-o-currency-dollar class="h-5 w-5" />
                        </div>
                        <div>
                            <p class="text-sm font-medium text-amber-700 dark:text-amber-400">Estimasi Tunai di Laci</p>
                            <p class="text-xl font-bold text-amber-700 dark:text-amber-300">Rp {{ number_format($summary['cash_in_drawer'] ?? 0, 0, ',', '.') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Recent Transactions --}}
        <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
            <div class="border-b border-gray-200 bg-gray-50 px-5 py-3 dark:border-gray-700 dark:bg-gray-800">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Transaksi Terbaru</h3>
            </div>
            @if($recentSales->isEmpty())
                <div class="px-5 py-12 text-center">
                    <x-heroicon-o-inbox class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600" />
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Tidak ada transaksi pada tanggal ini</p>
                </div>
            @else
                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($recentSales as $sale)
                        <div class="flex items-center justify-between px-5 py-3">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                    @if($sale->payment_method === 'cash')
                                        <x-heroicon-o-banknotes class="h-5 w-5 text-yellow-500" />
                                    @elseif($sale->payment_method === 'qris')
                                        <x-heroicon-o-qr-code class="h-5 w-5 text-green-500" />
                                    @elseif($sale->payment_method === 'transfer')
                                        <x-heroicon-o-arrow-right-circle class="h-5 w-5 text-blue-500" />
                                    @else
                                        <x-heroicon-o-building-office class="h-5 w-5 text-purple-500" />
                                    @endif
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ $sale->invoice_number }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $sale->created_at->format('H:i') }}
                                        @if($sale->customer)
                                            - {{ $sale->customer->name }}
                                        @endif
                                        @if($sale->table)
                                            - Meja {{ $sale->table->name }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold text-gray-900 dark:text-white">Rp {{ number_format($sale->grand_total, 0, ',', '.') }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">{{ $sale->payment_method }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
