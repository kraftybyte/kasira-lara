<x-filament-panels::page>

    {{-- Active Table Banner --}}
    @if($this->activeTableId || $this->tableId)
        <div class="mb-3 rounded-xl border-2 border-emerald-200 bg-emerald-50 p-3 dark:border-emerald-500/30 dark:bg-emerald-500/10">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-500 text-white">
                        <x-heroicon-o-archive-box class="h-6 w-6" />
                    </div>
                    <div>
                        @if($this->activeTableId)
                            <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $this->selectedTable?->name ?? 'Meja Aktif' }}</p>
                            <p class="text-sm text-emerald-600 dark:text-emerald-400">Bill sedang aktif</p>
                        @elseif($this->tableId)
                            <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $this->selectedTable?->name ?? 'Meja Dipilih' }}</p>
                            <p class="text-sm text-gray-500">Siap menambahkan item</p>
                        @endif
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    @if($this->activeTableId)
                        <button type="button" wire:click="saveToTable" @disabled(empty($this->cart)) class="btn btn-outline-success btn-md">
                            <x-heroicon-o-check class="h-4 w-4" />
                            Simpan
                        </button>
                    @endif
                    <button type="button" wire:click="clearTable" class="btn btn-outline-danger btn-md">
                        <x-heroicon-o-x-mark class="h-4 w-4" />
                        Tutup Meja
                    </button>
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
                <a href="{{ url('/admin/' . filament()->getTenant()?->id . '/tables') }}" class="text-[10px] font-medium text-red-500 hover:text-red-600">Lihat Semua</a>
            </div>
            <div class="flex flex-wrap gap-2">
                @foreach($this->activeTables as $table)
                    @php
                        $tableSales = $table->sales ?? collect();
                        $unpaidCash = $tableSales->where('status', 'pending')->where('payment_method', 'pending')->sum('grand_total');
                        $paidOrders = $tableSales->where('status', 'completed')->sum('grand_total');
                        $hasUnpaidCash = $unpaidCash > 0;
                    @endphp
                    <a href="{{ url('/admin/' . filament()->getTenant()?->id . '/tables-overview?table=' . $table->id) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-gray-100 bg-gray-50 px-3 py-1.5 text-center transition-all hover:border-red-300 hover:bg-red-50/50 dark:border-gray-700 dark:bg-gray-800 dark:hover:border-red-500/30">
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

    <div class="grid grid-cols-1 gap-3 xl:grid-cols-12">

        {{-- PRODUCTS --}}
        <div class="xl:col-span-8">

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
                <div class="p-3">
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                        @forelse ($this->products as $product)
                            <button
                                type="button"
                                wire:click="addToCart({{ $product->id }})"
                                wire:loading.attr="disabled"
                                class="group flex flex-col overflow-hidden rounded-xl border border-gray-100 bg-white transition-all hover:shadow-lg disabled:cursor-wait disabled:opacity-50 dark:border-gray-700 dark:bg-gray-800"
                            >
                                {{-- Image (Standard Size) --}}
                                <div class="relative aspect-4/3 w-full shrink-0 overflow-hidden bg-gray-50 dark:bg-gray-700">
                                    @if ($product->image)
                                        <img src="{{ Storage::disk('public')->url($product->image) }}" alt="{{ $product->name }}" class="h-full w-full object-cover">
                                    @else
                                        <div class="flex h-full w-full items-center justify-center bg-linear-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600">
                                            <span class="text-2xl font-bold uppercase text-gray-300">{{ substr($product->name, 0, 2) }}</span>
                                        </div>
                                    @endif

                                    {{-- Badge --}}
                                    @if ($product->rate_type === 'duration')
                                        <div class="absolute left-2 top-2">
                                            <span class="rounded-full bg-amber-500 px-2.5 py-1 text-xs font-semibold text-white shadow">Durasi/Jam</span>
                                        </div>
                                    @endif
                                    @if ($product->rate_type !== 'duration' && (float) $product->stock <= 0)
                                        <div class="absolute left-2 top-2">
                                            <span class="rounded-full bg-red-500 px-2.5 py-1 text-xs font-semibold text-white shadow">Habis</span>
                                        </div>
                                    @endif
                                </div>

                                {{-- Info (Centered Layout) --}}
                                <div class="flex flex-1 flex-col p-3">
                                    <div class="flex flex-1 flex-col items-center justify-center text-center">
                                        <h3 class="text-sm font-semibold leading-tight text-gray-900 dark:text-white">{{ $product->name }}</h3>
                                        <p class="mt-2 text-base font-bold text-red-500">Rp {{ number_format((float) $product->selling_price, 0, ',', '.') }}</p>
                                    </div>
                                    <div class="mt-4 flex justify-start">
                                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-red-500 text-sm font-bold text-white shadow transition-transform duration-200 group-hover:scale-110">
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
        <div class="xl:col-span-4">
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


                                {{-- SEARCH ICON --}}
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <x-heroicon-o-magnifying-glass class="h-4 w-4 text-gray-400" />
                                </div>


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
                            <button type="button" wire:click="clearCart" class="text-xs font-medium text-red-500 transition-colors hover:text-red-600">Hapus semua</button>
                        @endif
                    </div>
                    <div class="scrollbar-thin p-3 max-h-48 overflow-y-auto">
                        @forelse ($cart as $productId => $item)
                            @php
                                $cartProduct = \App\Models\Product::find($productId);
                                $isDuration = $item['is_duration'] ?? false;
                            @endphp
                            <div wire:key="cart-item-{{ $productId }}" class="mb-2 flex items-center gap-2 rounded-xl border border-gray-100 bg-gray-50/50 p-2.5 dark:border-gray-700/50 dark:bg-gray-800/50 {{ $isDuration ? 'border-amber-200 dark:border-amber-700/50' : '' }}">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-lg {{ $isDuration ? 'bg-amber-100 dark:bg-amber-500/20' : 'bg-gray-200 dark:bg-gray-700' }}">
                                    @if($cartProduct?->image)
                                        <img src="{{ Storage::disk('public')->url($cartProduct->image) }}" alt="{{ $item['product_name'] }}" class="h-full w-full object-cover">
                                    @else
                                        <x-heroicon-o-clock class="h-4 w-4 {{ $isDuration ? 'text-amber-600 dark:text-amber-400' : 'text-gray-400' }}" />
                                    @endif
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-xs font-medium text-gray-900 dark:text-white">{{ $item['product_name'] }}</p>
                                    <p class="text-[11px] text-gray-500">
                                        @if($isDuration)
                                            <span class="text-amber-600 dark:text-amber-400">⏱ {{ number_format($item['unit_price'], 0, ',', '.') }}/jam</span>
                                        @else
                                            Rp {{ number_format($item['unit_price'], 0, ',', '.') }} × {{ $item['quantity'] }}
                                        @endif
                                    </p>
                                </div>
                                <div class="flex items-center gap-1">
                                    <button type="button" wire:click="decrementQuantity({{ $productId }})" class="btn-qty">
                                        <x-heroicon-o-minus class="h-3 w-3" />
                                    </button>
                                    <span class="w-6 text-center text-xs font-semibold text-gray-900 dark:text-white">{{ $item['quantity'] }}</span>
                                    <button type="button" wire:click="incrementQuantity({{ $productId }})" class="btn-qty">
                                        <x-heroicon-o-plus class="h-3 w-3" />
                                    </button>
                                </div>
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
                                <select wire:model.live="tableId" class="block w-full cursor-pointer rounded-lg border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 transition-colors focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white {{ $this->cartHasDurationProducts && !$tableId ? 'border-amber-400' : '' }}">
                                    <option value="">Pilih Meja</option>
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
                                @if($this->cartHasDurationProducts)
                                    Checkout - {{ $this->selectedTable?->name }}
                                @else
                                    Bayar Sekarang
                                @endif
                            </button>
                        @elseif(count($cart) > 0 && $this->tableId)
                            {{-- Save to Table Button (Required for Duration Products) --}}
                            <button type="button" wire:click="saveToTable" class="btn btn-primary btn-lg w-full">
                                <x-heroicon-o-archive-box class="h-4 w-4" />
                                @if($this->cartHasDurationProducts)
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
                <div class="flex shrink-0 items-center justify-between border-b border-gray-100 px-6 py-5 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl text-white" class="gradient-bg">
                            <x-heroicon-o-credit-card class="h-5 w-5" />
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-gray-900 dark:text-white">Pembayaran</h2>
                            <p class="text-xs text-gray-500">Selesaikan transaksi</p>
                        </div>
                    </div>
                    <button type="button" wire:click="closeCheckout" class="btn-icon btn-ghost">
                        <x-heroicon-o-x-mark class="h-5 w-5" />
                    </button>
                </div>

                {{-- CONTENT --}}
                <div class="kasira-scrollbar min-h-0 flex-1 overflow-y-auto">
                    <div class="space-y-4 p-6">

                        {{-- TOTAL --}}
                        <div class="overflow-hidden rounded-2xl p-5 text-gray-900" class="gradient-bg">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-medium text-gray-900">Total pembayaran</p>
                                    <p class="mt-1 text-3xl font-black">Rp {{ number_format($this->total, 0, ',', '.') }}</p>
                                </div>
                                <x-heroicon-o-credit-card class="h-10 w-10 text-white/80" />
                            </div>
                        </div>

                        {{-- PAYMENT METHOD --}}
                        <div>
                            <label class="mb-3 block text-sm font-semibold text-gray-900 dark:text-white">Metode Pembayaran</label>
                            <div class="grid grid-cols-3 gap-2">
                                @foreach (['cash' => ['label' => 'Tunai', 'icon' => 'banknotes'], 'qris' => ['label' => 'QRIS', 'icon' => 'qr'], 'transfer' => ['label' => 'Transfer', 'icon' => 'building']] as $method => $data)
                                    <button type="button" wire:click="$set('paymentMethod', '{{ $method }}')" class="relative rounded-xl border p-3 text-center transition-all {{ $paymentMethod === $method ? 'border-red-500 bg-red-50 ring-2 ring-red-500/20 dark:border-red-500 dark:bg-red-500/10' : 'border-gray-200 bg-white hover:border-red-300 dark:border-gray-700 dark:bg-gray-800' }}">
                                        <div class="mx-auto mb-2 flex h-9 w-9 items-center justify-center rounded-lg {{ $paymentMethod === $method ? 'bg-red-500 text-white' : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-300' }}">
                                            @if ($data['icon'] === 'banknotes')
                                                <x-heroicon-o-credit-card class="h-5 w-5" />
                                            @elseif ($data['icon'] === 'qr')
                                                <x-heroicon-o-qr-code class="h-5 w-5" />
                                            @else
                                                <x-heroicon-o-building-office class="h-5 w-5" />
                                            @endif
                                        </div>
                                        <span class="text-xs font-semibold text-gray-900 dark:text-white">{{ $data['label'] }}</span>
                                        @if ($paymentMethod === $method)
                                            <div class="absolute -right-1 -top-1 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-white">
                                                <x-heroicon-o-check class="h-3 w-3" />
                                            </div>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        {{-- CASH INPUT --}}
                        @if ($paymentMethod === 'cash')
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-gray-900 dark:text-white">Uang Diterima</label>
                                <x-filament::input.wrapper class="text-lg">
                                    <x-filament::input type="number" wire:model.live="paidAmount" min="0" step="1000" placeholder="0" class="text-lg font-bold" />
                                </x-filament::input.wrapper>
                                <div class="mt-2 grid grid-cols-4 gap-2">
                                    @foreach ([20000, 50000, 100000, 200000] as $amount)
                                        <button type="button" wire:click="$set('paidAmount', {{ $amount }})" class="btn-amount">
                                            Rp {{ number_format($amount/1000, 0, '', '.') }}K
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div class="flex items-center gap-3 rounded-xl border-2 border-amber-300 bg-amber-50 p-4 dark:border-amber-500 dark:bg-amber-500/20">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-500 text-white">
                                    <x-heroicon-o-information-circle class="h-5 w-5" />
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-amber-800 dark:text-amber-300">Pembayaran {{ strtoupper($paymentMethod) }}</p>
                                    <p class="text-xs text-amber-700 dark:text-amber-400">Pastikan sudah diterima sebelum menyimpan</p>
                                </div>
                            </div>
                        @endif

                        {{-- CUSTOMER SELECT --}}
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-gray-900 dark:text-white">Customer</label>
                            <x-filament::input.wrapper>
                                <x-filament::input.select wire:model="customerId">
                                    <option value="">Walk-in Customer</option>
                                    @foreach ($this->customers as $customer)
                                        <option value="{{ $customer->id }}">{{ $customer->name }}@if ($customer->is_member) ★@endif</option>
                                    @endforeach
                                </x-filament::input.select>
                            </x-filament::input.wrapper>
                        </div>

                        {{-- MEMBER INFO --}}
                        @if ($customerId)
                            @php $selectedCustomer = $this->customers->firstWhere('id', $customerId); @endphp
                            @if ($selectedCustomer && $selectedCustomer->is_member)
                                <div class="flex items-center gap-3 rounded-xl border-2 border-amber-300 bg-amber-50 p-4 dark:border-amber-500 dark:bg-amber-500/20">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl gradient-bg">
                                        <x-heroicon-s-star class="h-5 w-5" />
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $selectedCustomer->name }}</p>
                                        <p class="text-xs text-amber-600 dark:text-amber-400">{{ number_format((int) $selectedCustomer->points, 0, ',', '.') }} Points</p>
                                    </div>
                                </div>
                            @endif
                        @endif

                        {{-- NOTES --}}
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-gray-900 dark:text-white">Catatan</label>
                            <x-filament::input.wrapper>
                                <textarea wire:model="paymentNotes" rows="2" placeholder="Catatan (opsional)..." class="fi-input block w-full rounded-lg border-0 bg-transparent px-3 py-2 text-sm text-gray-900 placeholder-gray-400 outline-none transition duration-75 dark:text-white dark:placeholder-gray-500 focus:ring-0 disabled:opacity-70"></textarea>
                            </x-filament::input.wrapper>
                        </div>

                        {{-- SUMMARY --}}
                        <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
                            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                                <div class="flex items-center justify-between px-4 py-2.5">
                                    <span class="text-xs text-gray-500">Subtotal (sblm PPN)</span>
                                    <span class="text-sm font-semibold text-gray-900 dark:text-white">Rp {{ number_format($this->subtotalBeforeTax, 0, ',', '.') }}</span>
                                </div>
                                @if($this->showTax && $this->taxAmount > 0)
                                    <div class="flex items-center justify-between px-4 py-2.5">
                                        <span class="text-xs text-gray-500">PPN ({{ $this->taxRate }}%)</span>
                                        <span class="text-sm font-semibold text-green-600 dark:text-green-400">Rp {{ number_format($this->taxAmount, 0, ',', '.') }}</span>
                                    </div>
                                @endif
                                <div class="flex items-center justify-between px-4 py-2.5 bg-red-50/40">
                                    <span class="text-sm font-semibold text-gray-900 dark:text-white">Total</span>
                                    <span class="text-base font-bold text-gray-900 dark:text-white">Rp {{ number_format($this->total, 0, ',', '.') }}</span>
                                </div>
                                @if ($paymentMethod === 'cash')
                                    <div class="flex items-center justify-between px-4 py-2.5">
                                        <span class="text-xs text-gray-500">Dibayar</span>
                                        <span class="text-sm font-semibold text-gray-900 dark:text-white">Rp {{ number_format((float) $paidAmount, 0, ',', '.') }}</span>
                                    </div>
                                    <div class="flex items-center justify-between px-4 py-3">
                                        <span class="text-sm font-semibold text-gray-900 dark:text-white">Kembalian</span>
                                        <span class="text-base font-bold text-green-600">Rp {{ number_format(max(0, (float) $this->changeAmount), 0, ',', '.') }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        @if ($paymentMethod === 'cash' && (float) $paidAmount > 0 && $this->remainingPayment > 0)
                            <div class="flex items-center gap-3 rounded-xl border-2 border-red-300 bg-red-50 p-4 dark:border-red-500 dark:bg-red-500/20">
                                <x-heroicon-o-exclamation-triangle class="h-5 w-5 shrink-0 text-red-500 dark:text-red-400" />
                                <div>
                                    <p class="text-sm font-semibold text-red-700 dark:text-red-300">Kurang Rp {{ number_format($this->remainingPayment, 0, ',', '.') }}</p>
                                </div>
                            </div>
                        @endif

                    </div>
                </div>

                {{-- FOOTER --}}
                <div class="shrink-0 border-t border-gray-100 bg-white p-5 dark:border-gray-700 dark:bg-gray-900">
                    <div class="flex gap-3">
                        <button type="button" wire:click="closeCheckout" class="btn btn-secondary btn-lg flex-1">
                            Batal
                        </button>
                        <button type="button" wire:click="processPayment" wire:loading.attr="disabled" @disabled($this->total <= 0 || ($paymentMethod === 'cash' && (float) $paidAmount < (float) $this->total)) class="btn btn-primary btn-lg flex-1">
                            <span wire:loading.remove wire:target="processPayment">
                                <x-heroicon-o-check class="h-4 w-4" />
                            </span>
                            Bayar Rp {{ number_format($this->total, 0, ',', '.') }}
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
                    <button type="button" wire:click="closeAddCustomer" class="flex h-9 w-9 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-800">
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

    {{-- Order Details Modal --}}
    @livewire(\App\Livewire\OrderDetailsModal::class)

</x-filament-panels::page>