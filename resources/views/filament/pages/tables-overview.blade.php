<x-filament-panels::page
    x-data="{
        tableParam: {{ request('table') ? request('table') : 'null' }},
    }"
    x-init="if (tableParam) { setTimeout(() => { const tables = {{ Js::from($this->tables) }}; const table = tables.find(t => t.id == tableParam); window.dispatchEvent(new CustomEvent('showOrderDetails', { detail: { tableId: tableParam, tableName: table ? table.name : 'Meja' }}) }, 100) }"
>

    @php
        $tenant = filament()->getTenant();
    @endphp

    {{-- Header --}}
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Manajemen Meja</h1>
            <p class="mt-1 text-sm text-gray-500">Kelola meja dan pantau pesanan customer</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ url('/admin/' . filament()->getTenant()?->slug . '/tables') }}"
                class="btn btn-secondary btn-md">
                <x-heroicon-o-cog-6-tooth class="h-4 w-4" />
                Manage Meja
            </a>
            <a href="{{ url('/admin/' . filament()->getTenant()?->slug . '/pos') }}"
                class="btn btn-primary btn-md">
                <x-heroicon-o-shopping-cart class="h-4 w-4" />
                Buka POS
            </a>
        </div>
    </div>

    {{-- Quick Stats --}}
    @php
        $showFilteredStats = $this->focusedTableId > 0;
        $filteredActiveCount = $showFilteredStats ? $this->activeTables->filter(fn($t) => $t->id === $this->focusedTableId)->count() : $this->activeTables->count();
        $filteredAvailableCount = $showFilteredStats ? $this->availableTables->filter(fn($t) => $t->id === $this->focusedTableId)->count() : $this->availableTables->count();
    @endphp
    <div class="mb-8 grid grid-cols-3 gap-3 sm:gap-4">
        <div class="card-hover flex items-center gap-3 rounded-2xl bg-white p-4 shadow-sm dark:bg-gray-800">
            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-500 text-white shrink-0">
                <x-heroicon-o-check-circle class="h-5 w-5" />
            </div>
            <div class="min-w-0">
                <p class="text-xl font-bold text-gray-900 dark:text-white sm:text-2xl">{{ $showFilteredStats ? $filteredAvailableCount : $this->availableTables->count() }}</p>
                <p class="text-xs text-gray-500 sm:text-sm">Tersedia</p>
            </div>
        </div>

        <div class="card-hover flex items-center gap-3 rounded-2xl bg-white p-4 shadow-sm dark:bg-gray-800">
            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-red-500 text-white shrink-0">
                <x-heroicon-o-user-group class="h-5 w-5" />
            </div>
            <div class="min-w-0">
                <p class="text-xl font-bold text-gray-900 dark:text-white sm:text-2xl">{{ $showFilteredStats ? $filteredActiveCount : $this->activeTables->count() }}</p>
                <p class="text-xs text-gray-500 sm:text-sm">Terpakai</p>
            </div>
        </div>

        <div class="card-hover flex items-center gap-3 rounded-2xl bg-white p-4 shadow-sm dark:bg-gray-800">
            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-500 text-white shrink-0">
                <x-heroicon-o-calendar class="h-5 w-5" />
            </div>
            <div class="min-w-0">
                <p class="text-xl font-bold text-gray-900 dark:text-white sm:text-2xl">{{ $this->reservedTables->count() }}</p>
                <p class="text-xs text-gray-500 sm:text-sm">Dipesan</p>
            </div>
        </div>
    </div>

    {{-- Today's Reservations --}}
    @if($this->todayReservations->isNotEmpty())
        <div class="mb-8">
            <div class="mb-4 flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-500 text-white">
                    <x-heroicon-o-calendar class="h-5 w-5" />
                </div>
                <div class="flex items-center gap-2">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white sm:text-xl">Reservasi Hari Ini</h2>
                    <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-bold text-amber-600 dark:bg-amber-500/20 dark:text-amber-400">
                        {{ $this->todayReservations->count() }}
                    </span>
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($this->todayReservations as $reservation)
                    <div class="card-hover flex flex-col gap-3 rounded-2xl bg-white p-4 shadow-sm dark:bg-gray-800 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-3">
                            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-100 text-amber-600 dark:bg-amber-500/20 dark:text-amber-400 shrink-0">
                                <x-heroicon-o-user-group class="h-5 w-5" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $reservation->customer_name }}</p>
                                <p class="text-xs text-gray-500">
                                    {{ $reservation->table?->name ?? 'Belum dipilih' }} &bull;
                                    {{ $reservation->guest_count }} orang &bull;
                                    {{ \Carbon\Carbon::parse($reservation->reservation_time)->format('H:i') }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 ml-14 sm:ml-0">
                            @if($reservation->status === 'pending')
                                <button wire:click="confirmReservation({{ $reservation->id }})"
                                    class="btn btn-secondary btn-sm">
                                    <x-heroicon-o-check class="h-4 w-4" />
                                </button>
                            @endif
                            @if($reservation->table && $reservation->table->status === 'available')
                                <button wire:click="seatReservation({{ $reservation->id }})"
                                    class="btn btn-primary btn-sm">
                                    <x-heroicon-o-arrow-right-end-on-rectangle class="h-4 w-4" />
                                    <span class="hidden sm:inline">Duduk</span>
                                </button>
                            @elseif($reservation->table && $reservation->table->status !== 'available')
                                <span class="rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-medium text-red-600 dark:bg-red-500/20 dark:text-red-400">
                                    Terpakai
                                </span>
                            @endif
                            <button wire:click="cancelReservation({{ $reservation->id }})"
                                class="btn btn-ghost btn-sm text-red-500 hover:bg-red-50">
                                <x-heroicon-o-x-mark class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Section Title --}}
    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white sm:text-xl">Daftar Meja</h2>
        <div class="flex items-center gap-2">
            <button
                wire:click="$dispatch('showAllOrders')"
                class="btn btn-secondary btn-sm">
                <x-heroicon-o-list-bullet class="h-4 w-4" />
                Semua Pesanan
            </button>
            <span class="text-xs text-gray-500">{{ $this->tables->count() }} meja</span>
        </div>
    </div>

    {{-- Tables Grid --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">

        {{-- Active Tables (with orders) --}}
        @foreach($this->activeTables as $table)
            @php
                $allOrders = $this->tableOrders[$table->id] ?? collect();
                $paidOrders = $allOrders->where('status', 'completed');
                $servedOrders = $paidOrders->filter(fn($o) => $o->served_at);
                $unservedOrders = $paidOrders->filter(fn($o) => !$o->served_at);
                $unpaidOrders = $allOrders->whereIn('status', ['open', 'pending']);
                $totalPaid = $paidOrders->sum('grand_total');
                $allPaid = $paidOrders->count() > 0 && $unpaidOrders->count() === 0;
            @endphp
            <div class="card-hover overflow-hidden rounded-xl bg-white shadow-sm dark:bg-gray-800">
                {{-- Header --}}
                <div class="bg-gradient-to-br {{ $allPaid ? 'from-emerald-500 to-emerald-600' : 'from-red-500 to-red-600' }} px-4 py-3 text-white">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-white/20">
                                <x-heroicon-o-archive-box class="h-5 w-5" />
                            </div>
                            <div>
                                <h3 class="text-base font-bold">{{ $table->name }}</h3>
                                <p class="text-xs text-red-100">
                                    {{ $allOrders->count() }} pesanan aktif
                                </p>
                            </div>
                        </div>
                        <span class="rounded-full bg-white/20 px-2.5 py-1 text-xs font-semibold flex items-center gap-1">
                            @if($allPaid)
                                <x-heroicon-o-check-circle class="h-3 w-3" />
                                Menunggu Ditutup
                            @else
                                <x-heroicon-o-clock class="h-3 w-3" />
                                Terpakai
                            @endif
                        </span>
                    </div>
                </div>

                {{-- Content --}}
                <div class="p-4 space-y-3">
                    @if($allPaid)
                        {{-- ALL PAID: Clean layout --}}
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300">
                                <x-heroicon-o-check-circle class="h-3.5 w-3.5" />
                                Semua Lunas
                            </span>
                            @if($paidOrders->count() > 0)
                                <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                    {{ $paidOrders->count() }} pesanan
                                </span>
                            @endif
                        </div>

                        @if($servedOrders->count() > 0 || $unservedOrders->count() > 0)
                            <div class="flex flex-wrap items-center gap-2">
                                @if($servedOrders->count() > 0)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-700 dark:bg-green-500/20 dark:text-green-300">
                                        <x-heroicon-o-check class="h-3 w-3" />
                                        {{ $servedOrders->count() }} Disajikan
                                    </span>
                                @endif
                                @if($unservedOrders->count() > 0)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-700 dark:bg-blue-500/20 dark:text-blue-300">
                                        <x-heroicon-o-bell class="h-3 w-3" />
                                        {{ $unservedOrders->count() }} Siap
                                    </span>
                                @endif
                            </div>
                        @endif

                        <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 dark:border-emerald-800 dark:bg-emerald-500/10">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-emerald-700 dark:text-emerald-300">Total Bayar</span>
                                <span class="text-xl font-black text-emerald-600 dark:text-emerald-200">
                                    Rp {{ number_format($totalPaid, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    @else
                        {{-- ACTIVE: Show pending --}}
                        <div class="grid grid-cols-3 gap-2">
                            @if($servedOrders->count() > 0)
                                <div class="rounded-lg bg-green-50 p-2.5 text-center dark:bg-green-500/10">
                                    <p class="text-lg font-bold text-green-600 dark:text-green-300">{{ $servedOrders->count() }}</p>
                                    <p class="text-[10px] font-medium text-green-600 dark:text-green-400">Disajikan</p>
                                </div>
                            @endif
                            @if($unservedOrders->count() > 0)
                                <div class="rounded-lg bg-blue-50 p-2.5 text-center dark:bg-blue-500/10">
                                    <p class="text-lg font-bold text-blue-600 dark:text-blue-300">{{ $unservedOrders->count() }}</p>
                                    <p class="text-[10px] font-medium text-blue-600 dark:text-blue-400">Siap</p>
                                </div>
                            @endif
                            @if($unpaidOrders->count() > 0)
                                <div class="rounded-lg bg-amber-50 p-2.5 text-center dark:bg-amber-500/10">
                                    <p class="text-lg font-bold text-amber-600 dark:text-amber-300">{{ $unpaidOrders->count() }}</p>
                                    <p class="text-[10px] font-medium text-amber-600 dark:text-amber-400">Tunda</p>
                                </div>
                            @endif
                        </div>

                        @if($totalPaid > 0)
                            <div class="flex items-center justify-between rounded-lg bg-gray-100 p-2 dark:bg-gray-700">
                                <span class="text-xs font-medium text-gray-600 dark:text-gray-300">Lunas</span>
                                <span class="text-base font-bold text-green-600 dark:text-green-300">Rp {{ number_format($totalPaid, 0, ',', '.') }}</span>
                            </div>
                        @endif
                    @endif

                    {{-- Actions --}}
                    <div class="flex gap-2">
                        <button
                            wire:click="$dispatch('showOrderDetails', { tableId: {{ $table->id }}, tableName: '{{ $table->name }}' })"
                            class="btn btn-secondary btn-sm flex-1">
                            <x-heroicon-o-eye class="h-4 w-4" />
                            <span class="hidden sm:inline">Lihat</span>
                        </button>
                        <a href="{{ url('/admin/' . filament()->getTenant()?->slug . '/pos?table=' . $table->id) }}"
                            class="btn btn-primary btn-sm">
                            <x-heroicon-o-plus class="h-4 w-4" />
                        </a>
                        <button wire:click="$dispatch('showQrCode', { tableId: {{ $table->id }}, tableName: '{{ $table->name }}' })"
                            class="btn btn-secondary btn-sm">
                            <x-heroicon-o-qr-code class="h-4 w-4" />
                        </button>
                        <button
                            wire:click="requestCloseTable({{ $table->id }}, '{{ $table->name }}')"
                            class="btn btn-danger btn-sm">
                            <x-heroicon-o-x-circle class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </div>
        @endforeach

        {{-- Available Tables --}}
        @foreach($this->availableTables as $table)
            <div class="card-hover overflow-hidden rounded-xl bg-white shadow-sm dark:bg-gray-800">
                <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 px-4 py-3 text-white">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-white/20">
                                <x-heroicon-o-archive-box class="h-5 w-5" />
                            </div>
                            <div>
                                <h3 class="text-base font-bold">{{ $table->name }}</h3>
                                <p class="text-xs text-emerald-100">Siap digunakan</p>
                            </div>
                        </div>
                        <span class="rounded-full bg-white/20 px-2.5 py-1 text-xs font-semibold flex items-center gap-1">
                            <x-heroicon-o-check-circle class="h-3 w-3" />
                            Kosong
                        </span>
                    </div>
                </div>
                <div class="p-4 space-y-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300">
                            <x-heroicon-o-check-circle class="h-3.5 w-3.5" />
                            Tersedia
                        </span>
                    </div>

                    <div class="flex gap-2">
                        <a href="{{ url('/admin/' . filament()->getTenant()?->slug . '/pos?table=' . $table->id) }}"
                            class="btn btn-primary btn-sm flex-1">
                            <x-heroicon-o-plus class="h-4 w-4" />
                            <span class="hidden sm:inline">Mulai</span>
                        </a>
                        <button wire:click="$dispatch('showQrCode', { tableId: {{ $table->id }}, tableName: '{{ $table->name }}' })"
                            class="btn btn-secondary btn-sm">
                            <x-heroicon-o-qr-code class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </div>
        @endforeach

        {{-- Reserved Tables --}}
        @foreach($this->reservedTables as $table)
            <div class="card-hover overflow-hidden rounded-xl bg-white shadow-sm dark:bg-gray-800">
                <div class="bg-gradient-to-br from-amber-500 to-amber-600 px-4 py-3 text-white">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-white/20">
                                <x-heroicon-o-calendar class="h-5 w-5" />
                            </div>
                            <div>
                                <h3 class="text-base font-bold">{{ $table->name }}</h3>
                                <p class="text-xs text-amber-100">Dipesan</p>
                            </div>
                        </div>
                        <span class="rounded-full bg-white/20 px-2.5 py-1 text-xs font-semibold flex items-center gap-1">
                            <x-heroicon-o-calendar class="h-3 w-3" />
                            Dipesan
                        </span>
                    </div>
                </div>
                <div class="p-4 space-y-3">
                    @if($table->notes)
                        <p class="text-xs text-gray-600 dark:text-gray-400">{{ $table->notes }}</p>
                    @endif
                    <button wire:click="$dispatch('showQrCode', { tableId: {{ $table->id }}, tableName: '{{ $table->name }}' })"
                        class="btn btn-secondary btn-sm w-full">
                        <x-heroicon-o-qr-code class="h-4 w-4" />
                        Tampilkan QR Code
                    </button>
                </div>
            </div>
        @endforeach

    </div>

    {{-- Empty State --}}
    @if($this->tables->isEmpty())
        <div class="rounded-2xl border-2 border-dashed border-gray-200 bg-white p-12 text-center dark:border-gray-700 dark:bg-gray-800">
            <x-heroicon-o-archive-box class="mx-auto h-14 w-14 text-gray-300" />
            <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">Belum ada meja</h3>
            <p class="mt-2 text-sm text-gray-500">Tambahkan meja baru untuk mulai</p>
            <a href="{{ url('/admin/' . filament()->getTenant()?->slug . '/tables') }}"
                class="btn btn-primary btn-md mt-4">
                <x-heroicon-o-plus class="h-4 w-4" />
                Tambah Meja
            </a>
        </div>
    @endif

    {{-- Close Table Confirmation Modal --}}
    @if($showCloseTableConfirm)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
            <div class="w-full max-w-sm rounded-2xl border border-gray-200 bg-white shadow-2xl dark:border-gray-700 dark:bg-gray-800 overflow-hidden">
                <div class="p-6 text-center">
                    <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-amber-100 text-amber-600 dark:bg-amber-500/20 dark:text-amber-400">
                        <x-heroicon-o-question-mark-circle class="h-6 w-6" />
                    </div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">Tutup Meja?</h3>
                    <p class="mt-2 text-sm text-gray-500">
                        {{ $pendingCloseTableName }} akan diselesaikan. Pastikan semua pesanan sudah diproses.
                    </p>
                    <div class="mt-6 flex gap-3">
                        <button type="button"
                                wire:click="cancelCloseTable"
                                class="flex-1 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                            Batal
                        </button>
                        <button type="button"
                                wire:click="confirmCloseTable"
                                class="flex-1 rounded-xl bg-red-500 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-red-600">
                            Ya, Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- QR Code Modal --}}
    @livewire(\App\Livewire\TableQrModal::class)

    {{-- Order Details Modal --}}
    @livewire(\App\Livewire\OrderDetailsModal::class)

</x-filament-panels::page>
