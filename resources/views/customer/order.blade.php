<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesan - {{ $tenant->name }} - Meja {{ $table->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/heroicons@24.0.0/clipboard.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#EF4444',
                        secondary: '#F97316',
                    },
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .hide-scrollbar::-webkit-scrollbar { display: none; }
        .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

        /* Touch scroll for category tabs */
        .touch-scroll {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scroll-snap-type: x mandatory;
            scroll-behavior: smooth;
        }
        .touch-scroll > * {
            scroll-snap-align: start;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <div id="app" class="max-w-md mx-auto bg-white min-h-screen flex flex-col">

        {{-- Header --}}
        <div class="sticky top-0 z-50 bg-white/95 backdrop-blur-md border-b border-gray-100 shadow-sm">
            <div class="px-4 py-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <button onclick="goBack()" class="w-10 h-10 rounded-xl bg-gray-100 hover:bg-gray-200 flex items-center justify-center transition-colors">
                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </button>
                        <div>
                            <h1 class="text-lg font-bold text-gray-900" id="page-title">{{ $tenant->name }}</h1>
                            <div class="flex items-center gap-1.5">
                                <div class="w-1.5 h-1.5 rounded-full bg-primary"></div>
                                <p class="text-xs text-gray-500">Meja {{ $table->name }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="relative">
                        <button onclick="toggleCart()" class="w-11 h-11 rounded-xl bg-gradient-to-br from-red-500 to-orange-500 text-white flex items-center justify-center relative shadow-lg shadow-red-500/25 hover:shadow-red-500/40 transition-all active:scale-95">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            <span id="cart-badge" class="hidden absolute -top-1 -right-1 w-5 h-5 bg-gray-900 text-white text-xs rounded-full flex items-center justify-center font-bold shadow-lg">0</span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Categories (only show on menu page) --}}
            <div id="category-tabs" class="px-4 pb-3">
                <div id="category-scroll" class="flex gap-2 overflow-x-auto hide-scrollbar pb-1" style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
                    <button onclick="filterCategory('all')" class="category-tab active px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap bg-gradient-to-r from-red-500 to-orange-500 text-white shadow-md shrink-0">
                        Semua
                    </button>
                    @foreach($products as $categoryId => $items)
                        @php $category = $items->first()->category @endphp
                        <button onclick="filterCategory('{{ $categoryId }}')" class="category-tab px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors shrink-0">
                            {{ $category->name ?? 'Lainnya' }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Success Message --}}
        @if($completedSale)
            <div class="mx-4 mt-4 p-4 bg-green-50 border border-green-200 rounded-xl text-center">
                <div class="flex flex-col items-center gap-3">
                    <div class="w-12 h-12 rounded-full bg-green-500 flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-bold text-green-800">Pembayaran Berhasil!</p>
                        <p class="text-xs text-green-600 mt-1">Invoice: {{ $completedSale->invoice_number }}</p>
                    </div>
                    <div class="pt-2 border-t border-green-200">
                        <p class="text-sm text-green-700">Pesanan Anda sedang diproses. Silakan tunggu.</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Pending Payment Banner --}}
        @if(isset($pendingSale) && $pendingSale)
            <div class="mx-4 mt-4 p-4 bg-amber-50 border border-amber-200 rounded-xl">
                <div class="flex flex-col items-center gap-3 text-center">
                    <div class="w-12 h-12 rounded-full bg-amber-500 flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-bold text-amber-800">Menunggu Pembayaran</p>
                        <p class="text-xs text-amber-600 mt-1">Invoice: {{ $pendingSale->invoice_number }}</p>
                    </div>
                    <div class="flex gap-2 w-full">
                        <a href="{{ route('customer.order.payment', ['tenant' => $tenant->getRouteKey(), 'table' => $table->id, 'sale' => $pendingSale->id]) }}" class="flex-1 py-2.5 bg-amber-500 text-white text-center rounded-lg font-semibold hover:bg-amber-600 transition-colors">
                            Bayar Sekarang
                        </a>
                        <form action="{{ route('customer.order.check-status', ['tenant' => $tenant->getRouteKey(), 'table' => $table->id, 'sale' => $pendingSale->id]) }}" method="POST" class="flex-1">
                            @csrf
                            <button type="submit" class="w-full py-2.5 bg-gray-100 text-gray-700 rounded-lg font-semibold hover:bg-gray-200 transition-colors">
                                Cek Status
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        {{-- Products Page --}}
        <div id="menu-page" class="flex-1 overflow-y-auto px-4 py-4 pb-28">
            @forelse($products as $categoryId => $items)
                @php $category = $items->first()->category @endphp
                <div class="product-category mb-6" data-category="{{ $categoryId }}">
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">{{ $category->name ?? 'Lainnya' }}</h3>
                    <div class="space-y-3">
                        @foreach($items as $product)
                            <div class="product-card bg-white border border-gray-100 rounded-2xl p-4 shadow-sm" data-product-id="{{ $product->id }}">
                                <div class="flex gap-3">
                                    @if($product->image)
                                        <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" class="w-20 h-20 rounded-xl object-cover bg-gray-100">
                                    @else
                                        <div class="w-20 h-20 rounded-xl bg-gradient-to-br from-gray-100 to-gray-50 flex items-center justify-center">
                                            <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                        </div>
                                    @endif
                                    <div class="flex-1 min-w-0">
                                        <h4 class="font-semibold text-gray-900">{{ $product->name }}</h4>
                                        @if($product->description)
                                            <p class="text-xs text-gray-500 mt-1 line-clamp-2">{{ $product->description }}</p>
                                        @endif
                                        <div class="flex items-center justify-between mt-2">
                                            <span class="font-bold text-primary">Rp {{ number_format($product->selling_price, 0, ',', '.') }}</span>
                                            <div class="flex items-center gap-2">
                                                @if($product->modifiers->count() > 0)
                                                    <button type="button" onclick="event.stopPropagation(); openModifierModal({{ $product->id }});" class="px-3 py-1.5 text-xs font-medium text-white bg-gradient-to-r from-orange-500 to-amber-500 rounded-lg hover:from-orange-600 hover:to-amber-600 transition-all shadow-sm">
                                                        Custom
                                                    </button>
                                                @endif
                                                <button type="button" onclick="addToCart({{ $product->id }}, '{{ addslashes($product->name) }}', {{ $product->selling_price }})" class="w-8 h-8 rounded-full bg-primary text-white flex items-center justify-center hover:bg-red-600 transition-all active:scale-90">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="text-center py-12">
                    <div class="w-16 h-16 mx-auto rounded-full bg-gray-100 flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                        </svg>
                    </div>
                    <p class="text-gray-500">Tidak ada produk tersedia</p>
                </div>
            @endforelse
        </div>

        {{-- Cart Page --}}
        <div id="cart-page" class="hidden flex-1 overflow-y-auto pb-36">
            <div class="p-4">
                <h2 class="text-lg font-bold text-gray-900 mb-4">Keranjang Belanja</h2>

                {{-- Cart Items --}}
                <div id="cart-items-container" class="space-y-3 mb-4">
                    {{-- Items will be rendered here --}}
                </div>

                {{-- Empty Cart --}}
                <div id="empty-cart" class="hidden text-center py-12">
                    <div class="w-16 h-16 mx-auto rounded-full bg-gray-100 flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <p class="text-gray-500">Keranjang masih kosong</p>
                </div>

                {{-- Payment Methods (inside cart container) --}}
                <div id="payment-section" class="hidden mt-6">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">Pilih Metode Pembayaran</h3>
                    <div class="space-y-3">
                        @if($isQrisActive)
                        <label class="payment-option flex items-center gap-4 p-4 bg-white border-2 border-gray-100 rounded-2xl cursor-pointer hover:border-blue-400 transition-all peer-checked:border-blue-500">
                            <input type="radio" name="payment_method" value="qris" class="sr-only peer">
                            <div class="w-12 h-12 rounded-xl bg-blue-500 flex items-center justify-center">
                                <x-heroicon-m-qr-code class="w-6 h-6 text-white"/>
                            </div>
                            <div class="flex-1">
                                <p class="font-semibold text-gray-900">QRIS</p>
                                <p class="text-xs text-gray-500">Scan QR code untuk bayar</p>
                            </div>
                            <div class="w-6 h-6 rounded-full border-2 border-gray-300 peer-checked:bg-blue-500 peer-checked:border-blue-500 flex items-center justify-center">
                                <svg class="w-3 h-3 text-white hidden peer-checked:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                        </label>
                        @endif
                        @if($isVaActive)
                        <label class="payment-option flex items-center gap-4 p-4 bg-white border-2 border-gray-100 rounded-2xl cursor-pointer hover:border-purple-400 transition-all peer-checked:border-purple-500">
                            <input type="radio" name="payment_method" value="transfer" class="sr-only peer">
                            <div class="w-12 h-12 rounded-xl bg-purple-500 flex items-center justify-center">
                                <x-heroicon-s-building-office class="w-6 h-6 text-white"/>
                            </div>
                            <div class="flex-1">
                                <p class="font-semibold text-gray-900">Transfer</p>
                                <p class="text-xs text-gray-500">Virtual Account bank</p>
                            </div>
                            <div class="w-6 h-6 rounded-full border-2 border-gray-300 peer-checked:bg-purple-500 peer-checked:border-purple-500 flex items-center justify-center">
                                <svg class="w-3 h-3 text-white hidden peer-checked:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                        </label>
                        @endif
                        @if($isCounterActive)
                        <label class="payment-option flex items-center gap-4 p-4 bg-white border-2 border-amber-200 rounded-2xl cursor-pointer hover:border-amber-400 transition-all peer-checked:border-amber-500">
                            <input type="radio" name="payment_method" value="counter" class="sr-only peer">
                            <div class="w-12 h-12 rounded-xl bg-amber-500 flex items-center justify-center">
                                <x-heroicon-s-credit-card class="w-6 h-6 text-white"/>
                            </div>
                            <div class="flex-1">
                                <p class="font-semibold text-gray-900">Bayar di Kasir</p>
                                <p class="text-xs text-gray-500">Cash / QR ke kasir</p>
                            </div>
                            <div class="w-6 h-6 rounded-full border-2 border-gray-300 peer-checked:bg-amber-500 peer-checked:border-amber-500 flex items-center justify-center">
                                <svg class="w-3 h-3 text-white hidden peer-checked:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                        </label>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Cart Summary (Fixed Bottom) --}}
        <div id="cart-summary" class="hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 shadow-[0_-4px_20px_rgba(0,0,0,0.08)] z-50">
            <form action="{{ route('customer.order.checkout', [$tenant->slug, $table->id]) }}" method="POST" id="order-form" onsubmit="return submitOrder()">
                @csrf
                <input type="hidden" name="items" id="cart-items">
                <input type="hidden" name="payment_method" id="payment-method">
                <div class="max-w-md mx-auto p-4 space-y-3">
                    {{-- Customer Info --}}
                    <div class="flex gap-2">
                        <input type="text" name="customer_name" id="customer-name" placeholder="Nama pemesan" required class="flex-1 px-4 py-3 border-2 border-gray-100 rounded-xl text-sm focus:border-red-400 focus:ring-0 transition-colors">
                        <input type="tel" name="customer_phone" placeholder="No. HP" class="w-28 px-4 py-3 border-2 border-gray-100 rounded-xl text-sm focus:border-red-400 focus:ring-0 transition-colors">
                    </div>

                    {{-- Total & Button --}}
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500"><span id="cart-count">0</span> item</p>
                            <p class="text-2xl font-bold bg-gradient-to-r from-red-500 to-orange-500 bg-clip-text text-transparent">Rp <span id="cart-total">0</span></p>
                        </div>
                        <button type="submit" id="checkout-btn" class="px-8 py-4 bg-gradient-to-r from-red-500 to-orange-500 text-white font-bold rounded-xl hover:shadow-lg hover:shadow-red-500/30 hover:from-red-600 hover:to-orange-600 transition-all active:scale-[0.98] flex items-center gap-2">
                            <span>Checkout</span>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </form>
        </div>

    </div>

    {{-- Modifier Modal --}}
    <div id="modifier-modal" class="fixed inset-0 hidden" style="z-index: 9999;">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeModifierModal()"></div>
        <div class="absolute bottom-0 left-0 right-0 bg-white rounded-t-3xl max-h-[85vh] flex flex-col" style="max-height: 85vh;">
            <div class="sticky top-0 bg-white px-4 py-3 border-b border-gray-100 flex items-center justify-between z-10">
                <div>
                    <h3 class="text-lg font-bold text-gray-900">Pilih Customisasi</h3>
                    <p id="modifier-product-name" class="text-sm text-gray-500"></p>
                </div>
                <button onclick="closeModifierModal()" class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center hover:bg-gray-200">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div id="modifier-content" class="flex-1 overflow-y-auto p-4" style="overflow-y: auto;">
                {{-- Modifiers will be rendered here --}}
            </div>
            <div class="sticky bottom-0 bg-white px-4 py-3 border-t border-gray-100 z-10">
                <button type="button" onclick="addToCartWithModifiers()" class="w-full py-3.5 bg-gradient-to-r from-red-500 to-orange-500 text-white font-semibold rounded-xl shadow-lg">
                    Tambah ke Keranjang
                </button>
            </div>
        </div>
    </div>

    <script>
        // Cart state
        let cart = [];
        let currentPage = 'menu';
        let currentModifierProduct = null;
        let selectedModifiers = [];

        // Product modifiers data
        const productModifiers = {};
        @foreach($products as $categoryId => $items)
            @foreach($items as $product)
                @if($product->modifiers->count() > 0)
                    productModifiers[{{ $product->id }}] = {
                        name: '{{ addslashes($product->name) }}',
                        price: {{ $product->selling_price }},
                        modifiers: [
                            @foreach($product->modifiers as $mod)
                                {id: {{ $mod->id }}, name: '{{ addslashes($mod->name) }}', type: '{{ $mod->type }}', price: {{ $mod->price_adjustment }}},
                            @endforeach
                        ]
                    };
                @endif
            @endforeach
        @endforeach

        function openModifierModal(productId) {
            // Get the modal element
            var modal = document.getElementById('modifier-modal');
            var contentDiv = document.getElementById('modifier-content');
            var nameDiv = document.getElementById('modifier-product-name');

            // Find product data
            var productData = null;
            for (var key in productModifiers) {
                if (parseInt(key) === productId) {
                    productData = productModifiers[key];
                    break;
                }
            }

            if (!productData) {
                alert('Tidak ada customisasi untuk produk ini');
                return;
            }

            nameDiv.textContent = productData.name;

            // Build HTML
            var html = '';

            // Addon modifiers
            var addons = productData.modifiers.filter(function(m) { return m.type === 'addon'; });
            if (addons.length > 0) {
                html += '<div class="mb-4"><h4 class="text-sm font-semibold text-gray-700 mb-2">Extra (Tambah Harga)</h4>';
                addons.forEach(function(mod) {
                    html += '<label class="flex items-center justify-between p-3 bg-gray-50 rounded-xl cursor-pointer hover:bg-gray-100 mb-2">';
                    html += '<div class="flex items-center gap-3">';
                    html += '<input type="checkbox" id="mod-' + mod.id + '" value="' + mod.id + '" onclick="toggleModifier(' + mod.id + ', ' + mod.price + ')" class="w-5 h-5 rounded border-gray-300" style="accent-color: #EF4444;">';
                    html += '<span class="text-gray-900">' + mod.name + '</span></div>';
                    html += '<span class="text-sm font-medium text-red-500">+ Rp ' + mod.price.toLocaleString('id-ID') + '</span></label>';
                });
                html += '</div>';
            }

            // Option modifiers
            var options = productData.modifiers.filter(function(m) { return m.type === 'option'; });
            if (options.length > 0) {
                html += '<div><h4 class="text-sm font-semibold text-gray-700 mb-2">Pilihan</h4>';
                options.forEach(function(mod) {
                    html += '<label class="flex items-center justify-between p-3 bg-gray-50 rounded-xl cursor-pointer hover:bg-gray-100 mb-2">';
                    html += '<div class="flex items-center gap-3">';
                    html += '<input type="radio" name="option_modifier" value="' + mod.id + '" onclick="selectOption(' + mod.id + ', ' + mod.price + ', \'' + mod.name.replace(/'/g, "\\'") + '\')" class="w-5 h-5 border-gray-300" style="accent-color: #EF4444;">';
                    html += '<span class="text-gray-900">' + mod.name + '</span></div>';
                    if (mod.price > 0) {
                        html += '<span class="text-sm font-medium text-red-500">+ Rp ' + mod.price.toLocaleString('id-ID') + '</span>';
                    }
                    html += '</label>';
                });
                html += '</div>';
            }

            contentDiv.innerHTML = html;
            currentModifierProduct = productId;
            selectedModifiers = [];
            selectedOption = null;

            // Show modal
            modal.classList.remove('hidden');
        }

        function closeModifierModal() {
            var modal = document.getElementById('modifier-modal');
            if (modal) {
                modal.classList.add('hidden');
            }
            currentModifierProduct = null;
            selectedModifiers = [];
            selectedOption = null;
        }

        function toggleModifier(modId, price) {
            var idx = -1;
            for (var i = 0; i < selectedModifiers.length; i++) {
                if (selectedModifiers[i].id === modId) {
                    idx = i;
                    break;
                }
            }
            if (idx > -1) {
                selectedModifiers.splice(idx, 1);
            } else {
                selectedModifiers.push({ id: modId, price: price });
            }
        }

        var selectedOption = null;
        function selectOption(modId, price, name) {
            selectedOption = { id: modId, price: price, name: name };
        }

        function addToCartWithModifiers() {
            if (!currentModifierProduct) {
                alert('Pilih produk terlebih dahulu');
                return;
            }

            var productData = null;
            for (var key in productModifiers) {
                if (parseInt(key) === currentModifierProduct) {
                    productData = productModifiers[key];
                    break;
                }
            }

            if (!productData) return;

            // Add selected option if exists
            if (selectedOption) {
                selectedModifiers.push({ id: selectedOption.id, price: selectedOption.price, name: selectedOption.name });
            }

            // Calculate total price with modifiers
            var modifierTotal = 0;
            for (var i = 0; i < selectedModifiers.length; i++) {
                modifierTotal += selectedModifiers[i].price;
            }
            var finalPrice = productData.price + modifierTotal;

            // Check if product already in cart
            var existing = null;
            for (var i = 0; i < cart.length; i++) {
                if (cart[i].id === currentModifierProduct) {
                    existing = cart[i];
                    break;
                }
            }

            if (existing) {
                existing.quantity++;
                existing.price = finalPrice;
                existing.modifiers = selectedModifiers.slice();
            } else {
                cart.push({
                    id: currentModifierProduct,
                    name: productData.name,
                    price: finalPrice,
                    quantity: 1,
                    modifiers: selectedModifiers.slice()
                });
            }

            updateCartUI();
            closeModifierModal();
        }

        function addToCart(productId, name, price) {
            const existing = cart.find(item => item.id === productId);
            if (existing) {
                existing.quantity++;
            } else {
                cart.push({ id: productId, name: name, price: price, quantity: 1, modifiers: [] });
            }
            updateCartUI();

            // Show feedback
            const btn = document.querySelector(`[data-product-id="${productId}"] button`);
            if (btn) {
                btn.classList.add('scale-90');
                setTimeout(() => btn.classList.remove('scale-90'), 150);
            }
        }

        function removeFromCart(productId) {
            cart = cart.filter(item => item.id !== productId);
            updateCartUI();
            renderCartPage();
        }

        function updateQuantity(productId, change) {
            const item = cart.find(item => item.id === productId);
            if (item) {
                item.quantity += change;
                if (item.quantity <= 0) {
                    removeFromCart(productId);
                } else {
                    updateCartUI();
                    renderCartPage();
                }
            }
        }

        function updateCartUI() {
            const summary = document.getElementById('cart-summary');
            const countEl = document.getElementById('cart-count');
            const totalEl = document.getElementById('cart-total');
            const itemsInput = document.getElementById('cart-items');
            const badge = document.getElementById('cart-badge');
            const paymentSection = document.getElementById('payment-section');

            if (cart.length === 0) {
                summary.classList.add('hidden');
                badge.classList.add('hidden');
                if (paymentSection) paymentSection.classList.add('hidden');
                return;
            }

            summary.classList.remove('hidden');
            badge.classList.remove('hidden');
            if (paymentSection) paymentSection.classList.remove('hidden');

            const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            const totalQty = cart.reduce((sum, item) => sum + item.quantity, 0);

            countEl.textContent = totalQty;
            totalEl.textContent = total.toLocaleString('id-ID');
            badge.textContent = totalQty;
            itemsInput.value = JSON.stringify(cart.map(item => ({
                product_id: item.id,
                quantity: item.quantity,
                modifiers: item.modifiers || []
            })));
        }

        function toggleCart() {
            const menuPage = document.getElementById('menu-page');
            const cartPage = document.getElementById('cart-page');
            const pageTitle = document.getElementById('page-title');
            const categoryTabs = document.getElementById('category-tabs');

            if (currentPage === 'menu') {
                currentPage = 'cart';
                menuPage.classList.add('hidden');
                cartPage.classList.remove('hidden');
                pageTitle.textContent = 'Keranjang';
                categoryTabs.classList.add('hidden');
                renderCartPage();
            } else {
                currentPage = 'menu';
                menuPage.classList.remove('hidden');
                cartPage.classList.add('hidden');
                pageTitle.textContent = '{{ $tenant->name }}';
                categoryTabs.classList.remove('hidden');
            }
        }

        function goBack() {
            if (currentPage === 'cart') {
                toggleCart();
            }
        }

        function renderCartPage() {
            const container = document.getElementById('cart-items-container');
            const emptyCart = document.getElementById('empty-cart');

            if (cart.length === 0) {
                container.innerHTML = '';
                emptyCart.classList.remove('hidden');
                return;
            }

            emptyCart.classList.add('hidden');
            container.innerHTML = cart.map(item => {
                const modText = item.modifiers && item.modifiers.length > 0
                    ? '<p class="text-xs text-gray-500 mt-1">' + item.modifiers.map(m => m.name || 'Custom').join(', ') + '</p>'
                    : '';
                return `
                <div class="flex items-center gap-3 bg-white border border-gray-100 rounded-xl p-3">
                    <div class="flex-1">
                        <p class="font-semibold text-gray-900">${item.name}</p>
                        ${modText}
                        <p class="text-sm text-primary font-medium">Rp ${item.price.toLocaleString('id-ID')}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button onclick="updateQuantity(${item.id}, -1)" class="w-8 h-8 rounded-lg border border-gray-200 flex items-center justify-center text-gray-600 hover:bg-gray-50">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                            </svg>
                        </button>
                        <span class="w-8 text-center font-semibold">${item.quantity}</span>
                        <button onclick="updateQuantity(${item.id}, 1)" class="w-8 h-8 rounded-lg border border-gray-200 flex items-center justify-center text-gray-600 hover:bg-gray-50">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                        </button>
                    </div>
                    <div class="text-right">
                        <p class="font-bold text-gray-900">Rp ${(item.price * item.quantity).toLocaleString('id-ID')}</p>
                        <button onclick="removeFromCart(${item.id})" class="text-xs text-red-500 hover:underline">Hapus</button>
                    </div>
                </div>
            `}).join('');
        }

        function submitOrder() {
            if (cart.length === 0) {
                alert('Pilih setidaknya satu produk untuk dipesan.');
                return false;
            }

            const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
            if (!paymentMethod) {
                alert('Pilih metode pembayaran terlebih dahulu.');
                return false;
            }

            document.getElementById('payment-method').value = paymentMethod.value;
            return true;
        }

        function filterCategory(categoryId) {
            document.querySelectorAll('.category-tab').forEach(tab => {
                tab.classList.remove('bg-gradient-to-r', 'from-red-500', 'to-orange-500', 'text-white', 'shadow-md');
                tab.classList.add('bg-gray-100', 'text-gray-600');
            });
            event.target.classList.remove('bg-gray-100', 'text-gray-600');
            event.target.classList.add('bg-gradient-to-r', 'from-red-500', 'to-orange-500', 'text-white', 'shadow-md');

            document.querySelectorAll('.product-category').forEach(category => {
                if (categoryId === 'all' || category.dataset.category === categoryId) {
                    category.style.display = 'block';
                } else {
                    category.style.display = 'none';
                }
            });
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            // Touch swipe for category tabs
            const scrollContainer = document.getElementById('category-scroll');
            if (scrollContainer) {
                let isDown = false;
                let startX;
                let scrollLeft;

                scrollContainer.addEventListener('mousedown', (e) => {
                    isDown = true;
                    scrollContainer.style.cursor = 'grabbing';
                    startX = e.pageX - scrollContainer.offsetLeft;
                    scrollLeft = scrollContainer.scrollLeft;
                });

                scrollContainer.addEventListener('mouseleave', () => {
                    isDown = false;
                    scrollContainer.style.cursor = 'grab';
                });

                scrollContainer.addEventListener('mouseup', () => {
                    isDown = false;
                    scrollContainer.style.cursor = 'grab';
                });

                scrollContainer.addEventListener('mousemove', (e) => {
                    if (!isDown) return;
                    e.preventDefault();
                    const x = e.pageX - scrollContainer.offsetLeft;
                    const walk = (x - startX) * 2;
                    scrollContainer.scrollLeft = scrollLeft - walk;
                });

                // Touch events for mobile
                scrollContainer.addEventListener('touchstart', (e) => {
                    startX = e.touches[0].pageX - scrollContainer.offsetLeft;
                    scrollLeft = scrollContainer.scrollLeft;
                });

                scrollContainer.addEventListener('touchmove', (e) => {
                    const x = e.touches[0].pageX - scrollContainer.offsetLeft;
                    const walk = (x - startX) * 2;
                    scrollContainer.scrollLeft = scrollLeft - walk;
                });

                scrollContainer.style.cursor = 'grab';
            }

            // Set default payment based on availability
            // Priority: QRIS > VA > Counter
            @if($isQrisActive)
                const qrisOption = document.querySelector('input[name="payment_method"][value="qris"]');
                if (qrisOption) qrisOption.checked = true;
            @elseif($isVaActive)
                const vaOption = document.querySelector('input[name="payment_method"][value="transfer"]');
                if (vaOption) vaOption.checked = true;
            @elseif($isCounterActive)
                const counterOption = document.querySelector('input[name="payment_method"][value="counter"]');
                if (counterOption) counterOption.checked = true;
            @endif
        });
    </script>
</body>
</html>
