<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran - {{ $tenant->name }} - Meja {{ $table->name }}</title>
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
        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.4); }
            50% { box-shadow: 0 0 0 12px rgba(34, 197, 94, 0); }
        }
        .pulse-success { animation: pulse-glow 2s infinite; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="max-w-md mx-auto bg-white min-h-screen">

        {{-- Header --}}
        <div class="sticky top-0 z-50 bg-white/95 backdrop-blur-md border-b border-gray-100 shadow-sm">
            <div class="px-4 py-4">
                <div class="text-center">
                    <div class="w-14 h-14 mx-auto rounded-2xl bg-gradient-to-br from-red-500 to-orange-500 flex items-center justify-center mb-3 shadow-lg shadow-red-500/25">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                        </svg>
                    </div>
                    <p class="text-xs text-gray-500 font-medium">{{ $sale->invoice_number }}</p>
                    <h1 class="text-xl font-bold text-gray-900 mt-1">Pembayaran</h1>
                    <div class="flex items-center justify-center gap-1.5 mt-1">
                        <div class="w-1.5 h-1.5 rounded-full bg-primary"></div>
                        <p class="text-xs text-gray-500">Meja {{ $table->name }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Payment Status Banner --}}
        @if($sale->payment_status === 'paid' || $sale->status === 'completed')
            <div class="mx-4 mt-4 bg-green-50 border border-green-200 rounded-2xl p-4 pulse-success">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-green-500 flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-semibold text-green-800">Pembayaran Berhasil!</p>
                        <p class="text-xs text-green-600">Pesanan Anda sedang diproses</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Order Summary --}}
        <div class="px-4 py-6">
            <div class="bg-gray-50 rounded-2xl p-4 mb-6">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Ringkasan Pesanan</h3>
                <div class="space-y-2">
                    @foreach($sale->items as $item)
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">{{ $item->product_name }} x{{ $item->quantity }}</span>
                            <span class="font-medium">Rp {{ number_format($item->total, 0, ',', '.') }}</span>
                        </div>
                    @endforeach
                </div>

                {{-- Price Breakdown --}}
                <div class="border-t border-gray-200 mt-3 pt-3 space-y-1.5">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Subtotal</span>
                        <span class="font-medium">Rp {{ number_format($sale->subtotal, 0, ',', '.') }}</span>
                    </div>
                    @if($sale->tax > 0)
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">PPN</span>
                            <span class="font-medium text-green-600">+ Rp {{ number_format($sale->tax, 0, ',', '.') }}</span>
                        </div>
                    @endif
                    @if($sale->paywuz_fee > 0)
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">
                                Biaya Layanan
                                @if($sale->paywuz_fee_by_merchant)
                                    <span class="text-xs text-green-600">(ditanggung toko)</span>
                                @else
                                    <span class="text-xs text-gray-400">(ditanggung Anda)</span>
                                @endif
                            </span>
                            <span class="font-medium">+ Rp {{ number_format($sale->paywuz_fee, 0, ',', '.') }}</span>
                        </div>
                    @endif
                </div>

                <div class="border-t border-gray-200 mt-3 pt-3 flex justify-between">
                    <span class="font-semibold text-gray-900">Total</span>
                    <span class="font-bold text-xl text-primary">Rp {{ number_format($sale->grand_total, 0, ',', '.') }}</span>
                </div>
            </div>

            {{-- QRIS Payment via Paywuz --}}
            @if($sale->payment_method === 'qris')
                <div class="text-center">
                    <div class="bg-white border-2 border-gray-100 rounded-2xl p-6 mb-6">
                        <div class="flex items-center justify-center gap-2 mb-4">
                            <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M3 3h7v7H3V3zm11 0h7v7h-7V3zM3 14h7v7H3v-7zm14.5-3.5a2.5 2.5 0 100-5 2.5 2.5 0 000 5z"/>
                            </svg>
                            <p class="text-sm font-medium text-gray-700">QRIS Payment</p>
                        </div>
                        <p class="text-xs text-gray-500 mb-4">Scan QR code berikut dengan aplikasi pembayaran</p>

                        {{-- QR Code from Paywuz or Generate --}}
                        @if($qrData)
                            <div class="bg-white rounded-2xl p-4 inline-block shadow-inner mb-4">
                                <img src="{{ $qrData }}" alt="QRIS Payment" class="w-56 h-56 mx-auto">
                            </div>
                        @elseif($sale->paywuz_transaction_id)
                            <div class="bg-white rounded-2xl p-4 inline-block shadow-inner mb-4">
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data={{ urlencode($qrData ?? 'PAY' . $sale->invoice_number) }}" alt="QRIS Payment" class="w-56 h-56 mx-auto">
                            </div>
                        @else
                            <div class="bg-white rounded-2xl p-4 inline-block shadow-inner mb-4">
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=PAY-{{ $sale->invoice_number }}-{{ $sale->grand_total }}" alt="QRIS Payment" class="w-56 h-56 mx-auto">
                            </div>
                        @endif

                        {{-- Paywuz Transaction ID --}}
                        @if($sale->paywuz_transaction_id)
                            <div class="bg-blue-50 rounded-xl p-3 mb-4">
                                <p class="text-xs text-blue-600">Transaction ID</p>
                                <p class="font-mono text-sm text-blue-800">{{ $sale->paywuz_transaction_id }}</p>
                            </div>
                        @endif

                        <p class="text-xs text-gray-500 mb-2">atau bayar dengan nominal</p>

                        {{-- Amount --}}
                        <div class="bg-green-50 border border-green-200 rounded-xl p-3">
                            <p class="text-xs text-green-600 mb-1">Jumlah Bayar</p>
                            <p class="font-bold text-2xl text-green-600">Rp {{ number_format($sale->grand_total, 0, ',', '.') }}</p>
                        </div>
                    </div>

                    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-6">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-amber-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            <div>
                                <p class="font-medium text-amber-800">Penting!</p>
                                <p class="text-sm text-amber-700">Setelah melakukan pembayaran, klik tombol "Sudah Bayar" untuk konfirmasi pesanan Anda.</p>
                            </div>
                        </div>
                    </div>

                    {{-- Auto-refresh status --}}
                    <div id="paymentStatus" class="text-xs text-gray-500 mb-4">
                        <span id="statusText">Menunggu pembayaran...</span>
                    </div>
                </div>
            @endif

            {{-- Transfer Payment --}}
            @if($sale->payment_method === 'transfer')
                <div class="text-center">
                    <div class="bg-white border-2 border-gray-100 rounded-2xl p-6 mb-6">
                        <p class="text-sm text-gray-500 mb-4">Transfer ke salah satu rekening berikut</p>

                        {{-- Bank Accounts --}}
                        <div class="space-y-3 text-left">
                            <div class="bg-gray-50 rounded-xl p-4">
                                <div class="flex items-center gap-3 mb-2">
                                    <div class="w-10 h-10 rounded-lg bg-blue-500 flex items-center justify-center">
                                        <span class="text-white font-bold text-sm">BCA</span>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900">Bank BCA</p>
                                        <p class="text-xs text-gray-500">a.n. {{ $tenant->name }}</p>
                                    </div>
                                </div>
                                <p class="font-mono text-lg text-gray-900">123-456-7890</p>
                            </div>

                            <div class="bg-gray-50 rounded-xl p-4">
                                <div class="flex items-center gap-3 mb-2">
                                    <div class="w-10 h-10 rounded-lg bg-red-500 flex items-center justify-center">
                                        <span class="text-white font-bold text-xs">MANDIRI</span>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900">Bank Mandiri</p>
                                        <p class="text-xs text-gray-500">a.n. {{ $tenant->name }}</p>
                                    </div>
                                </div>
                                <p class="font-mono text-lg text-gray-900">130-00-1234567-8</p>
                            </div>
                        </div>

                        <div class="mt-4 bg-blue-50 border border-blue-200 rounded-xl p-4">
                            <p class="text-xs text-blue-700"><strong>Jumlah transfer:</strong></p>
                            <p class="font-bold text-xl text-blue-600">Rp {{ number_format($sale->grand_total, 0, ',', '.') }}</p>
                            <p class="text-xs text-blue-600 mt-1">Wajib sesuai nominal untuk automatic verification</p>
                        </div>
                    </div>

                    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-6">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-amber-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            <div>
                                <p class="font-medium text-amber-800">Penting!</p>
                                <p class="text-sm text-amber-700">Setelah transfer, klik tombol "Sudah Bayar" untuk konfirmasi pesanan Anda.</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Virtual Account Payment --}}
            @if($sale->payment_method === 'va')
                <div class="text-center">
                    <div class="bg-white border-2 border-gray-100 rounded-2xl p-6 mb-6">
                        <div class="flex items-center justify-center gap-2 mb-4">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                            </div>
                            <p class="text-sm font-semibold text-gray-700">Virtual Account</p>
                        </div>

                        <p class="text-xs text-gray-500 mb-4">Lakukan pembayaran melalui Virtual Account</p>

                        {{-- VA Number --}}
                        @if($sale->paywuz_qr_url)
                            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border-2 border-blue-200 rounded-xl p-5 mb-4">
                                <p class="text-xs text-blue-600 mb-2 font-medium uppercase tracking-wider">Nomor Virtual Account</p>
                                <p class="font-mono text-2xl font-bold text-blue-700 tracking-wider">{{ $sale->paywuz_qr_url }}</p>
                            </div>
                        @else
                            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border-2 border-blue-200 rounded-xl p-5 mb-4">
                                <p class="text-xs text-blue-600 mb-2 font-medium uppercase tracking-wider">Nomor Virtual Account</p>
                                <p class="font-mono text-2xl font-bold text-blue-700 tracking-wider">88{{ substr($sale->invoice_number, -9) }}</p>
                            </div>
                        @endif

                        {{-- Amount --}}
                        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-4">
                            <p class="text-xs text-blue-600 mb-1">Jumlah Bayar</p>
                            <p class="font-bold text-3xl text-blue-600">Rp {{ number_format($sale->grand_total, 0, ',', '.') }}</p>
                        </div>

                        {{-- Transaction ID --}}
                        @if($sale->paywuz_transaction_id)
                            <div class="bg-gray-50 rounded-xl p-3 mb-4">
                                <p class="text-xs text-gray-500">Transaction ID</p>
                                <p class="font-mono text-sm text-gray-700">{{ $sale->paywuz_transaction_id }}</p>
                            </div>
                        @endif

                        {{-- Instructions --}}
                        <div class="bg-gray-50 rounded-xl p-4 text-left">
                            <p class="text-xs font-semibold text-gray-900 mb-2">Cara Pembayaran:</p>
                            <ol class="text-xs text-gray-600 space-y-1 list-decimal list-inside">
                                <li>Buka aplikasi mobile banking atau ATM</li>
                                <li>Pilih menu "Transfer" / "Pembayaran"</li>
                                <li>Pilih bank yang sesuai</li>
                                <li>Masukkan nomor Virtual Account di atas</li>
                                <li>Ikuti instruksi untuk menyelesaikan pembayaran</li>
                            </ol>
                        </div>
                    </div>

                    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-6">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-amber-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            <div>
                                <p class="font-medium text-amber-800">Penting!</p>
                                <p class="text-sm text-amber-700">Setelah melakukan pembayaran, klik tombol "Sudah Bayar" untuk konfirmasi pesanan Anda.</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Counter Payment (Bayar di Kasir) --}}
            @if($sale->payment_method === 'counter')
                <div class="text-center">
                    <div class="bg-white border-2 border-amber-200 rounded-2xl p-6 mb-6">
                        <div class="w-16 h-16 mx-auto rounded-full bg-amber-100 flex items-center justify-center mb-4">
                            <x-heroicon-s-credit-card class="w-8 h-8 text-amber-500"/>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 mb-2">Bayar di Kasir</h3>
                        <p class="text-sm text-gray-500 mb-4">Tunjukkan invoice ini ke kasir untuk pembayaran</p>

                        {{-- Invoice Number --}}
                        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-4">
                            <p class="text-xs text-amber-600 mb-1">Invoice Number</p>
                            <p class="font-mono text-xl font-bold text-amber-700">{{ $sale->invoice_number }}</p>
                        </div>

                        {{-- Amount --}}
                        <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 mb-4">
                            <p class="text-xs text-gray-500 mb-1">Total Pembayaran</p>
                            <p class="font-bold text-3xl text-gray-900">Rp {{ number_format($sale->grand_total, 0, ',', '.') }}</p>
                        </div>

                        {{-- Info Box --}}
                        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-left">
                            <div class="flex items-start gap-3">
                                <x-heroicon-s-information-circle class="w-5 h-5 text-blue-500 mt-0.5 shrink-0"/>
                                <div>
                                    <p class="font-medium text-blue-800 text-sm">Informasi</p>
                                    <p class="text-xs text-blue-700 mt-1">Silakan menuju ke kasir dengan membawa invoice ini. Anda dapat membayar dengan cash atau QR.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Confirm Button (Fixed Bottom) --}}
        {{-- Hide for counter payment - staff will mark as paid from POS --}}
        @if($sale->payment_status !== 'paid' && $sale->status !== 'completed' && $sale->payment_method !== 'counter')
        <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 shadow-lg z-50">
            <div class="max-w-md mx-auto p-4">
                <form action="{{ route('customer.order.payment.confirm', [$tenant->getRouteKey(), $table->id, $sale->id]) }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full py-4 bg-gradient-to-r from-green-500 to-green-600 text-white font-bold rounded-xl hover:opacity-90 transition-opacity text-lg">
                        <span class="flex items-center justify-center gap-2">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Sudah Bayar
                        </span>
                    </button>
                </form>
            </div>
        </div>
        @endif

        {{-- Already Paid Button --}}
        @if($sale->payment_status === 'paid' || $sale->status === 'completed')
        <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 shadow-lg z-50">
            <div class="max-w-md mx-auto p-4">
                <a href="{{ route('customer.order.success', [$tenant->getRouteKey(), $table->id, $sale->id]) }}" class="block w-full py-4 bg-gradient-to-r from-green-500 to-green-600 text-white font-bold rounded-xl hover:opacity-90 transition-opacity text-lg text-center">
                    <span class="flex items-center justify-center gap-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Lihat Struk
                    </span>
                </a>
            </div>
        </div>
        @endif

        {{-- Spacer for fixed button --}}
        <div class="h-24"></div>

    </div>

    {{-- Auto-check payment status --}}
    @if($sale->payment_method === 'qris' && $sale->paywuz_transaction_id && $sale->payment_status !== 'paid')
    <script>
        // Auto-refresh payment status every 3 seconds
        setInterval(function() {
            fetch('{{ route('customer.order.payment.confirm', [$tenant->slug ?? $tenant->id, $table->id, $sale->id]) }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
            })
            .then(response => response.json())
            .then(data => {
                // If payment confirmed, reload page
                if (data.success || data.redirect) {
                    window.location.reload();
                }
            })
            .catch(error => console.log('Checking payment status...'));
        }, 3000);

        // Update status text
        let seconds = 0;
        setInterval(function() {
            seconds++;
            const statusText = document.getElementById('statusText');
            if (statusText) {
                statusText.textContent = 'Menunggu pembayaran... (' + seconds + 's)';
            }
        }, 1000);

        // Reload on visibility change (when customer comes back to tab)
        document.addEventListener('visibilitychange', function() {
            if (document.visibilityState === 'visible') {
                fetch('{{ route('customer.order.payment.confirm', [$tenant->slug ?? $tenant->id, $table->id, $sale->id]) }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success || data.redirect) {
                        window.location.reload();
                    }
                })
                .catch(error => {});
            }
        });
    </script>
    @endif
</body>
</html>
