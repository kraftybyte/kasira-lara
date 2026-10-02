<div class="flex items-center justify-between gap-4">
    {{-- Left: Title --}}
    <div class="flex items-center gap-3">
        <div class="flex h-10 w-10 items-center justify-center rounded-xl gradient-bg-shadow">
            <x-heroicon-o-shopping-cart class="h-5 w-5 text-white" />
        </div>
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">Point of Sale</h1>
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
            <a href="{{ url('/admin/' . filament()->getTenant()?->slug . '/tables-overview') }}" class="gradient-bg text-white btn btn-md">
                <x-heroicon-o-archive-box class="h-4 w-4" />
                <span class="hidden sm:inline">Cek Meja</span>
            </a>
            <a href="{{ url('/admin/' . filament()->getTenant()?->slug . '/pos') }}" class="btn btn-ghost btn-md">
                <x-heroicon-o-arrow-uturn-left class="h-4 w-4" />
            </a>
        </div>
    </div>
</div>
