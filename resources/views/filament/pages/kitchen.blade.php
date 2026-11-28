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
        x-data="{ flash: false, audio: null, enableSound() { try { this.audio = new (window.AudioContext || window.webkitAudioContext)(); this.audio.resume(); this.beep(); } catch (e) {} }, beep() { if (! this.audio) return; try { const osc = this.audio.createOscillator(); const gain = this.audio.createGain(); osc.connect(gain); gain.connect(this.audio.destination); osc.frequency.value = 880; gain.gain.value = 0.2; osc.start(); osc.stop(this.audio.currentTime + 0.3); } catch (e) {} } }"
        x-on:kitchen-new-order.window="flash = true; beep(); setTimeout(() => flash = false, 2000)"
        :class="flash && 'ring-4 ring-inset ring-red-500'"
        class="transition-shadow {{ $display ? 'dark min-h-screen bg-gray-950 p-6' : 'rounded-2xl' }}"
    >
    @if($display)
        <style>html { font-size: 20px; }</style>
    @endif
    {{-- Header --}}
    <div class="mb-6 flex items-center justify-between gap-4">
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
            <span class="{{ $display ? 'mr-2 text-2xl font-bold text-white' : 'text-sm font-medium text-gray-500 dark:text-gray-400' }}">{{ $this->getTenant()?->now()->format('H:i') }} {{ $this->getTenant()?->timezoneLabel() }}</span>
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

    {{-- Orders in Progress --}}
    <div class="mb-12">
        <div class="mb-6 flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-red-500 text-white">
                <x-heroicon-o-fire class="h-5 w-5" />
            </div>
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Sedang Dimasak</h2>
            @if($this->pendingOrders->isNotEmpty())
                <span class="rounded-full bg-red-100 px-3 py-1 text-sm font-bold text-red-600 dark:bg-red-500/20 dark:text-red-400">
                    {{ $this->pendingOrders->count() }}
                </span>
            @endif
        </div>

        @if($this->pendingOrders->isEmpty())
            <div class="rounded-2xl border border-gray-200 bg-gray-50 p-12 text-center dark:border-gray-700 dark:bg-gray-800/50">
                <x-heroicon-o-face-smile class="mx-auto h-12 w-12 text-gray-300" />
                <p class="mt-4 text-sm font-medium text-gray-400">Tidak ada pesanan aktif</p>
            </div>
        @else
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5">
                @foreach($this->pendingOrders as $order)
                    @php
                        $waitMinutes = max(0, (int) $order->created_at->diffInMinutes());
                        $urgency = $waitMinutes >= 15 ? 'bg-red-600' : ($waitMinutes >= 10 ? 'bg-amber-500' : 'bg-gray-800 dark:bg-gray-700');
                    @endphp
                    <div class="card-hover overflow-hidden rounded-2xl bg-white shadow-sm dark:bg-gray-800">
                        {{-- Header --}}
                        <div class="{{ $urgency }} p-3 text-white">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2 min-w-0 flex-1">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white/20">
                                        <x-heroicon-o-archive-box class="h-4 w-4" />
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-xs font-bold text-white/90" title="{{ $order->invoice_number }}">{{ $order->invoice_number }}</p>
                                        <p class="truncate text-[11px] text-white/70">{{ $order->table?->name ?? 'Tanpa Meja' }}@if ($order->customer_name) &middot; {{ $order->customer_name }}@endif</p>
                                    </div>
                                </div>
                                <div class="text-right shrink-0 ml-2">
                                    <p class="text-lg font-bold leading-none">{{ $waitMinutes }} mnt</p>
                                    <p class="text-[10px] text-white/70">menunggu</p>
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
            @if($this->readyOrders->isNotEmpty())
                <span class="rounded-full bg-emerald-100 px-3 py-1 text-sm font-bold text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-400">
                    {{ $this->readyOrders->count() }}
                </span>
            @endif
        </div>

        @if($this->readyOrders->isEmpty())
            <div class="rounded-2xl border border-gray-200 bg-gray-50 p-12 text-center dark:border-gray-700 dark:bg-gray-800/50">
                <p class="text-sm text-gray-400">Tidak ada pesanan siap disajikan</p>
            </div>
        @else
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5">
                @foreach($this->readyOrders as $order)
                    <div class="card-hover overflow-hidden rounded-2xl bg-white shadow-sm dark:bg-gray-800">
                        {{-- Header --}}
                        <div class="bg-emerald-600 p-3 text-white">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2 min-w-0 flex-1">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white/20">
                                        <x-heroicon-o-archive-box class="h-4 w-4" />
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-xs font-bold text-white/90" title="{{ $order->invoice_number }}">{{ $order->invoice_number }}</p>
                                        <p class="truncate text-[11px] text-white/70">{{ $order->table?->name ?? 'Tanpa Meja' }}</p>
                                    </div>
                                </div>
                                <div class="text-right shrink-0 ml-2">
                                    <p class="text-lg font-bold leading-none">{{ max(0, (int) ($order->served_at ?? $order->created_at)->diffInMinutes()) }} mnt</p>
                                    <p class="text-[10px] text-white/70">siap</p>
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
    </div>
</x-filament-panels::page>
