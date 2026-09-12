<x-filament-panels::page>
        {{-- Loading State --}}
        <div wire:loading class="fixed inset-0 z-50 flex items-center justify-center bg-black/20">
            <div class="flex items-center gap-2 rounded-xl bg-white px-6 py-4 shadow-lg dark:bg-gray-800">
                <svg class="h-6 w-6 animate-spin text-red-500" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Memuat...</span>
            </div>
        </div>

    {{-- Header --}}
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Kitchen Display</h1>
            <p class="mt-1 text-sm text-gray-500">{{ $this->getTenant()?->name }}</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::now('Asia/Jakarta')->format('H:i') }} WIB</span>
            <button wire:click="$refresh" class="btn btn-secondary btn-md" aria-label="Refresh halaman">
                <x-heroicon-o-arrow-path class="h-4 w-4" />
                Refresh
            </button>
        </div>
    </div>

    {{-- Quick Stats --}}
    <div class="mb-8 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
        <div class="card-hover flex items-center gap-4 p-5">
            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-red-500 text-white">
                <x-heroicon-o-fire class="h-7 w-7" />
            </div>
            <div>
                <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $this->getPendingOrders()->count() }}</p>
                <p class="text-sm text-gray-500">Sedang Dimasak</p>
            </div>
        </div>

        <div class="card-hover flex items-center gap-4 p-5">
            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-500 text-white">
                <x-heroicon-o-check-circle class="h-7 w-7" />
            </div>
            <div>
                <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $this->getReadyOrders()->count() }}</p>
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
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5">
                @foreach($this->getPendingOrders() as $order)
                    <div class="card-hover overflow-hidden p-0">
                        {{-- Header --}}
                        <div class="bg-linear-to-br from-red-500 to-orange-500 p-3 text-white">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-white/20">
                                        <x-heroicon-o-archive-box class="h-4 w-4" />
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate text-xs font-bold text-red-100">{{ $order->invoice_number }}</p>
                                        <p class="text-xs text-red-200">{{ $order->table?->name ?? 'Tanpa Meja' }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-xl font-bold">{{ $order->created_at->setTimezone('Asia/Jakarta')->format('H:i') }}</p>
                                </div>
                            </div>
                        </div>

                        {{-- Items --}}
                        <div class="max-h-56 overflow-y-auto p-3">
                            @foreach($order->items as $item)
                                <div class="mb-2 flex items-start gap-2 rounded-lg bg-gray-50 p-2 dark:bg-gray-800">
                                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-red-100 text-xs font-bold text-red-600 dark:bg-red-500/20 dark:text-red-400">
                                        {{ (int) $item->quantity }}
                                    </span>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $item->product_name }}</p>
                                        @if($item->notes)
                                            <p class="mt-0.5 text-xs text-amber-600 dark:text-amber-400">
                                                <x-heroicon-o-information-circle class="inline h-3 w-3 mr-1" />
                                                {{ $item->notes }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                            {{-- Order Notes --}}
                            @if($order->notes)
                                <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 p-2 dark:border-amber-700/50 dark:bg-amber-500/10">
                                    <p class="text-xs font-semibold text-amber-700 dark:text-amber-400">
                                        <x-heroicon-o-pencil-square class="inline h-3 w-3 mr-1" />
                                        Catatan Pesanan:
                                    </p>
                                    <p class="mt-1 text-sm text-amber-800 dark:text-amber-300">
                                        {{ $order->notes }}
                                    </p>
                                </div>
                            @endif
                        </div>

                        {{-- Action --}}
                        <div class="border-t border-gray-100 p-3">
                            @if($order->status === 'pending')
                                <button wire:click="markAsPreparing({{ $order->id }})"
                                    class="btn btn-warning btn-md w-full"
                                    aria-label="Mulai masak pesanan {{ $order->invoice_number }}">
                                    <x-heroicon-o-play class="h-4 w-4" />
                                    Mulai Masak
                                </button>
                            @else
                                <button wire:click="markAsReady({{ $order->id }})"
                                    class="btn btn-success btn-md w-full"
                                    aria-label="Tandai selesai masak pesanan {{ $order->invoice_number }}">
                                    <x-heroicon-o-check class="h-4 w-4" />
                                    Selesai Masak
                                </button>
                            @endif
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
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5">
                @foreach($this->getReadyOrders() as $order)
                    <div class="card-hover overflow-hidden p-0">
                        {{-- Header --}}
                        <div class="bg-linear-to-br from-emerald-500 to-teal-500 p-3 text-white">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-white/20">
                                        <x-heroicon-o-archive-box class="h-4 w-4" />
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate text-xs font-bold text-emerald-100">{{ $order->invoice_number }}</p>
                                        <p class="text-xs text-emerald-200">{{ $order->table?->name ?? 'Tanpa Meja' }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-xl font-bold">{{ $order->created_at->setTimezone('Asia/Jakarta')->format('H:i') }}</p>
                                </div>
                            </div>
                        </div>

                        {{-- Items --}}
                        <div class="max-h-56 overflow-y-auto p-3">
                            @foreach($order->items as $item)
                                <div class="mb-2 flex items-start gap-2 rounded-lg bg-emerald-50 p-2 dark:bg-emerald-500/10">
                                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-200 text-xs font-bold text-emerald-700 dark:bg-emerald-500/30 dark:text-emerald-300">
                                        {{ (int) $item->quantity }}
                                    </span>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $item->product_name }}</p>
                                        @if($item->notes)
                                            <p class="mt-0.5 text-xs text-amber-600 dark:text-amber-400">
                                                <x-heroicon-o-information-circle class="inline h-3 w-3 mr-1" />
                                                {{ $item->notes }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                            {{-- Order Notes --}}
                            @if($order->notes)
                                <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 p-2 dark:border-amber-700/50 dark:bg-amber-500/10">
                                    <p class="text-xs font-semibold text-amber-700 dark:text-amber-400">
                                        <x-heroicon-o-pencil-square class="inline h-3 w-3 mr-1" />
                                        Catatan Pesanan:
                                    </p>
                                    <p class="mt-1 text-sm text-amber-800 dark:text-amber-300">
                                        {{ $order->notes }}
                                    </p>
                                </div>
                            @endif
                        </div>

                        {{-- Action --}}
                        <div class="border-t border-gray-100 p-3">
                            <button wire:click="markAsCompleted({{ $order->id }})"
                                class="btn btn-secondary btn-md w-full"
                                aria-label="Tandai pesanan {{ $order->invoice_number }} sudah selesai">
                                <x-heroicon-o-hand-thumb-up class="h-4 w-4" />
                                Selesai
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-filament-panels::page>
