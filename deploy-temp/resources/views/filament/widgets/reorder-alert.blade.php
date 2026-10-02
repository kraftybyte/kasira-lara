<div>
    @if($getRecords())
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-700 dark:bg-amber-950/20">
            <div class="flex items-center gap-2 mb-3">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400">
                    <x-heroicon-o-exclamation-triangle class="h-4 w-4" />
                </div>
                <h3 class="text-sm font-semibold text-amber-900 dark:text-amber-300">
                    Reorder Alerts
                </h3>
            </div>

            <div class="space-y-2">
                @foreach($getRecords() as $record)
                    <div class="flex items-center justify-between rounded-lg border border-amber-200 bg-white p-3 dark:border-amber-700 dark:bg-amber-950/40">
                        <div>
                            <p class="text-sm font-semibold text-amber-900 dark:text-amber-300">{{ $record['name'] }}</p>
                            <p class="text-xs text-amber-700 dark:text-amber-400">
                                Stok: {{ number_format($record['stock'], 1) }} {{ $record['unit'] }} / Min: {{ number_format($record['minimum'], 1) }} {{ $record['unit'] }}
                            </p>
                        </div>
                        @if($record['supplier_phone'])
                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $record['supplier_phone']) }}?text={{ urlencode("Halo, kami ingin reorder {$record['name']} (saat ini stok: {$record['stock']} {$record['unit']})"
                               target="_blank"
                               class="shrink-0 inline-flex items-center gap-1 rounded-lg bg-green-500 px-3 py-1.5 text-xs font-semibold text-white hover:bg-green-600">
                                <x-heroicon-o-bolt-slash class="h-3 w-3" />
                                Order
                            </a>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
