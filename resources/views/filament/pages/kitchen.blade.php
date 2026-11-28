<x-filament-panels::page>
    {{-- Poll tiap 5 detik; server kirim kitchen-new-order kalau ada pesanan baru --}}
    @once
    @push('scripts')
    <script>
        document.addEventListener('livewire:init', function() {
            setInterval(function() {
                @this.call('poll');
            }, 5000);
        });
    </script>
    @endpush
    @endonce

    <div
        x-data="kitchenAlerts()"
        x-on:kitchen-new-order.window="flash = true; beep(); setTimeout(() => flash = false, 2000)"
        :class="flash && 'ring-4 ring-inset ring-red-500'"
        class="transition-shadow {{ $display ? 'dark min-h-screen bg-gray-950 p-6' : 'rounded-2xl' }}"
    >
    @if($display)
        {{-- Latar body ikut gelap dan padding atas/bawah kontainer halaman Filament dibuang, supaya tidak ada pita terang --}}
        <style>
            @media (min-width: 1024px) { html { font-size: 20px; } }
            body { background-color: #030712; }
            .fi-page-header-main-ctn { padding: 0; }
        </style>
    @endif
    {{-- Header --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Dapur</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ $this->getTenant()?->name }}
                @if($display)
                    &middot; {{ $this->pendingOrders->count() }} dimasak &middot; {{ $this->readyOrders->count() }} siap
                @endif
            </p>
        </div>
        <div class="flex items-center gap-2">
            <span class="whitespace-nowrap {{ $display ? 'mr-2 text-2xl font-bold text-white' : 'text-sm font-medium text-gray-500 dark:text-gray-400' }}">{{ $this->getTenant()?->now()->format('H:i') }} {{ $this->getTenant()?->timezoneLabel() }}</span>
            <button type="button" x-show="! audio" x-cloak @click="enableSound()" class="btn btn-secondary btn-md">
                <x-heroicon-o-bell-alert class="h-4 w-4" />
                Aktifkan suara
            </button>
            @if($display)
                <button type="button" @click="document.documentElement.requestFullscreen?.()" class="btn btn-secondary btn-md" aria-label="Layar penuh" title="Layar penuh">
                    <x-heroicon-o-arrows-pointing-out class="h-4 w-4" />
                </button>
                <a href="{{ route('filament.admin.pages.kitchen', ['tenant' => $this->getTenant()?->getRouteKey()]) }}" class="btn btn-secondary btn-md" aria-label="Keluar mode layar" title="Keluar mode layar">
                    <x-heroicon-o-x-mark class="h-4 w-4" />
                </a>
            @else
                <button wire:click="$refresh" class="btn btn-secondary btn-md" aria-label="Refresh halaman">
                    <x-heroicon-o-arrow-path class="h-4 w-4" />
                    Refresh
                </button>
                <a href="{{ route('filament.admin.pages.kitchen', ['tenant' => $this->getTenant()?->getRouteKey(), 'display' => 1]) }}" target="_blank" rel="noopener" class="btn btn-primary btn-md">
                    <x-heroicon-o-tv class="h-4 w-4" />
                    Layar dapur
                </a>
            @endif
        </div>
    </div>

    @if(! $display && $this->displayUrl)
        {{-- Tautan layar pantau bertoken: tanpa login, hanya tampilan --}}
        <div x-data="{ copied: false }" class="mb-6 rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                <x-heroicon-o-tv class="h-5 w-5 shrink-0 text-gray-500" />
                <span class="font-semibold text-gray-900 dark:text-white">Tautan layar pantau</span>
                <span class="text-xs text-gray-500">tanpa login, hanya tampilan, untuk TV atau monitor dapur</span>
            </div>
            <div class="mt-2 flex flex-wrap items-center gap-2">
            <input type="text" readonly value="{{ $this->displayUrl }}" @focus="$el.select()" class="min-w-0 flex-1 rounded-lg border-gray-200 bg-gray-50 px-3 py-1.5 text-xs text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
            <button type="button" @click="navigator.clipboard.writeText('{{ $this->displayUrl }}').then(() => { copied = true; setTimeout(() => copied = false, 1500) })" class="btn btn-secondary btn-sm" x-text="copied ? 'Tersalin' : 'Salin'"></button>
            <button type="button" wire:click="regenerateDisplayToken" wire:confirm="Tautan lama akan berhenti bekerja. Lanjutkan?" class="btn btn-ghost btn-sm">Buat tautan baru</button>
            </div>
        </div>
    @endif

    @unless($display)
    {{-- Quick Stats --}}
    <div class="mb-8 grid grid-cols-2 gap-4">
        <div class="card-hover flex items-center gap-4 rounded-2xl bg-white p-5 shadow-sm dark:bg-gray-800">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-red-500 text-white">
                <x-heroicon-o-fire class="h-6 w-6" />
            </div>
            <div>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $this->pendingOrders->count() }}</p>
                <p class="text-sm text-gray-500">Sedang Dimasak</p>
            </div>
        </div>

        <div class="card-hover flex items-center gap-4 rounded-2xl bg-white p-5 shadow-sm dark:bg-gray-800">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-emerald-500 text-white">
                <x-heroicon-o-check-circle class="h-6 w-6" />
            </div>
            <div>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $this->readyOrders->count() }}</p>
                <p class="text-sm text-gray-500">Siap Disajikan</p>
            </div>
        </div>
    </div>
    @endunless

    @include('filament.pages.kitchen.board', ['pendingOrders' => $this->pendingOrders, 'readyOrders' => $this->readyOrders, 'sop' => $this->sopMinutes, 'display' => $display, 'readonly' => false])
    </div>
</x-filament-panels::page>
