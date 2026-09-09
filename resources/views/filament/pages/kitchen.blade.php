<x-filament-panels::page>
    {{-- Header --}}
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Kitchen Display</h1>
            <p class="mt-1 text-sm text-gray-500">{{ $this->getTenant()?->name }}</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::now('Asia/Jakarta')->format('H:i') }} WIB</span>
            <button wire:click="$refresh" class="btn btn-secondary btn-md">
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
            <div class="rounded-2xl border-2 border-dashed border-gray-200 bg-gray-50 p-16 text-center dark:border-gray-700 dark:bg-gray-800/50">
                <x-heroicon-o-face-smile class="mx-auto h-16 w-16 text-gray-300" />
                <p class="mt-4 text-lg font-medium text-gray-400">Tidak ada pesanan aktif</p>
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
                                <div class="mb-2 flex items-center gap-2 rounded-lg bg-gray-50 p-2 dark:bg-gray-800">
                                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-red-100 text-xs font-bold text-red-600 dark:bg-red-500/20 dark:text-red-400">
                                        {{ (int) $item->quantity }}
                                    </span>
                                    <span class="flex-1 text-sm font-medium text-gray-900 dark:text-white">{{ $item->product_name }}</span>
                                </div>
                            @endforeach
                        </div>

                        {{-- Action --}}
                        <div class="border-t border-gray-100 p-3">
                            @if($order->status === 'pending')
                                <button wire:click="markAsPreparing({{ $order->id }})"
                                    class="btn btn-warning btn-md w-full">
                                    <x-heroicon-o-play class="h-4 w-4" />
                                    Mulai Masak
                                </button>
                            @else
                                <button wire:click="markAsReady({{ $order->id }})"
                                    class="btn btn-success btn-md w-full">
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
                                <div class="mb-2 flex items-center gap-2 rounded-lg bg-emerald-50 p-2 dark:bg-emerald-500/10">
                                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-200 text-xs font-bold text-emerald-700 dark:bg-emerald-500/30 dark:text-emerald-300">
                                        {{ (int) $item->quantity }}
                                    </span>
                                    <span class="flex-1 text-sm font-medium text-gray-900 dark:text-white">{{ $item->product_name }}</span>
                                </div>
                            @endforeach
                        </div>

                        {{-- Action --}}
                        <div class="border-t border-gray-100 p-3">
                            @if($order->status !== 'completed')
                                <a href="/admin/{{ $order->tenant_id ?? 1 }}/pos?table={{ $order->table_id }}"
                                    class="btn btn-primary btn-md mb-2 flex w-full items-center justify-center gap-2">
                                    <x-heroicon-o-credit-card class="h-4 w-4" />
                                    Bayar Sekarang
                                </a>
                            @endif
                            <button wire:click="markAsCompleted({{ $order->id }})"
                                class="btn btn-secondary btn-md w-full">
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
