<div class="flex items-center justify-between gap-4">
    {{-- Left: Title --}}
    <div class="flex items-center gap-3">
        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gray-100 dark:bg-gray-800">
            <x-heroicon-o-shopping-cart class="h-5 w-5 text-gray-600 dark:text-gray-300" />
        </div>
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">Kasir</h1>
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ filament()->getTenant()?->name ?? 'Kasir' }}</p>
        </div>
    </div>

    {{-- Right: Stats + Actions --}}
    <div class="flex items-center gap-3">
        {{-- Stats Pills - Hidden on mobile --}}
        <div class="hidden lg:flex items-center gap-2 rounded-full bg-white px-4 py-2 shadow-sm ring-1 ring-gray-200/50 dark:bg-gray-800 dark:ring-gray-700/50">
            <div class="flex items-center gap-1.5">
                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-400">
                    <x-heroicon-o-check class="h-3 w-3" />
                </span>
                <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $availableTables->count() }}</span>
                <span class="text-xs text-gray-500 dark:text-gray-400">Tersedia</span>
            </div>

            <div class="h-4 w-px bg-gray-200 dark:bg-gray-700"></div>

            <div class="flex items-center gap-1.5">
                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-red-100 text-red-600 dark:bg-red-500/20 dark:text-red-400">
                    <x-heroicon-o-users class="h-3 w-3" />
                </span>
                <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $activeTables->count() }}</span>
                <span class="text-xs text-gray-500 dark:text-gray-400">Terpakai</span>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="flex items-center gap-2">
            <div
                x-data="{ open: false, connected: false, name: null, width: 32, error: null,
                    init() { this.width = window.KasiraPrinter ? window.KasiraPrinter.width() : 32; this.sync(); window.addEventListener('printer-changed', () => this.sync()); },
                    sync() { const p = window.KasiraPrinter; this.connected = !!(p && p.connected()); this.name = p ? p.name : null; },
                    async pair(kind) { this.error = null; try { const ok = kind === 'usb' ? await window.KasiraPrinter.pairUsb() : await window.KasiraPrinter.pairBle(); if (!ok) this.error = 'Printer tidak bisa dibuka. Di Windows, printer USB biasanya dipegang driver bawaan: pakai Bluetooth, atau ganti drivernya ke WinUSB.'; } catch (e) { this.error = e.message; } },
                    setWidth(w) { this.width = w; window.KasiraPrinter.setWidth(w); },
                    test() { window.KasiraPrinter.print(['Tes cetak Kasira', new Date().toLocaleString('id-ID'), '', 'Printer siap dipakai'], {}).catch(e => this.error = e.message); } }"
                class="relative"
                @click.outside="open = false"
            >
                <button type="button" @click="open = ! open" class="btn btn-md" :class="connected ? 'bg-emerald-600 text-white' : 'btn-secondary'" aria-label="Printer termal">
                    <x-heroicon-o-printer class="h-4 w-4" />
                    <span class="hidden sm:inline" x-text="connected ? name : 'Printer'"></span>
                </button>
                <div x-show="open" x-cloak class="absolute right-0 z-50 mt-2 w-80 rounded-xl border border-gray-200 bg-white p-4 text-left text-sm shadow-lg dark:border-gray-700 dark:bg-gray-800">
                    <p class="font-semibold text-gray-900 dark:text-white" x-text="connected ? 'Terhubung: ' + name : 'Printer termal belum terhubung'"></p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Struk dicetak langsung tanpa dialog cetak. Printer yang pernah dihubungkan tersambung lagi otomatis saat halaman dibuka.</p>
                    <div class="mt-3 flex gap-2">
                        <button type="button" @click="pair('usb')" class="btn btn-secondary btn-sm flex-1">Hubungkan USB</button>
                        <button type="button" @click="pair('ble')" class="btn btn-secondary btn-sm flex-1">Hubungkan Bluetooth</button>
                    </div>
                    <div class="mt-3 flex items-center gap-2 text-xs text-gray-600 dark:text-gray-300">
                        <span class="mr-1">Kertas</span>
                        <button type="button" @click="setWidth(32)" :class="width == 32 ? 'bg-gray-900 text-white dark:bg-white dark:text-gray-900' : 'bg-gray-100 dark:bg-gray-700'" class="rounded-lg px-2 py-1 font-semibold">58 mm</button>
                        <button type="button" @click="setWidth(48)" :class="width == 48 ? 'bg-gray-900 text-white dark:bg-white dark:text-gray-900' : 'bg-gray-100 dark:bg-gray-700'" class="rounded-lg px-2 py-1 font-semibold">80 mm</button>
                    </div>
                    <div class="mt-3 flex gap-2" x-show="connected">
                        <button type="button" @click="test()" class="btn btn-ghost btn-sm flex-1">Tes cetak</button>
                        <button type="button" @click="window.KasiraPrinter.disconnect()" class="btn btn-ghost btn-sm flex-1">Lepas</button>
                    </div>
                    <p x-show="error" x-text="error" x-cloak class="mt-2 text-xs text-red-600"></p>
                    <p x-show="!navigator.usb && !navigator.bluetooth" x-cloak class="mt-2 text-xs text-amber-600">Browser ini tidak mendukung WebUSB atau Web Bluetooth. Pakai Chrome atau Edge.</p>
                </div>
            </div>
            <a href="{{ url('/admin/' . filament()->getTenant()?->slug . '/tables-overview') }}" class="btn btn-secondary btn-md">
                <x-heroicon-o-archive-box class="h-4 w-4" />
                <span class="hidden sm:inline">Cek Meja</span>
            </a>
            <a href="{{ url('/admin/' . filament()->getTenant()?->slug . '/pos') }}" class="btn btn-ghost btn-md">
                <x-heroicon-o-arrow-uturn-left class="h-4 w-4" />
                <span class="hidden sm:inline">Mulai baru</span>
            </a>
        </div>
    </div>
</div>
