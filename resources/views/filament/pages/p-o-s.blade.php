<x-filament-panels::page>

    {{-- Active Table Banner --}}
    @if($this->activeTableId || $this->tableId)
        <div class="mb-3 rounded-xl border-2 border-emerald-200 bg-emerald-50 p-3 dark:border-emerald-500/30 dark:bg-emerald-500/10">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-500 text-white">
                        @if($this->tableId === 'takeaway')
                            <x-heroicon-o-shopping-bag class="h-6 w-6" />
                        @else
                            <x-heroicon-o-archive-box class="h-6 w-6" />
                        @endif
                    </div>
                    <div>
                        @if($this->tableId === 'takeaway')
                            <p class="text-lg font-bold text-gray-900 dark:text-white">Take Away</p>
                            <p class="text-sm text-emerald-600 dark:text-emerald-400">Bawa Pulang</p>
                        @elseif($this->reservation && $this->reservation->status !== 'seated')
                            <p class="text-lg font-bold text-gray-900 dark:text-white">Reservasi</p>
                            <p class="text-sm text-emerald-600 dark:text-emerald-400">{{ $this->reservation->customer_name }} - {{ $this->reservation->guest_count }} orang</p>
                        @elseif($this->activeTableId)
                            <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $this->selectedTable?->name ?? 'Meja Aktif' }}</p>
                            <p class="text-sm text-emerald-600 dark:text-emerald-400">Bill sedang aktif</p>
                        @elseif($this->tableId)
                            <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $this->selectedTable?->name ?? 'Meja Dipilih' }}</p>
                            <p class="text-sm text-gray-500">Siap menambahkan item</p>
                        @endif
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    @if($this->reservation && $this->reservation->status !== 'seated')
                        <button type="button" wire:click="seatReservation" class="btn btn-primary btn-md">
                            <x-heroicon-o-user-group class="h-4 w-4" />
                            Tempatkan Tamu
                        </button>
                    @endif
                    @if($this->activeTableId)
                        <button type="button" wire:click="saveToTable" @disabled(empty($this->cart)) class="btn btn-outline-success btn-md">
                            <x-heroicon-o-check class="h-4 w-4" />
                            Simpan
                        </button>
                    @endif
                    <button type="button" wire:click="clearTable" class="btn btn-outline-danger btn-md">
                        <x-heroicon-o-x-mark class="h-4 w-4" />
                        @if($this->tableId === 'takeaway')
                            Batal
                        @else
                            Tutup Meja
                        @endif
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Bulk Counter Mode Banner --}}
    @if($this->isBulkCounterMode)
        <div class="mb-3 rounded-xl border-2 border-amber-200 bg-amber-50 p-3 dark:border-amber-500/30 dark:bg-amber-500/10">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-500 text-white">
                    <x-heroicon-s-banknotes class="h-5 w-5" />
                </div>
                <div>
                    <p class="text-sm font-bold text-gray-900 dark:text-white">Mode Bayar Counter</p>
                    <p class="text-xs text-amber-600 dark:text-amber-400">Item dari pesanan counter telah dimuat. Selesaikan pembayaran untuk menghapus pesanan counter.</p>
                </div>
            </div>
        </div>
    @endif

    {{-- Active Tables Quick View --}}
    @if($this->activeTables->count() > 0 && !$this->activeTableId)
        <div class="mb-3 rounded-xl border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-900">
            <div class="mb-2 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-red-500 text-white">
                        <x-heroicon-o-archive-box class="h-3.5 w-3.5" />
                    </span>
                    <span class="text-xs font-semibold text-gray-900 dark:text-white">Meja Terpakai</span>
                    <span class="rounded-full bg-red-100 px-1.5 py-0.5 text-[10px] font-bold text-red-600 dark:bg-red-500/20 dark:text-red-400">{{ $this->activeTables->count() }}</span>
                </div>
                <a href="{{ url('/admin/' . filament()->getTenant()?->slug . '/tables') }}" class="text-[10px] font-medium text-red-500 hover:text-red-600">Lihat Semua</a>
            </div>
            <div class="flex flex-wrap gap-2">
                @foreach($this->activeTables as $table)
                    @php
                        $tableSales = $table->sales ?? collect();
                        $unpaidCash = $tableSales->where('status', 'pending')->where('payment_method', 'pending')->sum('grand_total');
                        $paidOrders = $tableSales->where('status', 'completed')->sum('grand_total');
                        $hasUnpaidCash = $unpaidCash > 0;
                    @endphp
                    <a href="{{ url('/admin/' . filament()->getTenant()?->slug . '/tables-overview?table=' . $table->id) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-gray-100 bg-gray-50 px-3 py-1.5 text-center transition-all hover:border-red-300 hover:bg-red-50/50 dark:border-gray-700 dark:bg-gray-800 dark:hover:border-red-500/30">
                        <div class="flex h-5 w-5 items-center justify-center rounded-lg bg-red-100 text-red-600 dark:bg-red-500/20 dark:text-red-400">
                            <x-heroicon-o-archive-box class="h-3 w-3" />
                        </div>
                        <span class="text-[10px] font-semibold text-gray-900 dark:text-white">{{ $table->name }}</span>
                        @if($hasUnpaidCash)
                            <span class="rounded-full bg-amber-100 px-1.5 py-0.5 text-[9px] font-medium text-amber-700 dark:bg-amber-500/20 dark:text-amber-400">Rp {{ number_format($unpaidCash/1000, 0, ',', '.') }}k</span>
                        @elseif($paidOrders > 0)
                            <span class="rounded-full bg-emerald-100 px-1.5 py-0.5 text-[9px] font-medium text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-400">Lunas</span>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-3 lg:grid-cols-12">

        {{-- PRODUCTS --}}
        <div class="lg:col-span-8">

            {{-- Search & Category Filter --}}
            <div class="mb-3 space-y-2">
                {{-- Search Bar --}}
                <div class="relative">
                    <x-filament::input.wrapper>
                        <x-filament::input
                            type="search"
                            wire:model.live.debounce.300ms="search"
                            placeholder="Cari produk atau SKU..."
                            class="pl-10 py-1.5 text-sm"
                        />
                        <x-slot name="prefix">
                            <x-heroicon-o-magnifying-glass class="h-4 w-4 text-gray-400" />
                        </x-slot>
                    </x-filament::input.wrapper>
                </div>

                {{-- Category Pills --}}
                <div class="flex items-center gap-1.5 overflow-x-auto pb-1 scrollbar-thin">
                    <button
                        type="button"
                        wire:click="$set('selectedCategoryId', null)"
                        class="{{ is_null($this->selectedCategoryId) ? 'gradient-bg text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700' }} shrink-0 rounded-full px-3 py-1.5 text-xs font-semibold transition-all hover:-translate-y-0.2"
                    >
                        Semua
                    </button>
                    @foreach($this->categories as $category)
                        <button
                            type="button"
                            wire:click="$set('selectedCategoryId', {{ $category->id }})"
                            class="{{ $this->selectedCategoryId === $category->id ? 'gradient-bg text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700' }} shrink-0 rounded-full px-3 py-1.5 text-xs font-semibold transition-all hover:-translate-y-0.5"
                        >
                            {{ $category->name }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="p-2">
                    {{-- Loading State --}}
                    <div wire:loading class="mb-3 flex items-center justify-center rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                        <div class="flex items-center gap-2 text-sm text-gray-500">
                            <svg class="h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>Memuat produk...</span>
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-2 sm:grid-cols-4 lg:grid-cols-4 max-h-[calc(100vh-280px)] overflow-y-auto">
                        @forelse ($this->products as $product)
                            <button
                                type="button"
                                wire:click="addToCart({{ $product->id }})"
                                wire:loading.attr="disabled"
                                class="group flex flex-col overflow-hidden rounded-lg border border-gray-100 bg-white transition-all hover:shadow-md disabled:cursor-wait disabled:opacity-50 dark:border-gray-700 dark:bg-gray-800"
                            >
                                {{-- Image (Square) --}}
                                <div class="relative aspect-square w-full shrink-0 overflow-hidden bg-gray-50 dark:bg-gray-700">
                                    @if ($product->image)
                                        <img src="{{ Storage::disk('public')->url($product->image) }}" alt="{{ $product->name }}" class="h-full w-full object-cover">
                                    @else
                                        <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600">
                                            <span class="text-lg font-bold uppercase text-gray-300">{{ substr($product->name, 0, 2) }}</span>
                                        </div>
                                    @endif

                                    {{-- Badge --}}
                                    @if ($product->rate_type === 'duration')
                                        <div class="absolute left-1 top-1">
                                            <span class="rounded-full bg-amber-500 px-2 py-0.5 text-xs font-semibold text-white shadow">Per Jam</span>
                                        </div>
                                    @endif
                                    @if ($product->rate_type !== 'duration' && (float) $product->stock <= 0)
                                        <div class="absolute left-1 top-1">
                                            <span class="rounded-full bg-red-500 px-2 py-0.5 text-xs font-semibold text-white shadow">Habis</span>
                                        </div>
                                    @endif
                                </div>

                                {{-- Info --}}
                                <div class="flex flex-1 flex-col p-2">
                                    <div class="flex flex-1 flex-col items-center justify-center text-center">
                                        <h3 class="text-xs font-medium leading-tight text-gray-900 dark:text-white">{{ $product->name }}</h3>
                                        <p class="mt-1 flex items-center gap-1 text-sm font-bold text-red-500">
                                            <span>Rp {{ number_format((float) $product->selling_price, 0, ',', '.') }}</span>
                                            @if($product->rate_type === 'duration')
                                                @if((float) $product->rate >= 60)
                                                    <span class="text-[10px] font-medium text-amber-600">/ Jam</span>
                                                @else
                                                    <span class="text-[10px] font-medium text-amber-600">/ {{ (int) $product->rate }} Menit</span>
                                                @endif
                                            @endif
                                        </p>
                                    </div>
                                    <div class="mt-2 flex justify-center">
                                        <span class="flex h-7 w-7 items-center justify-center rounded-full bg-red-500 text-xs font-bold text-white shadow transition-transform duration-200 group-hover:scale-110">
                                            +
                                        </span>
                                    </div>
                                </div>
                            </button>
                        @empty
                            <div class="col-span-full py-12 text-center">
                                <p class="text-sm text-gray-500 dark:text-gray-400">Tidak ada produk</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
        {{-- CUSTOMER & CART --}}
        <div class="lg:col-span-4">
            <div class="flex flex-col gap-4">

                {{-- ====================================================
                    CUSTOMER
                ===================================================== --}}
                <div
                    class="overflow-visible rounded-2xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900"
                    x-data="{
                        open: false,
                        search: ''
                    }"
                    @click.outside="open = false"
                >

                    {{-- HEADER --}}
                    <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3 dark:border-gray-700">

                        <div class="flex items-center gap-2.5">

                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 text-red-500 dark:bg-red-500/10">

                                <x-heroicon-o-user class="h-4 w-4" />

                            </div>

                            <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                Customer
                            </span>

                        </div>


                        {{-- ADD CUSTOMER --}}
                        <button
                            type="button"
                            wire:click="openAddCustomer"
                            class="flex h-7 w-7 items-center justify-center rounded-lg text-red-500 transition-colors hover:bg-red-50 dark:hover:bg-red-500/10"
                        >

                            <x-heroicon-o-user class="h-4 w-4" />

                        </button>

                    </div>


                    {{-- CUSTOMER CONTENT --}}
                    <div class="space-y-2.5 p-3">


                        {{-- =================================================
                            SEARCH CUSTOMER
                        ================================================== --}}
                        <div class="relative">

                            {{-- SEARCH INPUT --}}
                            <div class="relative">

                                <input
                                    type="text"
                                    x-model="search"
                                    @focus="open = true"
                                    placeholder="Cari nama atau nomor HP..."
                                    class="block w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 pl-9 pr-9 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-red-500 focus:ring-1 focus:ring-red-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-500"
                                >


                                {{-- CLEAR --}}
                                <button
                                    type="button"
                                    x-show="search.length > 0"
                                    x-cloak
                                    @click="search = ''"
                                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                                    aria-label="Clear search"
                                >
                                    <x-heroicon-o-x-mark class="h-4 w-4" />
                                </button>

                            </div>


                            {{-- =================================================
                                DROPDOWN
                            ================================================== --}}
                            <div
                                x-show="open"
                                x-cloak
                                x-transition:enter="transition ease-out duration-100"
                                x-transition:enter-start="opacity-0 -translate-y-1"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                class="absolute left-0 right-0 top-full z-50 mt-1 overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800"
                            >

                                {{-- WALK IN --}}
                                <button
                                    type="button"
                                    wire:click="$set('customerId', null)"
                                    @click="search = ''; open = false"
                                    class="flex w-full items-center gap-3 border-b border-gray-100 px-3 py-2.5 text-left transition hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-700"
                                >

                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-300">

                                        <x-heroicon-o-user class="h-4 w-4" />

                                    </div>

                                    <div class="min-w-0 flex-1">

                                        <p class="text-xs font-semibold text-gray-900 dark:text-white">
                                            Walk-in Customer
                                        </p>

                                        <p class="text-[11px] text-gray-500 dark:text-gray-400">
                                            Tanpa customer terdaftar
                                        </p>

                                    </div>

                                    @if (!$customerId)

                                        <x-heroicon-o-user class="h-4 w-4 text-red-500" />

                                    @endif

                                </button>


                                {{-- CUSTOMER LIST --}}
                                <div class="scrollbar-thin max-h-60 overflow-y-auto">

                                    @foreach($this->customers as $customer)

                                        <button
                                            type="button"
                                            x-show="
                                                search === '' ||
                                                '{{ strtolower($customer->name) }}'.includes(search.toLowerCase()) ||
                                                '{{ strtolower($customer->phone ?? '') }}'.includes(search.toLowerCase())
                                            "
                                            wire:click="$set('customerId', {{ $customer->id }})"
                                            @click="search = ''; open = false"
                                            class="flex w-full items-center gap-3 px-3 py-2.5 text-left transition hover:bg-red-50 dark:hover:bg-red-500/10"
                                        >

                                            {{-- AVATAR --}}
                                            <div
                                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-xs font-bold text-white"
                                                class="gradient-bg"
                                            >
                                                {{ strtoupper(substr($customer->name, 0, 1)) }}
                                            </div>


                                            {{-- INFO --}}
                                            <div class="min-w-0 flex-1">

                                                <div class="flex items-center gap-1">

                                                    <p class="truncate text-xs font-semibold text-gray-900 dark:text-white">
                                                        {{ $customer->name }}
                                                    </p>

                                                    @if ($customer->is_member)

                                                        <span class="text-[10px] text-amber-500">
                                                            ★
                                                        </span>

                                                    @endif

                                                </div>

                                                <p class="truncate text-[11px] text-gray-500 dark:text-gray-400">
                                                    {{ $customer->phone ?: 'Tidak ada nomor HP' }}
                                                </p>

                                            </div>


                                            {{-- MEMBER --}}
                                            @if ($customer->is_member)

                                                <span class="hidden rounded-full bg-amber-100 px-1.5 py-0.5 text-[9px] font-bold text-amber-600 sm:inline-flex dark:bg-amber-500/20 dark:text-amber-400">
                                                    Member
                                                </span>

                                            @endif


                                            {{-- CHECK --}}
                                            @if ($customerId == $customer->id)

                                                <x-heroicon-o-user class="h-4 w-4 shrink-0 text-red-500" />

                                            @endif

                                        </button>

                                    @endforeach


                                </div>

                            </div>

                        </div>


                        {{-- =================================================
                            SELECTED CUSTOMER
                        ================================================== --}}
                        @if ($customerId)

                            @php
                                $selectedCustomer = $this->customers->firstWhere('id', $customerId);
                            @endphp

                            @if ($selectedCustomer)

                                <div class="flex items-center gap-2.5 rounded-xl border border-red-100 bg-red-50/50 p-2.5 dark:border-red-500/20 dark:bg-red-500/5">

                                    <div
                                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-white"
                                        class="gradient-bg"
                                    >

                                        <x-heroicon-o-user class="h-3.5 w-3.5" />

                                    </div>


                                    <div class="min-w-0 flex-1">

                                        <p class="truncate text-xs font-semibold text-gray-900 dark:text-white">
                                            {{ $selectedCustomer->name }}
                                        </p>

                                        <p class="truncate text-[11px] text-gray-500 dark:text-gray-400">
                                            {{ $selectedCustomer->phone ?: '-' }}
                                        </p>

                                    </div>


                                    @if ($selectedCustomer->is_member)

                                        <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-600 dark:bg-amber-500/20 dark:text-amber-400">
                                            ★ Member
                                        </span>

                                    @endif

                                </div>

                            @endif

                        @endif

                    </div>

                </div>

                {{-- CART --}}
                <div class="overflow-hidden rounded-xl border border-gray-200/80 bg-white shadow-sm dark:border-gray-700/80 dark:bg-gray-900">
                    <div class="flex items-center justify-between border-b border-gray-100/80 px-3 py-2 dark:border-gray-700/50">
                        <div class="flex items-center gap-2">
                            <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-red-50 text-red-500 dark:bg-red-500/10">
                                <x-heroicon-o-shopping-cart class="h-4 w-4" />
                            </div>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">Keranjang</span>
                            @if ($this->cartCount > 0)
                                <span class="flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1.5 text-[10px] font-bold text-white">{{ $this->cartCount }}</span>
                            @endif
                        </div>
                        @if (count($cart) > 0)
                            <button type="button" wire:click="clearCart" wire:confirm="Yakin ingin menghapus semua item dari keranjang?" class="text-xs font-medium text-red-500 transition-colors hover:text-red-600">Hapus semua</button>
                        @endif
                    </div>
                    <div class="scrollbar-thin p-3 max-h-48 overflow-y-auto">
                        @forelse ($cart as $productId => $item)
                            @php
                                // Handle variant keys (e.g., "1_1699999999")
                                $baseProductId = is_numeric($productId) ? $productId : explode('_', $productId)[0];
                                $cartProduct = \App\Models\Product::find($baseProductId);
                                $isDuration = $item['is_duration'] ?? false;
                                $modifiers = $item['modifiers'] ?? [];
                                $hasModifiers = !empty($modifiers);
                                $hasNotes = !empty($item['notes']);
                                $isVariant = $item['is_variant'] ?? false;
                                $isCompleted = $item['is_completed'] ?? false;
                            @endphp
                            <div wire:key="cart-item-{{ $productId }}" class="mb-2 flex items-start gap-2 rounded-xl border border-gray-100 bg-gray-50/50 p-2.5 dark:border-gray-700/50 dark:bg-gray-800/50 {{ $isDuration ? 'border-amber-200 dark:border-amber-700/50' : '' }} {{ $isCompleted ? 'border-emerald-200 dark:border-emerald-700/50 bg-emerald-50/30 dark:bg-emerald-500/10' : '' }}">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-lg {{ $isDuration ? 'bg-amber-100 dark:bg-amber-500/20' : 'bg-gray-200 dark:bg-gray-700' }}">
                                    @if($cartProduct?->image)
                                        <img src="{{ Storage::disk('public')->url($cartProduct->image) }}" alt="{{ $item['product_name'] }}" class="h-full w-full object-cover">
                                    @else
                                        <x-heroicon-o-clock class="h-4 w-4 {{ $isDuration ? 'text-amber-600 dark:text-amber-400' : 'text-gray-400' }}" />
                                    @endif
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-1">
                                                <p class="truncate text-xs font-medium text-gray-900 dark:text-white">
                                                    {{ $item['product_name'] }}
                                                </p>
                                                @if($isVariant)
                                                    <span class="text-[10px] text-blue-500">(custom)</span>
                                                @endif
                                                @if($isCompleted)
                                                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-1.5 py-0.5 text-[10px] font-medium text-emerald-700 dark:bg-emerald-500/30 dark:text-emerald-400">LUNAS</span>
                                                @endif
                                            </div>
                                            <p class="text-[11px] text-gray-500">
                                                @if($isDuration)
                                                    <span class="text-amber-600 dark:text-amber-400">⏱ {{ number_format($item['unit_price'], 0, ',', '.') }}/jam</span>
                                                @else
                                                    Rp {{ number_format($item['unit_price'], 0, ',', '.') }} × {{ $item['quantity'] }}
                                                @endif
                                            </p>
                                            {{-- Show modifiers badge --}}
                                            @if($hasModifiers || $hasNotes)
                                                <div class="mt-1 flex flex-wrap items-center gap-1">
                                                    @foreach($modifiers as $modifier)
                                                        <span class="inline-flex items-center rounded-full bg-blue-100 px-1.5 py-0.5 text-[10px] font-medium text-blue-700 dark:bg-blue-500/20 dark:text-blue-400">
                                                            {{ $modifier['name'] }}
                                                        </span>
                                                    @endforeach
                                                    @if($hasNotes)
                                                        <span class="inline-flex items-center rounded-full bg-amber-100 px-1.5 py-0.5 text-[10px] font-medium text-amber-700 dark:bg-amber-500/20 dark:text-amber-400">
                                                            📝 {{ Str::limit($item['notes'], 20) }}
                                                        </span>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                        @if(!$isCompleted)
                                            <button type="button" wire:click="openModifierModal({{ $baseProductId }})" class="shrink-0 rounded-lg p-1.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700">
                                                <x-heroicon-o-adjustments-horizontal class="h-4 w-4" />
                                            </button>
                                        @endif
                                    </div>
                                </div>
                                @if(!$isCompleted)
                                <div class="flex items-center gap-1">
                                    <button type="button" wire:click="decrementQuantity('{{ $productId }}')" class="btn-qty" aria-label="Kurangi jumlah {{ $item['product_name'] }}">
                                        <x-heroicon-o-minus class="h-3 w-3" />
                                    </button>
                                    <span class="w-6 text-center text-xs font-semibold text-gray-900 dark:text-white">{{ $item['quantity'] }}</span>
                                    <button type="button" wire:click="incrementQuantity('{{ $productId }}')" class="btn-qty" aria-label="Tambah jumlah {{ $item['product_name'] }}">
                                        <x-heroicon-o-plus class="h-3 w-3" />
                                    </button>
                                </div>
                                @endif
                            </div>
                        @empty
                            <div class="py-8 text-center">
                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800">
                                    <x-heroicon-o-shopping-cart class="h-6 w-6 text-gray-400" />
                                </div>
                                <p class="mt-2 text-xs text-gray-500">Keranjang kosong</p>
                            </div>
                        @endforelse
                    </div>
                    <div class="border-t border-gray-100/80 px-4 py-3 dark:border-gray-700/50">
                        {{-- Table Selection - Required for Duration Products --}}
                        @if(!$this->activeTableId && count($cart) > 0)
                            <div class="mb-3">
                                @if($this->cartHasDurationProducts)
                                    <div class="mb-2 flex items-center gap-2 rounded-lg bg-amber-50 p-2 text-xs text-amber-700 dark:bg-amber-500/10 dark:text-amber-400">
                                        <x-heroicon-o-information-circle class="h-4 w-4 shrink-0" />
                                        <span>Produk durasi wajib pilih meja</span>
                                    </div>
                                @endif
                                <select wire:model.live="tableId" class="block w-full cursor-pointer rounded-lg border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 transition-colors focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white {{ $this->cartHasDurationProducts && !$tableId && $tableId !== 'takeaway' ? 'border-amber-400' : '' }}">
                                    <option value="">Pilih Meja</option>
                                @if(!$this->cartHasDurationProducts)
                                    <option value="takeaway">Take Away / Bawa Pulang</option>
                                @endif
                                    @foreach($this->tables as $table)
                                        <option value="{{ $table->id }}">{{ $table->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-500">Subtotal (Sebelum PPN)</span>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">Rp {{ number_format($this->subtotalBeforeTax, 0, ',', '.') }}</span>
                            </div>
                            @if($this->showTax && $this->taxAmount > 0)
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-gray-500">PPN ({{ $this->taxRate }}%)</span>
                                    <span class="text-sm font-medium text-green-600 dark:text-green-400">+ Rp {{ number_format($this->taxAmount, 0, ',', '.') }}</span>
                                </div>
                            @endif
                            @if($this->paywuzFee > 0)
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-gray-500">
                                        Biaya Layanan
                                        @if($this->paywuzFeeByMerchant)
                                            <span class="text-green-600">(ditanggung toko)</span>
                                        @else
                                            <span class="text-orange-500">(ditanggung Anda)</span>
                                        @endif
                                    </span>
                                    <span class="text-sm font-medium {{ $this->paywuzFeeByMerchant ? 'text-green-600 dark:text-green-400' : 'text-orange-500' }}">
                                        {{ $this->paywuzFeeByMerchant ? '-' : '+' }} Rp {{ number_format($this->paywuzFee, 0, ',', '.') }}
                                    </span>
                                </div>
                            @endif
                            <div class="flex items-center justify-between border-t border-gray-100 pt-2 dark:border-gray-700">
                                <span class="text-sm font-semibold text-gray-900 dark:text-white">Total</span>
                                <span class="text-lg font-bold text-gray-900 dark:text-white">Rp {{ number_format($this->total, 0, ',', '.') }}</span>
                            </div>
                        </div>

                        <div class="mt-4">
                        @if($this->activeTableId)
                            {{-- Checkout Button for Active Table --}}
                            <button type="button" wire:click="openCheckout" class="btn btn-primary btn-lg w-full">
                                <x-heroicon-o-credit-card class="h-4 w-4" />
                                @if($this->tableId === 'takeaway')
                                    Bayar Sekarang
                                @elseif($this->cartHasDurationProducts)
                                    Checkout - {{ $this->selectedTable?->name }}
                                @else
                                    Bayar Sekarang
                                @endif
                            </button>
                        @elseif(count($cart) > 0 && $this->tableId)
                            {{-- Save to Table Button (Required for Duration Products) --}}
                            <button type="button" wire:click="saveToTable" class="btn btn-primary btn-lg w-full">
                                <x-heroicon-o-archive-box class="h-4 w-4" />
                                @if($this->tableId === 'takeaway')
                                    Bayar Sekarang
                                @elseif($this->cartHasDurationProducts)
                                    Simpan ke Meja
                                @else
                                    Simpan & Bayar
                                @endif
                            </button>
                        @elseif(count($cart) > 0)
                            {{-- Need Table Selection for Duration Products --}}
                            <button type="button" disabled class="btn btn-primary btn-lg w-full opacity-50">
                                <x-heroicon-o-information-circle class="h-4 w-4" />
                                @if($this->cartHasDurationProducts)
                                    Pilih Meja Dulu
                                @else
                                    Pilih Meja atau Bayar Sekarang
                                @endif
                            </button>
                        @else
                            {{-- Regular Checkout Button --}}
                            <button type="button" wire:click="openCheckout" class="btn btn-primary btn-lg w-full">
                                <x-heroicon-o-credit-card class="h-4 w-4" />
                                Bayar Sekarang
                            </button>
                        @endif
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>
    @if ($showCheckout)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4" wire:keydown.escape="closeCheckout">
            <div class="flex w-full max-w-lg flex-col overflow-hidden rounded-3xl bg-white shadow-xl dark:bg-gray-900 max-h-[92vh]">

                {{-- HEADER --}}
                <div class="flex shrink-0 items-center justify-between border-b border-gray-100 px-6 py-5 dark:border-gray-700 bg-gradient-to-r from-red-500 to-red-600">
                    <div class="flex items-center gap-3">
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-white/20 text-white">
                            <x-heroicon-o-credit-card class="h-5 w-5" />
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-white">Pembayaran</h2>
                            <p class="text-xs text-white/80">Selesaikan transaksi</p>
                        </div>
                    </div>
                    <button type="button" wire:click="closeCheckout" class="flex h-9 w-9 items-center justify-center rounded-lg text-white/80 transition hover:bg-white/20 hover:text-white" aria-label="Tutup checkout">
                        <x-heroicon-o-x-mark class="h-5 w-5" />
                    </button>
                </div>

                {{-- CONTENT --}}
                <div class="kasira-scrollbar min-h-0 flex-1 overflow-y-auto">
                    <div class="space-y-4 p-6">

                        {{-- TOTAL --}}
                        <div class="overflow-hidden rounded-2xl bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 p-6 text-white shadow-xl">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Total Pembayaran</p>
                                    <p class="mt-2 text-4xl font-black tracking-tight">Rp {{ number_format($this->total, 0, ',', '.') }}</p>
                                </div>
                                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/10 backdrop-blur">
                                    <x-heroicon-o-shopping-cart class="h-7 w-7 text-white" />
                                </div>
                            </div>
                        </div>

                        {{-- PAYMENT METHOD --}}
                        <div class="space-y-3">
                            <label class="block text-sm font-semibold text-gray-900 dark:text-white">Metode Pembayaran</label>
                            <div class="flex flex-wrap gap-2">
                                @if ($this->isPaymentCashEnabled)
                                    <button type="button" wire:click="$set('paymentMethod', 'cash')" class="group relative flex min-w-[100px] flex-1 flex-col items-center gap-2 rounded-2xl border-2 p-3 transition-all duration-200 {{ $paymentMethod === 'cash' ? 'border-green-500 bg-green-50 shadow-lg shadow-green-100 dark:bg-green-500/10' : 'border-gray-200 bg-white hover:border-green-300 hover:shadow dark:border-gray-700 dark:bg-gray-800 dark:hover:border-green-400' }}">
                                        <div class="flex h-11 w-11 items-center justify-center rounded-xl transition-all duration-200 {{ $paymentMethod === 'cash' ? 'bg-green-500 text-white shadow-lg' : 'bg-gray-100 text-gray-600 group-hover:bg-green-100 dark:bg-gray-700 dark:text-gray-300 dark:group-hover:text-green-400' }}">
                                            <x-heroicon-o-banknotes class="h-5 w-5" />
                                        </div>
                                        <span class="text-xs font-bold {{ $paymentMethod === 'cash' ? 'text-green-600 dark:text-green-400' : 'text-gray-700 dark:text-gray-300' }}">Tunai</span>
                                        @if ($paymentMethod === 'cash')
                                            <div class="absolute -right-1 -top-1 flex h-5 w-5 items-center justify-center rounded-full bg-green-500">
                                                <x-heroicon-o-check class="h-3 w-3 text-white" />
                                            </div>
                                        @endif
                                    </button>
                                @endif
                                @if ($this->isPaymentQrisAutoEnabled)
                                    <button type="button" wire:click="$set('paymentMethod', 'qris')" class="group relative flex min-w-[100px] flex-1 flex-col items-center gap-2 rounded-2xl border-2 p-3 transition-all duration-200 {{ $paymentMethod === 'qris' ? 'border-green-500 bg-green-50 shadow-lg shadow-green-100 dark:bg-green-500/10' : 'border-gray-200 bg-white hover:border-green-300 hover:shadow dark:border-gray-700 dark:bg-gray-800 dark:hover:border-green-400' }}">
                                        <div class="flex h-11 w-11 items-center justify-center rounded-xl transition-all duration-200 {{ $paymentMethod === 'qris' ? 'bg-green-500 text-white shadow-lg' : 'bg-gray-100 text-gray-600 group-hover:bg-green-100 dark:bg-gray-700 dark:text-gray-300 dark:group-hover:text-green-400' }}">
                                            <x-heroicon-o-qr-code class="h-5 w-5" />
                                        </div>
                                        <span class="text-xs font-bold {{ $paymentMethod === 'qris' ? 'text-green-600 dark:text-green-400' : 'text-gray-700 dark:text-gray-300' }}">QRIS</span>
                                        @if ($paymentMethod === 'qris')
                                            <div class="absolute -right-1 -top-1 flex h-5 w-5 items-center justify-center rounded-full bg-green-500">
                                                <x-heroicon-o-check class="h-3 w-3 text-white" />
                                            </div>
                                        @endif
                                    </button>
                                @endif
                                @if ($this->isPaymentVaEnabled)
                                    <button type="button" wire:click="$set('paymentMethod', 'va')" class="group relative flex min-w-[100px] flex-1 flex-col items-center gap-2 rounded-2xl border-2 p-3 transition-all duration-200 {{ $paymentMethod === 'va' ? 'border-green-500 bg-green-50 shadow-lg shadow-green-100 dark:bg-green-500/10' : 'border-gray-200 bg-white hover:border-green-300 hover:shadow dark:border-gray-700 dark:bg-gray-800 dark:hover:border-green-400' }}">
                                        <div class="flex h-11 w-11 items-center justify-center rounded-xl transition-all duration-200 {{ $paymentMethod === 'va' ? 'bg-green-500 text-white shadow-lg' : 'bg-gray-100 text-gray-600 group-hover:bg-green-100 dark:bg-gray-700 dark:text-gray-300 dark:group-hover:text-green-400' }}">
                                            <x-heroicon-o-building-office class="h-5 w-5" />
                                        </div>
                                        <span class="text-xs font-bold {{ $paymentMethod === 'va' ? 'text-green-600 dark:text-green-400' : 'text-gray-700 dark:text-gray-300' }}">VA</span>
                                        @if ($paymentMethod === 'va')
                                            <div class="absolute -right-1 -top-1 flex h-5 w-5 items-center justify-center rounded-full bg-green-500">
                                                <x-heroicon-o-check class="h-3 w-3 text-white" />
                                            </div>
                                        @endif
                                    </button>
                                @endif
                                @if ($this->isPaymentTransferEnabled)
                                    <button type="button" wire:click="$set('paymentMethod', 'transfer')" class="group relative flex min-w-[100px] flex-1 flex-col items-center gap-2 rounded-2xl border-2 p-3 transition-all duration-200 {{ $paymentMethod === 'transfer' ? 'border-green-500 bg-green-50 shadow-lg shadow-green-100 dark:bg-green-500/10' : 'border-gray-200 bg-white hover:border-green-300 hover:shadow dark:border-gray-700 dark:bg-gray-800 dark:hover:border-green-400' }}">
                                        <div class="flex h-11 w-11 items-center justify-center rounded-xl transition-all duration-200 {{ $paymentMethod === 'transfer' ? 'bg-green-500 text-white shadow-lg' : 'bg-gray-100 text-gray-600 group-hover:bg-green-100 dark:bg-gray-700 dark:text-gray-300 dark:group-hover:text-green-400' }}">
                                            <x-heroicon-o-building-library class="h-5 w-5" />
                                        </div>
                                        <span class="text-xs font-bold {{ $paymentMethod === 'transfer' ? 'text-green-600 dark:text-green-400' : 'text-gray-700 dark:text-gray-300' }}">Transfer</span>
                                        @if ($paymentMethod === 'transfer')
                                            <div class="absolute -right-1 -top-1 flex h-5 w-5 items-center justify-center rounded-full bg-green-500">
                                                <x-heroicon-o-check class="h-3 w-3 text-white" />
                                            </div>
                                        @endif
                                    </button>
                                @endif
                                @if ($this->isPaymentQrisManualEnabled)
                                    <button type="button" wire:click="$set('paymentMethod', 'qris_manual')" class="group relative flex min-w-[100px] flex-1 flex-col items-center gap-2 rounded-2xl border-2 p-3 transition-all duration-200 {{ $paymentMethod === 'qris_manual' ? 'border-green-500 bg-green-50 shadow-lg shadow-green-100 dark:bg-green-500/10' : 'border-gray-200 bg-white hover:border-green-300 hover:shadow dark:border-gray-700 dark:bg-gray-800 dark:hover:border-green-400' }}">
                                        <div class="flex h-11 w-11 items-center justify-center rounded-xl transition-all duration-200 {{ $paymentMethod === 'qris_manual' ? 'bg-green-500 text-white shadow-lg' : 'bg-gray-100 text-gray-600 group-hover:bg-green-100 dark:bg-gray-700 dark:text-gray-300 dark:group-hover:text-green-400' }}">
                                            <x-heroicon-o-qr-code class="h-5 w-5" />
                                        </div>
                                        <span class="text-xs font-bold {{ $paymentMethod === 'qris_manual' ? 'text-green-600 dark:text-green-400' : 'text-gray-700 dark:text-gray-300' }}">QR Manual</span>
                                        @if ($paymentMethod === 'qris_manual')
                                            <div class="absolute -right-1 -top-1 flex h-5 w-5 items-center justify-center rounded-full bg-green-500">
                                                <x-heroicon-o-check class="h-3 w-3 text-white" />
                                            </div>
                                        @endif
                                    </button>
                                @endif
                            </div>
                        </div>

                        {{-- CASH INPUT --}}
                        @if ($paymentMethod === 'cash')
                            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                                <div class="mb-4">
                                    <label class="mb-2 block text-sm font-semibold text-gray-900 dark:text-white">Uang Diterima</label>
                                    <x-filament::input.wrapper class="text-lg">
                                        <x-filament::input type="number" wire:model.live="paidAmount" min="0" step="1000" placeholder="0" class="text-2xl font-bold" />
                                    </x-filament::input.wrapper>
                                </div>
                                <div class="mb-4 grid grid-cols-4 gap-2">
                                    @foreach ([20000, 50000, 100000, 200000] as $amount)
                                        <button type="button" wire:click="$set('paidAmount', {{ $amount }})" class="rounded-xl border-2 border-gray-200 bg-gray-50 py-3 text-center text-sm font-bold text-gray-700 transition hover:border-green-500 hover:bg-green-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:border-green-500">
                                            Rp {{ number_format($amount/1000, 0, '', '.') }}K
                                        </button>
                                    @endforeach
                                </div>
                                @if ((float) $paidAmount > 0)
                                    <div class="flex items-center justify-between rounded-xl border-2 border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-900/30">
                                        <div>
                                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Kembalian</p>
                                            <p class="text-2xl font-black text-green-600 dark:text-green-400">Rp {{ number_format(max(0, (float) $this->changeAmount), 0, ',', '.') }}</p>
                                        </div>
                                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-green-100 dark:bg-green-800">
                                            <x-heroicon-o-banknotes class="h-6 w-6 text-green-600 dark:text-green-400" />
                                        </div>
                                    </div>
                                @else
                                    <div class="flex items-center justify-between rounded-xl border-2 border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900/30">
                                        <div>
                                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Kurang</p>
                                            <p class="text-2xl font-black text-amber-600 dark:text-amber-400">Rp {{ number_format($this->remainingPayment, 0, ',', '.') }}</p>
                                        </div>
                                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-800">
                                            <x-heroicon-o-exclamation-triangle class="h-6 w-6 text-amber-600 dark:text-amber-400" />
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @elseif ($paymentMethod === 'qris')
                            {{-- QRIS --}}
                            @if (!$qrisTransactionId)
                                <button type="button" wire:click="generateQrisPayment" wire:loading.attr="disabled" class="w-full rounded-2xl border-2 border-dashed border-green-500 bg-green-50 p-8 transition hover:bg-green-100 dark:border-green-400 dark:bg-green-900/20">
                                    <div class="flex flex-col items-center justify-center gap-4 text-center">
                                        @if ($isGeneratingQr)
                                            <div class="h-16 w-16 rounded-full border-4 border-green-200 border-t-green-500 animate-spin"></div>
                                            <span class="text-sm font-medium text-green-600 dark:text-green-400">Membuat QR Code...</span>
                                        @else
                                            <div class="flex h-20 w-20 items-center justify-center rounded-2xl bg-green-500 text-white shadow-lg shadow-green-200">
                                                <x-heroicon-o-qr-code class="h-10 w-10" />
                                            </div>
                                            <div>
                                                <p class="text-xl font-bold text-green-700 dark:text-green-400">Generate QRIS</p>
                                                <p class="mt-1 text-sm text-green-600 dark:text-green-500">Klik untuk buat QR Code pembayaran</p>
                                            </div>
                                        @endif
                                    </div>
                                </button>
                            @else
                                <div class="space-y-4">
                                    {{-- QR Display Card --}}
                                    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800">
                                        <div class="bg-gradient-to-r from-green-500 to-green-600 p-4 text-center">
                                            <p class="text-sm font-semibold uppercase tracking-wider text-white/80">Total Bayar</p>
                                            <p class="text-3xl font-black text-white">Rp {{ number_format($currentQrisSale?->grand_total ?? $this->total, 0, ',', '.') }}</p>
                                        </div>
                                        <div class="flex justify-center bg-white p-6 dark:bg-gray-900">
                                            @if ($this->qrisCodeUrl)
                                                <img src="{{ $this->qrisCodeUrl }}" alt="QRIS" class="h-64 w-64 rounded-xl object-contain">
                                            @elseif ($currentQrisSale?->paywuz_qr_url)
                                                <img src="{{ $currentQrisSale->paywuz_qr_url }}" alt="QRIS" class="h-64 w-64 rounded-xl object-contain">
                                            @endif
                                        </div>
                                        <div class="border-t border-gray-100 p-4 text-center dark:border-gray-700">
                                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Tunjukkan QR ke customer untuk discan</p>
                                        </div>
                                    </div>
                                    @if ($currentQrisSale?->paywuz_transaction_id)
                                        <div class="rounded-xl bg-blue-50 px-4 py-3 dark:bg-blue-900/30">
                                            <p class="text-center text-xs font-medium text-blue-600 dark:text-blue-400">ID Transaksi: {{ $currentQrisSale->paywuz_transaction_id }}</p>
                                        </div>
                                    @endif
                                    <div class="flex gap-3">
                                        <button type="button" wire:click="checkQrisPaymentStatus" wire:loading.attr="disabled" class="flex-1 flex items-center justify-center gap-2 rounded-xl bg-green-500 py-4 text-base font-bold text-white shadow-lg shadow-green-200 transition hover:bg-green-600 disabled:opacity-50">
                                            <span wire:loading.remove wire:target="checkQrisPaymentStatus" class="flex items-center justify-center gap-2">
                                                <x-heroicon-o-check-circle class="h-5 w-5" /> Cek Pembayaran
                                            </span>
                                            <span wire:loading wire:target="checkQrisPaymentStatus" class="flex items-center justify-center gap-2">
                                                <div class="h-5 w-5 rounded-full border-2 border-white/70 border-t-white animate-spin"></div>
                                                <span>Mengecek...</span>
                                            </span>
                                        </button>
                                        <button type="button" wire:click="cancelQrisPayment" class="flex items-center justify-center rounded-xl bg-gray-100 px-5 py-4 text-gray-600 transition hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-400 dark:hover:bg-gray-600" aria-label="Batalkan QRIS">
                                            <x-heroicon-o-x-mark class="h-5 w-5" />
                                        </button>
                                    </div>
                                </div>
                            @endif
                        @elseif ($paymentMethod === 'va')
                            {{-- VA --}}
                            @if (!$vaTransactionId)
                                <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                                    <div class="mb-4 text-center">
                                        <p class="text-lg font-bold text-gray-900 dark:text-white">Pilih Bank</p>
                                        <p class="text-sm text-gray-500">Virtual Account</p>
                                    </div>
                                    <div class="grid grid-cols-3 gap-3 sm:grid-cols-5">
                                        @foreach ($this->availableBanks as $bank)
                                            @php
                                                $code = strtolower($bank['code'] ?? '');
                                                $name = strtolower($bank['name'] ?? '');
                                                if (empty($code) && empty($name)) {
                                                    $bgColor = 'bg-gray-500';
                                                } elseif ($code === 'bca' || str_contains($name, 'bca')) {
                                                    $bgColor = 'bg-blue-500';
                                                } elseif ($code === 'bni' || str_contains($name, 'bni')) {
                                                    $bgColor = 'bg-orange-500';
                                                } elseif ($code === 'bri' || str_contains($name, 'bri')) {
                                                    $bgColor = 'bg-blue-700';
                                                } elseif ($code === 'mandiri' || str_contains($name, 'mandiri')) {
                                                    $bgColor = 'bg-yellow-600';
                                                } elseif ($code === 'permata' || str_contains($name, 'permata')) {
                                                    $bgColor = 'bg-purple-500';
                                                } else {
                                                    $bgColor = 'bg-gray-500';
                                                }
                                            @endphp
                                            <button type="button" wire:click="generateVaPayment('{{ $bank['code'] ?? $code }}')" wire:loading.attr="disabled" class="flex flex-col items-center gap-2 rounded-xl border-2 border-gray-100 bg-white p-4 transition hover:border-blue-300 hover:shadow-lg dark:border-gray-700 dark:bg-gray-800 disabled:opacity-50">
                                                <div class="flex h-14 w-14 items-center justify-center rounded-xl {{ $bgColor }} font-bold text-white shadow-lg">
                                                    {{ strtoupper(substr($bank['name'], 0, 4)) }}
                                                </div>
                                                <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">{{ $bank['name'] }}</span>
                                                <div wire:loading wire:target="generateVaPayment('{{ $bank['code'] ?? $code }}')" class="flex items-center justify-center">
                                                    <div class="h-5 w-5 rounded-full border-2 border-gray-400 border-t-transparent animate-spin"></div>
                                                </div>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @else
                                @php
                                    $bankCode = strtolower($vaBankCode ?? '');
                                    if (str_contains($bankCode, 'bca')) {
                                        $bgColor = 'bg-gradient-to-r from-blue-500 to-blue-600';
                                    } elseif (str_contains($bankCode, 'bni')) {
                                        $bgColor = 'bg-gradient-to-r from-orange-500 to-orange-600';
                                    } elseif (str_contains($bankCode, 'bri')) {
                                        $bgColor = 'bg-gradient-to-r from-blue-700 to-blue-800';
                                    } elseif (str_contains($bankCode, 'mandiri')) {
                                        $bgColor = 'bg-gradient-to-r from-yellow-500 to-yellow-600';
                                    } elseif (str_contains($bankCode, 'permata')) {
                                        $bgColor = 'bg-gradient-to-r from-purple-500 to-purple-600';
                                    } else {
                                        $bgColor = 'bg-gradient-to-r from-blue-500 to-blue-600';
                                    }
                                @endphp
                                <div class="space-y-4">
                                    {{-- Total Bayar --}}
                                    <div class="overflow-hidden rounded-2xl bg-white shadow-lg dark:bg-gray-800">
                                        <div class="bg-gradient-to-r from-gray-800 to-gray-900 p-5 text-center">
                                            <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Total Bayar</p>
                                            <p class="mt-1 text-3xl font-black text-white">Rp {{ number_format($currentVaSale?->grand_total ?? $this->total, 0, ',', '.') }}</p>
                                        </div>
                                        <div class="{{ $bgColor }} px-5 py-4 text-center">
                                            <p class="text-sm font-semibold text-white/90">{{ $vaBankName ?? 'Virtual Account' }}</p>
                                        </div>
                                        <div class="p-5 text-center">
                                            @if($vaAccountNumber)
                                                <p class="mb-3 text-xs font-medium uppercase tracking-wider text-gray-500">Nomor Virtual Account</p>
                                                <div class="flex items-center justify-center gap-3">
                                                    <span class="font-mono text-xl font-bold text-gray-900 dark:text-white">{{ $vaAccountNumber }}</span>
                                                    <button type="button" x-on:click="navigator.clipboard.writeText('{{ $vaAccountNumber }}')" class="flex h-10 w-10 items-center justify-center rounded-xl bg-gray-100 text-gray-600 transition hover:bg-green-100 hover:text-green-600 dark:bg-gray-700 dark:text-gray-400 dark:hover:bg-green-900/30 dark:hover:text-green-400">
                                                        <x-heroicon-o-document-duplicate class="h-5 w-5" />
                                                    </button>
                                                </div>
                                            @elseif($vaPaymentUrl)
                                                <p class="mb-3 text-xs font-medium uppercase tracking-wider text-amber-600">Customer belum pilih bank</p>
                                                <div class="flex flex-col items-center gap-3">
                                                    <a href="{{ $vaPaymentUrl }}" target="_blank" class="inline-flex items-center gap-2 rounded-xl bg-blue-500 px-6 py-3 text-sm font-bold text-white shadow-lg transition hover:bg-blue-600">
                                                        Buka Link Pembayaran
                                                    </a>
                                                    <button type="button" wire:click="checkVaPaymentStatus" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400">
                                                        Cek setelah customer pilih bank
                                                    </button>
                                                </div>
                                            @else
                                                <p class="text-gray-400">Memuat...</p>
                                            @endif
                                        </div>
                                        @if ($vaExpiryTime)
                                            <div class="flex items-center justify-center gap-2 border-t border-gray-100 bg-amber-50 px-5 py-3 dark:border-gray-700 dark:bg-amber-900/20">
                                                <x-heroicon-o-clock class="h-4 w-4 text-amber-600 dark:text-amber-400" />
                                                <span class="text-xs font-medium text-amber-700 dark:text-amber-400">Berlaku hingga: {{ $vaExpiryTime }}</span>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="flex gap-3">
                                        <button type="button" wire:click="checkVaPaymentStatus" wire:loading.attr="disabled" class="flex-1 flex items-center justify-center gap-2 rounded-xl bg-green-500 py-4 text-base font-bold text-white shadow-lg shadow-green-200 transition hover:bg-green-600 disabled:opacity-50">
                                            <span wire:loading.remove wire:target="checkVaPaymentStatus" class="flex items-center justify-center gap-2">
                                                <x-heroicon-o-check-circle class="h-5 w-5" /> Cek Pembayaran
                                            </span>
                                            <span wire:loading wire:target="checkVaPaymentStatus" class="flex items-center justify-center gap-2">
                                                <div class="h-5 w-5 rounded-full border-2 border-white/70 border-t-white animate-spin"></div>
                                                <span>Mengecek...</span>
                                            </span>
                                        </button>
                                        <button type="button" wire:click="cancelVaPayment" class="flex items-center justify-center rounded-xl bg-gray-100 px-5 py-4 text-gray-600 transition hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-400 dark:hover:bg-gray-600" aria-label="Batalkan Virtual Account">
                                            <x-heroicon-o-x-mark class="h-5 w-5" />
                                        </button>
                                    </div>
                                </div>
                            @endif
                        @elseif ($paymentMethod === 'transfer')
                            {{-- TRANSFER MANUAL --}}
                            <div class="space-y-4">
                                @if (!$this->storeBankAccount)
                                    <div class="flex items-center gap-3 rounded-xl border-2 border-red-200 bg-red-50 p-4 dark:border-red-700 dark:bg-red-900/20">
                                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-red-100 dark:bg-red-900/50">
                                            <x-heroicon-o-exclamation-triangle class="h-5 w-5 text-red-600 dark:text-red-400" />
                                        </div>
                                        <div>
                                            <p class="font-semibold text-red-800 dark:text-red-300">Belum ada data rekening</p>
                                            <p class="text-sm text-red-600 dark:text-red-400">Hubungi admin untuk setting rekening</p>
                                        </div>
                                    </div>
                                @else
                                    {{-- Rekening Info Card --}}
                                    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800">
                                        <div class="bg-gradient-to-r from-blue-600 to-blue-700 p-5 text-center">
                                            <p class="text-lg font-bold text-white">Transfer Manual</p>
                                            <p class="mt-1 text-3xl font-black text-white">Rp {{ number_format($this->total, 0, ',', '.') }}</p>
                                        </div>
                                        <div class="divide-y divide-gray-100 p-5 dark:divide-gray-700">
                                            <div class="flex items-center justify-between py-3">
                                                <div class="flex items-center gap-3">
                                                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-100 dark:bg-blue-900/50">
                                                        <x-heroicon-o-building-library class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                                                    </div>
                                                    <div>
                                                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Bank</p>
                                                        <p class="font-bold text-gray-900 dark:text-white">{{ $this->storeBankName ?? '-' }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="flex items-center justify-between py-3">
                                                <div>
                                                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Nomor Rekening</p>
                                                    <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white">{{ $this->storeBankAccount }}</p>
                                                </div>
                                                <button type="button" x-on:click="navigator.clipboard.writeText('{{ $this->storeBankAccount }}')" class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-100 text-blue-600 transition hover:bg-blue-200 dark:bg-blue-900/50 dark:text-blue-400 dark:hover:bg-blue-900/70">
                                                    <x-heroicon-o-document-duplicate class="h-5 w-5" />
                                                </button>
                                            </div>
                                            <div class="flex items-center justify-between py-3">
                                                <div>
                                                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Atas Nama</p>
                                                    <p class="font-bold text-gray-900 dark:text-white">{{ $this->storeBankAccountName ?? '-' }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Instruksi --}}
                                    <div class="flex items-start gap-3 rounded-xl border-2 border-amber-200 bg-amber-50 p-4 dark:border-amber-700 dark:bg-amber-900/20">
                                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-500 text-white">
                                            <x-heroicon-o-information-circle class="h-5 w-5" />
                                        </div>
                                        <div>
                                            <p class="font-semibold text-amber-800 dark:text-amber-300">Instruksi Pembayaran</p>
                                            <ol class="mt-1 space-y-1 text-sm text-amber-700 dark:text-amber-400">
                                                <li>1. Berikan nomor rekening ke customer</li>
                                                <li>2. Customer transfer ke rekening tersebut</li>
                                                <li>3. Klik "Konfirmasi Bayar" jika sudah</li>
                                            </ol>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @elseif ($paymentMethod === 'qris_manual')
                            {{-- QRIS MANUAL --}}
                            <div class="space-y-4">
                                @if (!$this->storeBankQrImage)
                                    <div class="flex items-center gap-3 rounded-xl border-2 border-red-200 bg-red-50 p-4 dark:border-red-700 dark:bg-red-900/20">
                                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-red-100 dark:bg-red-900/50">
                                            <x-heroicon-o-exclamation-triangle class="h-5 w-5 text-red-600 dark:text-red-400" />
                                        </div>
                                        <div>
                                            <p class="font-semibold text-red-800 dark:text-red-300">QR Code belum diupload</p>
                                            <p class="text-sm text-red-600 dark:text-red-400">Upload QR di Settings → Rekening Bank</p>
                                        </div>
                                    </div>
                                @else
                                    {{-- QR Display Card - Full Width --}}
                                    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800">
                                        <div class="bg-gradient-to-r from-purple-600 to-pink-600 p-5 text-center">
                                            <p class="text-lg font-bold text-white">QRIS Manual</p>
                                            <p class="mt-1 text-3xl font-black text-white">Rp {{ number_format($this->total, 0, ',', '.') }}</p>
                                        </div>
                                        <div class="flex justify-center bg-white p-6 dark:bg-gray-900">
                                            <img src="{{ Storage::disk('public')->url($this->storeBankQrImage) }}" alt="QRIS Manual" class="h-96 w-auto rounded-2xl object-contain shadow-xl" />
                                        </div>
                                        <div class="border-t border-gray-100 p-4 text-center dark:border-gray-700">
                                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Tunjukkan QR ke customer untuk discan</p>
                                        </div>
                                    </div>

                                    {{-- Instruksi --}}
                                    <div class="flex items-start gap-3 rounded-xl border-2 border-amber-200 bg-amber-50 p-4 dark:border-amber-700 dark:bg-amber-900/20">
                                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-500 text-white">
                                            <x-heroicon-o-information-circle class="h-5 w-5" />
                                        </div>
                                        <div>
                                            <p class="font-semibold text-amber-800 dark:text-amber-300">Instruksi Pembayaran</p>
                                            <ol class="mt-1 space-y-1 text-sm text-amber-700 dark:text-amber-400">
                                                <li>1. Tunjukkan QR ke customer</li>
                                                <li>2. Customer scan dengan aplikasi bank/e-wallet</li>
                                                <li>3. Klik "Konfirmasi Bayar" jika sudah</li>
                                            </ol>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @else
                            <div class="flex items-center gap-3 rounded-xl bg-amber-50 p-4 dark:bg-amber-500/20">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-500 text-white">
                                    <x-heroicon-o-information-circle class="h-5 w-5" />
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-amber-800 dark:text-amber-300">Pembayaran {{ strtoupper($paymentMethod) }}</p>
                                    <p class="text-xs text-amber-700 dark:text-amber-400">Pastikan sudah diterima</p>
                                </div>
                            </div>
                        @endif

                        {{-- CUSTOMER SELECT --}}
                        <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                            <label class="mb-2 block text-sm font-semibold text-gray-900 dark:text-white">Customer</label>
                            <x-filament::input.wrapper>
                                <x-filament::input.select wire:model="customerId">
                                    <option value="">👤 Walk-in Customer</option>
                                    @foreach ($this->customers as $customer)
                                        <option value="{{ $customer->id }}">{{ $customer->name }}@if ($customer->is_member) ⭐@endif</option>
                                    @endforeach
                                </x-filament::input.select>
                            </x-filament::input.wrapper>
                        </div>

                        {{-- MEMBER INFO --}}
                        @if ($customerId)
                            @php $selectedCustomer = $this->customers->firstWhere('id', $customerId); @endphp
                            @if ($selectedCustomer && $selectedCustomer->is_member)
                                <div class="flex items-center gap-3 rounded-xl border-2 border-amber-300 bg-gradient-to-r from-amber-50 to-orange-50 p-4 dark:border-amber-500 dark:from-amber-900/20 dark:to-orange-900/20">
                                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-amber-400 to-orange-500 text-white shadow-lg shadow-amber-200">
                                        <x-heroicon-s-star class="h-6 w-6" />
                                    </div>
                                    <div class="flex-1">
                                        <p class="font-semibold text-gray-900 dark:text-white">{{ $selectedCustomer->name }}</p>
                                        <p class="text-sm font-medium text-amber-600 dark:text-amber-400">{{ number_format((int) $selectedCustomer->points, 0, ',', '.') }} Points Available</p>
                                    </div>
                                </div>
                            @endif
                        @endif

                        {{-- NOTES --}}
                        <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                            <label class="mb-2 block text-sm font-semibold text-gray-900 dark:text-white">Catatan Transaksi</label>
                            <x-filament::input.wrapper>
                                <textarea wire:model="paymentNotes" rows="2" placeholder="Tambahkan catatan jika diperlukan..." class="fi-input block w-full rounded-lg border-0 bg-transparent px-3 py-2 text-sm text-gray-900 placeholder-gray-400 outline-none transition duration-75 dark:text-white dark:placeholder-gray-500 focus:ring-0 disabled:opacity-70"></textarea>
                            </x-filament::input.wrapper>
                        </div>

                        {{-- SUMMARY --}}
                        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                            <div class="bg-gradient-to-r from-gray-800 to-gray-900 px-4 py-3">
                                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Ringkasan</p>
                            </div>
                            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                                <div class="flex items-center justify-between px-4 py-3">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Subtotal</span>
                                    <span class="font-semibold text-gray-900 dark:text-white">Rp {{ number_format($this->subtotalBeforeTax, 0, ',', '.') }}</span>
                                </div>
                                @if($this->showTax && $this->taxAmount > 0)
                                    <div class="flex items-center justify-between px-4 py-3">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">PPN ({{ $this->taxRate }}%)</span>
                                        <span class="font-semibold text-green-600 dark:text-green-400">Rp {{ number_format($this->taxAmount, 0, ',', '.') }}</span>
                                    </div>
                                @endif
                                <div class="flex items-center justify-between bg-gray-50 px-4 py-4 dark:bg-gray-900/50">
                                    <span class="font-semibold text-gray-900 dark:text-white">Total</span>
                                    <span class="text-xl font-black text-gray-900 dark:text-white">Rp {{ number_format($this->total, 0, ',', '.') }}</span>
                                </div>
                            </div>
                        </div>

                        @if ($paymentMethod === 'cash' && (float) $paidAmount > 0 && $this->remainingPayment > 0)
                            <div class="flex items-center gap-3 rounded-xl border-2 border-red-300 bg-red-50 p-4 dark:border-red-500 dark:bg-red-500/20">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-red-100 dark:bg-red-900/50">
                                    <x-heroicon-o-exclamation-triangle class="h-5 w-5 text-red-600 dark:text-red-400" />
                                </div>
                                <div>
                                    <p class="font-semibold text-red-700 dark:text-red-300">Pembayaran Kurang</p>
                                    <p class="text-sm text-red-600 dark:text-red-400">Rp {{ number_format($this->remainingPayment, 0, ',', '.') }}</p>
                                </div>
                            </div>
                        @endif

                    </div>
                </div>

                {{-- FOOTER --}}
                <div class="shrink-0 border-t border-gray-100 bg-white p-5 dark:border-gray-700 dark:bg-gray-900">
                    @php
                        $isQrVa = in_array($paymentMethod, ['qris', 'va']);
                        $isManualConfirm = in_array($paymentMethod, ['transfer', 'qris_manual']);
                        $canPay = ($this->total > 0) && (
                            ($paymentMethod === 'cash' && (float) $paidAmount >= (float) $this->total) ||
                            $isManualConfirm
                        );
                    @endphp
                    <div class="flex gap-3">
                        <button type="button" wire:click="closeCheckout" class="btn btn-secondary btn-lg flex-1">
                            Batal
                        </button>
                        <button type="button" wire:click="processPayment" wire:loading.attr="disabled" @disabled(!$canPay) class="btn btn-lg flex-1 {{ $canPay ? 'btn-primary' : 'bg-gray-300 border border-gray-300 text-gray-500 cursor-not-allowed' }}">
                            <span wire:loading.remove wire:target="processPayment" class="flex items-center justify-center gap-2">
                                @if ($isQrVa)
                                    <x-heroicon-o-lock-closed class="h-5 w-5" />
                                    <span>Cek Status Dulu</span>
                                @elseif ($isManualConfirm)
                                    <x-heroicon-o-check-circle class="h-5 w-5" />
                                    <span>Konfirmasi Bayar</span>
                                @else
                                    <x-heroicon-o-check class="h-5 w-5" />
                                    <span>Bayar Rp {{ number_format($this->total, 0, ',', '.') }}</span>
                                @endif
                            </span>
                            <span wire:loading wire:target="processPayment" class="flex items-center justify-center gap-2">
                                <div class="h-4 w-4 rounded-full border-2 border-white/50 border-t-white animate-spin" style="border-top-color: white;"></div>
                                Memproses...
                            </span>
                        </button>
                    </div>
                </div>

            </div>
        </div>
    @endif
    {{-- SUCCESS POPUP --}}
    <div x-data="{
        show: false, countdown: 5, invoice: '-', total: 0, change: 0, pointsEarned: 0, customerPoints: 0, receiptUrl: null, timer: null,
        openPopup(data) {
            this.invoice = data.invoice ?? data.invoice_number ?? data.uuid ?? '-';
            this.total = Number(data.total ?? data.grand_total ?? 0);
            this.change = Number(data.change ?? data.change_amount ?? 0);
            this.pointsEarned = Number(data.pointsEarned ?? data.points_earned ?? 0);
            this.customerPoints = Number(data.customerPoints ?? data.customer_points ?? 0);
            this.receiptUrl = data.receiptUrl ?? data.receipt_url ?? data.url ?? null;
            this.countdown = 5; this.show = true; this.startCountdown();
        },
        startCountdown() {
            if (this.timer) clearInterval(this.timer);
            this.timer = setInterval(() => { this.countdown > 1 ? this.countdown-- : (clearInterval(this.timer), this.timer = null, this.printReceipt()); }, 1000);
        },
        printReceipt() {
            if (this.timer) clearInterval(this.timer), this.timer = null;
            if (!this.receiptUrl) { this.show = false; return; }
            window.open(this.receiptUrl, '_blank', 'width=420,height=700,scrollbars=yes,resizable=yes');
            this.show = false;
        },
        skip() { if (this.timer) clearInterval(this.timer), this.timer = null; this.show = false; }
    }" x-on:payment-success.window="openPopup($event.detail)">
        <div x-show="show" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4">
            <div x-show="show" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="scale-95 opacity-0" x-transition:enter-end="scale-100 opacity-100" class="w-full max-w-sm overflow-hidden rounded-3xl bg-white shadow-xl dark:bg-gray-900">

                <div class="relative overflow-hidden px-6 pb-7 pt-8 text-center gradient-bg-soft">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full text-white shadow-lg gradient-bg">
                        <x-heroicon-o-check class="h-8 w-8" />
                    </div>
                    <h2 class="mt-4 text-xl font-bold text-gray-900 dark:text-white">Berhasil!</h2>
                    <p class="mt-1 text-sm text-gray-500">Transaksi tersimpan</p>
                </div>

                <div class="mx-5 mt-5 overflow-hidden rounded-2xl border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3 dark:border-gray-700">
                        <span class="text-xs text-gray-500">Invoice</span>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white" x-text="invoice"></span>
                    </div>
                    <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3 dark:border-gray-700">
                        <span class="text-xs text-gray-500">Total</span>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white" x-text="'Rp ' + new Intl.NumberFormat('id-ID').format(total)"></span>
                    </div>
                    <div class="flex items-center justify-between px-4 py-3">
                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Kembalian</span>
                        <span class="text-base font-bold text-green-600" x-text="'Rp ' + new Intl.NumberFormat('id-ID').format(change)"></span>
                    </div>
                </div>

                <div x-show="pointsEarned > 0" x-cloak class="mx-5 mt-3 flex items-center gap-3 rounded-xl border-2 border-amber-300 bg-amber-50 p-3 dark:border-amber-500 dark:bg-amber-500/20">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg gradient-bg">
                        <x-heroicon-s-star class="h-4 w-4" />
                    </div>
                    <div class="flex-1">
                        <p class="text-xs font-semibold text-gray-900 dark:text-white">+<span x-text="new Intl.NumberFormat('id-ID').format(pointsEarned)"></span> Points</p>
                        <p class="text-[10px] text-amber-600 dark:text-amber-400">Telah ditambahkan</p>
                    </div>
                </div>

                <div class="px-5 pt-5 text-center">
                    <p class="text-xs text-gray-500">Struk terbuka dalam <span x-text="countdown" class="font-bold text-gray-900 dark:text-white"></span> detik</p>
                </div>

                <div class="flex gap-3 p-5">
                    <button type="button" @click="skip()" class="flex-1 rounded-xl border border-gray-200 bg-white py-3 text-sm font-semibold text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">Lewati</button>
                    <button type="button" @click="printReceipt()" class="flex flex-1 items-center justify-center gap-2 rounded-xl py-3 text-sm font-semibold text-white transition-transform hover:-translate-y-0.5 gradient-bg-shadow">
                        <x-heroicon-o-printer class="h-4 w-4" />
                        Cetak
                    </button>
                </div>

            </div>
        </div>
    </div>

    {{-- ADD CUSTOMER MODAL --}}
    @if ($showAddCustomer)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4 backdrop-blur-sm" wire:keydown.escape="closeAddCustomer">
            <div class="w-full max-w-sm overflow-hidden rounded-3xl bg-white shadow-xl dark:bg-gray-900" x-on:click.stop>
                <div class="flex items-center justify-between border-b border-gray-100 px-6 py-5 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl text-white" class="gradient-bg">
                            <x-heroicon-o-user-plus class="h-5 w-5" />
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-gray-900 dark:text-white">Tambah Customer</h2>
                            <p class="text-xs text-gray-500">Customer baru</p>
                        </div>
                    </div>
                    <button type="button" wire:click="closeAddCustomer" class="flex h-9 w-9 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-800" aria-label="Tutup tambah customer">
                        <x-heroicon-o-x-mark class="h-5 w-5" />
                    </button>
                </div>
                <div class="space-y-4 p-6">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-gray-900 dark:text-white">Nama <span class="text-red-500">*</span></label>
                        <x-filament::input.wrapper><x-filament::input type="text" wire:model.live="newCustomerName" placeholder="Nama customer" /></x-filament::input.wrapper>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-gray-900 dark:text-white">No. HP <span class="text-red-500">*</span></label>
                        <x-filament::input.wrapper><x-filament::input type="tel" wire:model.live="newCustomerPhone" placeholder="08xxxxxxxxxx" /></x-filament::input.wrapper>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-500 dark:text-gray-400">Email <span class="text-xs">(opsional)</span></label>
                        <x-filament::input.wrapper><x-filament::input type="email" wire:model.live="newCustomerEmail" placeholder="email@example.com" /></x-filament::input.wrapper>
                    </div>
                </div>
                <div class="flex gap-3 border-t border-gray-100 p-5 dark:border-gray-700">
                    <button type="button" wire:click="closeAddCustomer" class="flex-1 rounded-xl border border-gray-200 bg-white py-3 text-sm font-semibold text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">Batal</button>
                    <button type="button" wire:click="saveCustomer" wire:loading.attr="disabled" class="flex flex-1 items-center justify-center gap-2 rounded-xl py-3 text-sm font-semibold text-white transition-transform hover:-translate-y-0.5 disabled:opacity-50 gradient-bg-shadow">
                        <span wire:loading.remove wire:target="saveCustomer">
                            <x-heroicon-o-check class="h-4 w-4" />
                        </span>
                        Simpan
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- MODIFIER MODAL --}}
    @if ($showModifierModal && $modifierProductId)
        @php
            $product = \App\Models\Product::with('modifiers')->find($modifierProductId);
            $addonModifiers = $product?->addonModifiers ?? collect();
            $optionModifiers = $product?->optionModifiers ?? collect();
        @endphp
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4 backdrop-blur-sm" wire:keydown.escape="closeModifierModal">
            <div class="w-full max-w-md overflow-hidden rounded-3xl bg-white shadow-xl dark:bg-gray-900" x-on:click.stop>
                <div class="flex items-center justify-between border-b border-gray-100 px-6 py-5 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-500 text-white">
                            <x-heroicon-o-adjustments-horizontal class="h-5 w-5" />
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-gray-900 dark:text-white">Modifier</h2>
                            <p class="text-xs text-gray-500">{{ $product?->name ?? 'Produk' }}</p>
                        </div>
                    </div>
                    <button type="button" wire:click="closeModifierModal" class="flex h-9 w-9 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-800" aria-label="Tutup pilihan modifier">
                        <x-heroicon-o-x-mark class="h-5 w-5" />
                    </button>
                </div>
                <div class="max-h-[60vh] space-y-4 overflow-y-auto p-6">
                    {{-- Addon Modifiers --}}
                    @if($addonModifiers->count() > 0)
                        <div>
                            <h3 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">Tambahan (Pilih beberapa)</h3>
                            <div class="space-y-2">
                                @foreach($addonModifiers as $modifier)
                                    <label class="flex cursor-pointer items-center justify-between rounded-xl border border-gray-200 bg-gray-50 p-3 transition-colors hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:hover:bg-gray-700">
                                        <div class="flex items-center gap-3">
                                            <input
                                                type="checkbox"
                                                wire:click="toggleModifier({{ json_encode(['id' => $modifier->id, 'name' => $modifier->name, 'price' => $modifier->price_adjustment]) }})"
                                                @if(in_array($modifier->id, array_column($selectedModifiers, 'id'))) checked @endif
                                                class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                            >
                                            <span class="text-sm text-gray-900 dark:text-white">{{ $modifier->name }}</span>
                                        </div>
                                        @if($modifier->price_adjustment > 0)
                                            <span class="text-sm font-medium text-blue-600 dark:text-blue-400">+ Rp {{ number_format($modifier->price_adjustment, 0, ',', '.') }}</span>
                                        @endif
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Option Modifiers --}}
                    @if($optionModifiers->count() > 0)
                        <div>
                            <h3 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">Pilihan (Pilih satu)</h3>
                            <div class="space-y-2">
                                @foreach($optionModifiers as $modifier)
                                    <label class="flex cursor-pointer items-center justify-between rounded-xl border border-gray-200 bg-gray-50 p-3 transition-colors hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:hover:bg-gray-700">
                                        <div class="flex items-center gap-3">
                                            <input
                                                type="radio"
                                                name="option_modifier"
                                                wire:click="toggleModifier({{ json_encode(['id' => $modifier->id, 'name' => $modifier->name, 'price' => $modifier->price_adjustment]) }})"
                                                @if(in_array($modifier->id, array_column($selectedModifiers, 'id'))) checked @endif
                                                class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500"
                                            >
                                            <span class="text-sm text-gray-900 dark:text-white">{{ $modifier->name }}</span>
                                        </div>
                                        @if($modifier->price_adjustment != 0)
                                            <span class="text-sm font-medium @if($modifier->price_adjustment > 0) text-blue-600 dark:text-blue-400 @else text-green-600 dark:text-green-400 @endif">
                                                {{ $modifier->price_adjustment > 0 ? '+' : '' }} Rp {{ number_format($modifier->price_adjustment, 0, ',', '.') }}
                                            </span>
                                        @endif
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Notes --}}
                    <div>
                        <h3 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">Catatan</h3>
                        <textarea
                            wire:model.live="modifierNotes"
                            rows="2"
                            placeholder="Contoh: Tidak pakai es, extra pedas..."
                            class="w-full rounded-xl border border-gray-200 bg-gray-50 p-3 text-sm text-gray-900 placeholder-gray-400 transition-colors focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        ></textarea>
                    </div>
                </div>
                <div class="flex gap-3 border-t border-gray-100 p-5 dark:border-gray-700">
                    <button type="button" wire:click="closeModifierModal" class="flex-1 rounded-xl border border-gray-200 bg-white py-3 text-sm font-semibold text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">Batal</button>
                    <button type="button" wire:click="saveModifiers" class="flex flex-1 items-center justify-center gap-2 rounded-xl py-3 text-sm font-semibold text-white transition-transform hover:-translate-y-0.5 gradient-bg-shadow">
                        <x-heroicon-o-check class="h-4 w-4" />
                        Simpan
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Order Details Modal --}}
    @livewire(\App\Livewire\OrderDetailsModal::class)

</x-filament-panels::page>