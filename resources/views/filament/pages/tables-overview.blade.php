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
            <a href="{{ url('/admin/' . filament()->getTenant()?->id . '/tables') }}"
                class="btn btn-secondary btn-md">
                <x-heroicon-o-cog-6-tooth class="h-4 w-4" />
                Manage Meja
            </a>
            <a href="{{ url('/admin/' . filament()->getTenant()?->id . '/pos') }}"
                class="btn btn-primary btn-md">
                <x-heroicon-o-shopping-cart class="h-4 w-4" />
                Buka POS
            </a>
        </div>
    </div>

    {{-- Quick Stats --}}
    <div class="mb-8 grid grid-cols-3 gap-4">
        <div class="card-hover flex items-center gap-4 p-4">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-500 text-white">
                <x-heroicon-o-check-circle class="h-6 w-6" />
            </div>
            <div>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $this->availableTables->count() }}</p>
                <p class="text-sm text-gray-500">Tersedia</p>
            </div>
        </div>

        <div class="card-hover flex items-center gap-4 p-4">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-red-500 text-white">
                <x-heroicon-o-user-group class="h-6 w-6" />
            </div>
            <div>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $this->activeTables->count() }}</p>
                <p class="text-sm text-gray-500">Terpakai</p>
            </div>
        </div>

        <div class="card-hover flex items-center gap-4 p-4">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-500 text-white">
                <x-heroicon-o-calendar class="h-6 w-6" />
            </div>
            <div>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $this->reservedTables->count() }}</p>
                <p class="text-sm text-gray-500">Dipesan</p>
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
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">Reservasi Hari Ini</h2>
                <span class="rounded-full bg-amber-100 px-3 py-1 text-sm font-bold text-amber-600 dark:bg-amber-500/20 dark:text-amber-400">
                    {{ $this->todayReservations->count() }}
                </span>
            </div>

            <div class="space-y-3">
                @foreach($this->todayReservations as $reservation)
                    <div class="card-hover flex items-center justify-between p-4">
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-100 text-amber-600 dark:bg-amber-500/20 dark:text-amber-400">
                                <x-heroicon-o-user-group class="h-6 w-6" />
                            </div>
                            <div>
                                <p class="text-base font-bold text-gray-900 dark:text-white">{{ $reservation->customer_name }}</p>
                                <p class="text-sm text-gray-500">
                                    {{ $reservation->table?->name ?? 'Meja belum ditentukan' }} &bull;
                                    {{ $reservation->guest_count }} orang &bull;
                                    {{ \Carbon\Carbon::parse($reservation->reservation_time)->format('H:i') }} WIB
                                </p>
                                @if($reservation->customer_phone)
                                    <p class="text-xs text-gray-400">{{ $reservation->customer_phone }}</p>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            @if($reservation->status === 'pending')
                                <button wire:click="confirmReservation({{ $reservation->id }})"
                                    class="btn btn-secondary btn-sm">
                                    <x-heroicon-o-check class="h-4 w-4" />
                                    Konfirmasi
                                </button>
                            @endif
                            @if($reservation->table && $reservation->table->status === 'available')
                                <button wire:click="seatReservation({{ $reservation->id }})"
                                    class="btn btn-primary btn-sm">
                                    <x-heroicon-o-arrow-right-end-on-rectangle class="h-4 w-4" />
                                    Tempatkan
                                </button>
                            @elseif($reservation->table && $reservation->table->status !== 'available')
                                <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-medium text-red-600 dark:bg-red-500/20 dark:text-red-400">
                                    Meja terpakai
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

    {{-- Tables Grid --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">

        {{-- Active Tables --}}
        @foreach($this->activeTables as $table)
            @php
                $tableOrders = $this->tableOrders[$table->id] ?? collect();
                $paidOrders = $tableOrders->where('status', 'completed');
                $servedOrders = $paidOrders->filter(fn($o) => $o->served_at);
                $unservedOrders = $paidOrders->filter(fn($o) => !$o->served_at);
                $unpaidOrders = $tableOrders->where('status', 'pending');
                $totalUnpaid = $unpaidOrders->where('payment_method', 'cash')->sum('grand_total');
                $totalPaid = $paidOrders->sum('grand_total');
            @endphp
            <div class="card-hover overflow-hidden p-0">
                {{-- Header --}}
                <div class="bg-linear-to-br from-red-500 to-red-600 p-4 text-white">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-white/20">
                                <x-heroicon-o-archive-box class="h-6 w-6" />
                            </div>
                            <div>
                                <h3 class="text-lg font-bold">{{ $table->name }}</h3>
                                <p class="text-sm text-red-100">{{ $tableOrders->count() }} pesanan</p>
                            </div>
                        </div>
                        <span class="rounded-full bg-white/20 px-3 py-1 text-xs font-semibold">Terpakai</span>
                    </div>
                </div>

                {{-- Content - Summary Only --}}
                <div class="p-4 space-y-3">
                    {{-- Quick Stats --}}
                    <div class="grid grid-cols-3 gap-2 text-center">
                        @if($servedOrders->count() > 0)
                            <div class="rounded-lg bg-green-50 p-2 dark:bg-green-500/10">
                                <p class="text-lg font-bold text-green-600 dark:text-green-400">{{ $servedOrders->count() }}</p>
                                <p class="text-[10px] text-green-600 dark:text-green-400">Disajikan</p>
                            </div>
                        @endif
                        @if($unservedOrders->count() > 0)
                            <div class="rounded-lg bg-blue-50 p-2 dark:bg-blue-500/10">
                                <p class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ $unservedOrders->count() }}</p>
                                <p class="text-[10px] text-blue-600 dark:text-blue-400">Siap</p>
                            </div>
                        @endif
                        @if($unpaidOrders->count() > 0)
                            <div class="rounded-lg bg-amber-50 p-2 dark:bg-amber-500/10">
                                <p class="text-lg font-bold text-amber-600 dark:text-amber-400">{{ $unpaidOrders->count() }}</p>
                                <p class="text-[10px] text-amber-600 dark:text-amber-400">Tunda</p>
                            </div>
                        @endif
                    </div>

                    {{-- Total --}}
                    @if($totalPaid > 0)
                        <div class="flex items-center justify-between rounded-lg bg-gray-100 p-2 dark:bg-gray-800">
                            <span class="text-xs text-gray-600 dark:text-gray-400">Total</span>
                            <span class="text-sm font-bold text-green-600 dark:text-green-400">Rp {{ number_format($totalPaid, 0, ',', '.') }}</span>
                        </div>
                    @endif

                    {{-- Actions --}}
                    <div class="flex gap-2">
                        <button
                            wire:click="$dispatch('showOrderDetails', { tableId: {{ $table->id }}, tableName: '{{ $table->name }}' })"
                            class="btn btn-secondary btn-md flex-1"
                            aria-label="Lihat pesanan meja {{ $table->name }}">
                            <x-heroicon-o-eye class="h-4 w-4" />
                            Lihat
                        </button>
                        <a href="{{ url('/admin/' . filament()->getTenant()?->id . '/pos?table=' . $table->id) }}"
                            class="btn btn-primary btn-md"
                            aria-label="Tambah pesanan meja {{ $table->name }}">
                            <x-heroicon-o-plus class="h-4 w-4" />
                        </a>
                        <button wire:click="$dispatch('showQrCode', { tableId: {{ $table->id }}, tableName: '{{ $table->name }}' })"
                            class="btn btn-secondary btn-md"
                            aria-label="Tampilkan QR code meja {{ $table->name }}">
                            <x-heroicon-o-qr-code class="h-4 w-4" />
                        </button>
                        <button
                            wire:click="requestCloseTable({{ $table->id }}, '{{ $table->name }}')"
                            class="btn btn-danger btn-md"
                            aria-label="Tutup meja {{ $table->name }}">
                            <x-heroicon-o-x-circle class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </div>
        @endforeach

        {{-- Available Tables --}}
        @foreach($this->availableTables as $table)
            <div class="card-hover overflow-hidden p-0">
                <div class="bg-linear-to-br from-emerald-500 to-emerald-600 p-4 text-white">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-white/20">
                                <x-heroicon-o-archive-box class="h-6 w-6" />
                            </div>
                            <div>
                                <h3 class="text-lg font-bold">{{ $table->name }}</h3>
                            </div>
                        </div>
                        <span class="rounded-full bg-white/20 px-3 py-1 text-xs font-semibold">Kosong</span>
                    </div>
                </div>
                <div class="p-3">
                    <p class="py-8 text-center text-sm text-gray-500">Siap digunakan</p>
                    <div class="flex gap-2">
                        <a href="{{ url('/admin/' . filament()->getTenant()?->id . '/pos?table=' . $table->id) }}"
                            class="btn btn-secondary btn-md flex-1">
                            <x-heroicon-o-plus class="h-4 w-4" />
                            Mulai
                        </a>
                        <button wire:click="$dispatch('showQrCode', { tableId: {{ $table->id }}, tableName: '{{ $table->name }}' })"
                            class="btn btn-secondary btn-md">
                            <x-heroicon-o-qr-code class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </div>
        @endforeach

        {{-- Reserved Tables --}}
        @foreach($this->reservedTables as $table)
            <div class="card-hover overflow-hidden p-0">
                <div class="bg-linear-to-br from-amber-500 to-amber-600 p-4 text-white">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-white/20">
                                <x-heroicon-o-calendar class="h-6 w-6" />
                            </div>
                            <div>
                                <h3 class="text-lg font-bold">{{ $table->name }}</h3>
                            </div>
                        </div>
                        <span class="rounded-full bg-white/20 px-3 py-1 text-xs font-semibold">Dipesan</span>
                    </div>
                </div>
                <div class="p-4">
                    @if($table->notes)
                        <p class="mb-3 text-sm text-gray-500">{{ $table->notes }}</p>
                    @endif
                    <button wire:click="$dispatch('showQrCode', { tableId: {{ $table->id }}, tableName: '{{ $table->name }}' })"
                        class="btn btn-secondary btn-md w-full">
                        <x-heroicon-o-qr-code class="h-4 w-4" />
                        QR Code
                    </button>
                </div>
            </div>
        @endforeach

    </div>

    {{-- Empty State --}}
    @if($this->tables->isEmpty())
        <div class="rounded-2xl border-2 border-dashed border-gray-200 p-12 text-center dark:border-gray-700">
            <x-heroicon-o-archive-box class="mx-auto h-16 w-16 text-gray-300" />
            <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">Belum ada meja</h3>
            <p class="mt-2 text-sm text-gray-500">Tambahkan meja baru untuk mulai</p>
        </div>
    @endif

    {{-- Close Table Confirmation Modal --}}
    @if($showCloseTableConfirm)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div class="w-full max-w-sm rounded-2xl border border-gray-200 bg-white p-6 shadow-2xl dark:border-gray-700 dark:bg-gray-800">
                <div class="text-center">
                    <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-amber-100 text-amber-600 dark:bg-amber-500/20 dark:text-amber-400">
                        <x-heroicon-o-question-mark-circle class="h-7 w-7" />
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Tutup Meja?</h3>
                    <p class="mt-2 text-sm text-gray-500">
                        {{ $pendingCloseTableName }} akan ditutup. Semua pesanan akan dihapus.
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
