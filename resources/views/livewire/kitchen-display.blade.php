<div
    wire:poll.5s="poll"
    x-data="kitchenAlerts()"
    x-on:kitchen-new-order.window="flash = true; beep(); setTimeout(() => flash = false, 2000)"
    :class="flash && 'ring-4 ring-inset ring-red-500'"
    class="dark min-h-screen bg-gray-950 p-6 transition-shadow"
>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-white">Dapur</h1>
            <p class="mt-1 text-sm text-gray-400">{{ $tenant->name }} &middot; {{ $pendingOrders->count() }} dimasak &middot; {{ $readyOrders->count() }} siap &middot; layar pantau</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="mr-2 whitespace-nowrap text-2xl font-bold text-white">{{ $tenant->now()->format('H:i') }} {{ $tenant->timezoneLabel() }}</span>
            <button type="button" x-show="! audio" x-cloak @click="enableSound()" class="btn btn-secondary btn-md">
                <x-heroicon-o-bell-alert class="h-4 w-4" />
                Aktifkan suara
            </button>
            <button type="button" @click="document.documentElement.requestFullscreen?.()" class="btn btn-secondary btn-md" aria-label="Layar penuh" title="Layar penuh">
                <x-heroicon-o-arrows-pointing-out class="h-4 w-4" />
            </button>
        </div>
    </div>

    @include('filament.pages.kitchen.board', ['pendingOrders' => $pendingOrders, 'readyOrders' => $readyOrders, 'sop' => $sop, 'display' => true, 'readonly' => true])
</div>
