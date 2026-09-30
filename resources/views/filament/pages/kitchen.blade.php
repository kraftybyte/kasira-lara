<x-filament-panels::page>
    {{-- Auto-refresh using JavaScript every 5 seconds --}}
    @once
    @push('scripts')
    <script>
        document.addEventListener('livewire:init', function() {
            setInterval(function() {
                @this.$refresh();
            }, 5000);
        });
    </script>
    @endpush
    @endonce

    {{-- Header --}}
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Kitchen Display</h1>
            <p class="mt-1 text-sm text-gray-500">{{ $this->getTenant()?->name }}</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ now('Asia/Jakarta')->format('H:i') }} WIB</span>
            <button wire:click="$refresh" class="btn btn-secondary btn-md" aria-label="Refresh halaman">
                <x-heroicon-o-arrow-path class="h-4 w-4" />
                Refresh
            </button>
        </div>
    </div>

    {{-- Quick Stats --}}
    <div class="mb-8 grid grid-cols-2 gap-4">
        <div class="card-hover flex items-center gap-4 rounded-2xl bg-white p-5 shadow-sm dark:bg-gray-800">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-red-500 text-white">
                <x-heroicon-o-fire class="h-6 w-6" />
            </div>
            <div>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $this->getPendingOrders()->count() }}</p>
                <p class="text-sm text-gray-500">Sedang Dimasak</p>
            </div>
        </div>

        <div class="card-hover flex items-center gap-4 rounded-2xl bg-white p-5 shadow-sm dark:bg-gray-800">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-emerald-500 text-white">
                <x-heroicon-o-check-circle class="h-6 w-6" />
            </div>
            <div>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $this->getReadyOrders()->count() }}</p>
                <p class="text-sm text-gray-500">Siap Disajikan</p>
            </div>
        </div>
    </div>

    {{-- Orders in Progress --}}
    <div class="mb-12">
        <div class="mb-6 flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-red-500 text-white">
                <x-heroicon-o-fire class="h-5 w-5" />
            </div>
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Sedang Dimasak</h2>
            @if($this->getPendingOrders()->isNotEmpty())
                <span class="rounded-full bg-red-100 px-3 py-1 text-sm font-bold text-red-600 dark:bg-red-500/20 dark:text-red-400">
                    {{ $this->getPendingOrders()->count() }}
                </span>
            @endif
        </div>

        @if($this->getPendingOrders()->isEmpty())
            <div class="rounded-2xl border border-gray-200 bg-gray-50 p-12 text-center dark:border-gray-700 dark:bg-gray-800/50">
                <x-heroicon-o-face-smile class="mx-auto h-12 w-12 text-gray-300" />
                <p class="mt-4 text-sm font-medium text-gray-400">Tidak ada pesanan aktif</p>
            </div>
        @else
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5">
                @foreach($this->getPendingOrders() as $order)
                    <div class="card-hover overflow-hidden rounded-2xl bg-white shadow-sm dark:bg-gray-800">
                        {{-- Header --}}
                        <div class="bg-gradient-to-br from-red-500 to-orange-500 p-3 text-white">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2 min-w-0 flex-1">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white/20">
                                        <x-heroicon-o-archive-box class="h-4 w-4" />
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-xs font-bold text-red-100" title="{{ $order->invoice_number }}">{{ $order->invoice_number }}</p>
                                        <p class="truncate text-[11px] text-red-200">{{ $order->table?->name ?? 'Tanpa Meja' }}</p>
                                    </div>
                                </div>
                                <div class="text-right shrink-0 ml-2">
                                    <p class="text-lg font-bold leading-none">{{ $order->created_at->setTimezone('Asia/Jakarta')->format('H:i') }}</p>
                                </div>
                            </div>
                        </div>

                        {{-- Items --}}
                        <div class="max-h-48 overflow-y-auto p-3 space-y-2">
                            @foreach($order->items as $item)
                                <div class="flex items-start gap-2 rounded-lg bg-gray-50 p-2 dark:bg-gray-700/50">
                                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-red-100 text-xs font-bold text-red-600 dark:bg-red-500/30 dark:text-red-300">
                                        {{ (int) $item->quantity }}
                                    </span>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white line-clamp-2">{{ $item->product_name }}</p>
                                        {{-- Modifiers --}}
                                        @if($item->modifiers && $item->modifiers->count() > 0)
                                            <div class="mt-1 flex flex-wrap items-center gap-1">
                                                @foreach($item->modifiers as $modifier)
                                                    <span class="inline-flex items-center rounded-full bg-blue-100 px-1.5 py-0.5 text-[10px] font-medium text-blue-700 dark:bg-blue-500/30 dark:text-blue-300">
                                                        + {{ $modifier->modifier_name ?? $modifier->name }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif
                                        {{-- Notes --}}
                                        @if($item->notes)
                                            <span class="mt-1 flex items-center gap-1 text-xs text-amber-600 dark:text-amber-400">
                                                <x-heroicon-o-pencil-square class="h-3 w-3 shrink-0" />
                                                <span class="truncate">{{ $item->notes }}</span>
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                            @if($order->notes)
                                <div class="rounded-lg border border-amber-200 bg-amber-50 p-2 dark:border-amber-700/50 dark:bg-amber-500/10">
                                    <p class="flex items-center gap-1 text-xs font-semibold text-amber-700 dark:text-amber-400">
                                        <x-heroicon-o-pencil-square class="h-3 w-3 shrink-0" />
                                        Catatan:
                                    </p>
                                    <p class="mt-0.5 text-xs text-amber-800 dark:text-amber-300 line-clamp-2">
                                        {{ $order->notes }}
                                    </p>
                                </div>
                            @endif
                        </div>

                        {{-- Action --}}
                        <div class="border-t border-gray-100 p-3 dark:border-gray-700">
                            <button wire:click="markAsServed({{ $order->id }})"
                                class="btn btn-success btn-md w-full"
                                aria-label="Tandai pesanan {{ $order->invoice_number }} siap disajikan">
                                <x-heroicon-o-check class="h-4 w-4" />
                                Siap Disajikan
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Ready to Serve --}}
    <div>
        <div class="mb-6 flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-500 text-white">
                <x-heroicon-o-check-circle class="h-5 w-5" />
            </div>
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Siap Disajikan</h2>
            @if($this->getReadyOrders()->isNotEmpty())
                <span class="rounded-full bg-emerald-100 px-3 py-1 text-sm font-bold text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-400">
                    {{ $this->getReadyOrders()->count() }}
                </span>
            @endif
        </div>

        @if($this->getReadyOrders()->isEmpty())
            <div class="rounded-2xl border border-gray-200 bg-gray-50 p-12 text-center dark:border-gray-700 dark:bg-gray-800/50">
                <p class="text-sm text-gray-400">Tidak ada pesanan siap disajikan</p>
            </div>
        @else
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5">
                @foreach($this->getReadyOrders() as $order)
                    <div class="card-hover overflow-hidden rounded-2xl bg-white shadow-sm dark:bg-gray-800">
                        {{-- Header --}}
                        <div class="bg-gradient-to-br from-emerald-500 to-teal-500 p-3 text-white">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2 min-w-0 flex-1">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white/20">
                                        <x-heroicon-o-archive-box class="h-4 w-4" />
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-xs font-bold text-emerald-100" title="{{ $order->invoice_number }}">{{ $order->invoice_number }}</p>
                                        <p class="truncate text-[11px] text-emerald-200">{{ $order->table?->name ?? 'Tanpa Meja' }}</p>
                                    </div>
                                </div>
                                <div class="text-right shrink-0 ml-2">
                                    <p class="text-lg font-bold leading-none">{{ $order->created_at->setTimezone('Asia/Jakarta')->format('H:i') }}</p>
                                </div>
                            </div>
                        </div>

                        {{-- Items --}}
                        <div class="max-h-48 overflow-y-auto p-3 space-y-2">
                            @foreach($order->items as $item)
                                <div class="flex items-start gap-2 rounded-lg bg-emerald-50 p-2 dark:bg-emerald-500/10">
                                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-200 text-xs font-bold text-emerald-700 dark:bg-emerald-500/30 dark:text-emerald-300">
                                        {{ (int) $item->quantity }}
                                    </span>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white line-clamp-2">{{ $item->product_name }}</p>
                                        @if($item->modifiers && $item->modifiers->count() > 0)
                                            <div class="mt-1 flex flex-wrap items-center gap-1">
                                                @foreach($item->modifiers as $modifier)
                                                    <span class="inline-flex items-center rounded-full bg-blue-100 px-1.5 py-0.5 text-[10px] font-medium text-blue-700 dark:bg-blue-500/30 dark:text-blue-300">
                                                        + {{ $modifier->modifier_name ?? $modifier->name }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif
                                        @if($item->notes)
                                            <span class="mt-1 flex items-center gap-1 text-xs text-amber-600 dark:text-amber-400">
                                                <x-heroicon-o-pencil-square class="h-3 w-3 shrink-0" />
                                                <span class="truncate">{{ $item->notes }}</span>
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                            @if($order->notes)
                                <div class="rounded-lg border border-amber-200 bg-amber-50 p-2 dark:border-amber-700/50 dark:bg-amber-500/10">
                                    <p class="flex items-center gap-1 text-xs font-semibold text-amber-700 dark:text-amber-400">
                                        <x-heroicon-o-pencil-square class="h-3 w-3 shrink-0" />
                                        Catatan:
                                    </p>
                                    <p class="mt-0.5 text-xs text-amber-800 dark:text-amber-300 line-clamp-2">
                                        {{ $order->notes }}
                                    </p>
                                </div>
                            @endif
                        </div>

                        {{-- Action --}}
                        <div class="border-t border-gray-100 p-3 dark:border-gray-700">
                            <div class="flex gap-2">
                                <button wire:click="markAsUnserved({{ $order->id }})"
                                    class="flex-1 btn btn-secondary btn-md"
                                    aria-label="Batalkan pesanan {{ $order->invoice_number }}">
                                    <x-heroicon-o-arrow-uturn-left class="h-4 w-4" />
                                    Batal
                                </button>
                                <button wire:click="markAsCompleted({{ $order->id }})"
                                    class="flex-1 btn btn-success btn-md"
                                    aria-label="Selesaikan pesanan {{ $order->invoice_number }}">
                                    <x-heroicon-o-check class="h-4 w-4" />
                                    Selesai
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-filament-panels::page>
