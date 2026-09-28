<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Berhasil - {{ $tenant->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
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

        /* Print Styles */
        @media print {
            body { background: white !important; }
            .no-print { display: none !important; }
            .print-only { display: block !important; }
            .min-h-screen { min-height: auto !important; }
        }
        .print-only { display: none; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="max-w-md mx-auto bg-white min-h-screen flex flex-col">

        {{-- Success Content --}}
        <div class="flex-1 flex flex-col items-center justify-center px-6 py-12">
            {{-- Success Animation --}}
            <div class="relative mb-8">
                <div class="w-28 h-28 rounded-full bg-green-100 flex items-center justify-center animate-bounce">
                    <svg class="w-16 h-16 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <div class="absolute -top-2 -right-2 w-10 h-10 rounded-full bg-green-500 flex items-center justify-center animate-ping opacity-25"></div>
            </div>

            <h1 class="text-2xl font-bold text-gray-900 mb-2">Pesanan Berhasil!</h1>
            <p class="text-gray-500 text-center mb-8">Terima kasih telah memesan. Pesanan Anda sedang diproses.</p>

            {{-- Order Info --}}
            <div class="w-full bg-gray-50 rounded-2xl p-6 mb-6">
                <div class="text-center mb-4">
                    <p class="text-sm text-gray-500">No. Order</p>
                    <p class="text-xl font-bold text-gray-900">{{ $sale->invoice_number }}</p>
                </div>

                <div class="border-t border-gray-200 pt-4 space-y-3">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Meja</span>
                        <span class="font-medium text-gray-900">{{ $table->name }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Metode Bayar</span>
                        <span class="font-medium text-gray-900">{{ strtoupper($sale->payment_method) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Total</span>
                        <span class="font-bold text-lg text-primary">Rp {{ number_format($sale->grand_total, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            {{-- Items Summary --}}
            <div class="w-full bg-white border border-gray-100 rounded-2xl p-4 mb-8">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Item Pesanan</h3>
                <div class="space-y-2">
                    @foreach($sale->items as $item)
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">{{ $item->product_name }}</span>
                            <span class="text-gray-500">x{{ $item->quantity }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Info --}}
            <div class="text-center">
                <p class="text-sm text-gray-500">Pesanan Anda akan segera diproses oleh kasir. Mohon tunggu sebentar.</p>
            </div>
        </div>

        {{-- Bottom Actions --}}
        <div class="p-4 border-t border-gray-100 space-y-3">
            <button onclick="window.print()" class="w-full py-4 bg-gray-100 text-gray-700 font-bold rounded-xl text-center hover:bg-gray-200 transition-colors flex items-center justify-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Cetak Struk
            </button>
            <a href="{{ url('/order/' . $tenant->getRouteKey() . '/' . $table->id) }}" class="block w-full py-4 bg-linear-to-r from-primary to-secondary text-white font-bold rounded-xl text-center hover:opacity-90 transition-opacity">
                Pesan Lagi
            </a>
        </div>

    </div>

    {{-- Printable Invoice --}}
    <div class="print-only p-4">
        <div class="text-center mb-6">
            <h1 class="text-xl font-bold">{{ $tenant->name }}</h1>
            <p class="text-sm text-gray-600">{{ $tenant->address ?? '' }}</p>
        </div>

        <div class="border-t border-b border-dashed border-gray-400 py-4 mb-4">
            <div class="flex justify-between mb-2">
                <span class="text-sm">No. Invoice:</span>
                <span class="text-sm font-bold">{{ $sale->invoice_number }}</span>
            </div>
            <div class="flex justify-between mb-2">
                <span class="text-sm">Tanggal:</span>
                <span class="text-sm">{{ $sale->created_at->format('d/m/Y H:i') }}</span>
            </div>
            <div class="flex justify-between mb-2">
                <span class="text-sm">Meja:</span>
                <span class="text-sm">{{ $table->name }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-sm">Pembayaran:</span>
                <span class="text-sm">{{ strtoupper($sale->payment_method) }}</span>
            </div>
        </div>

        <table class="w-full mb-4">
            <thead>
                <tr class="border-b border-dashed border-gray-400">
                    <th class="text-left py-2 text-sm font-semibold">Item</th>
                    <th class="text-center py-2 text-sm font-semibold">Qty</th>
                    <th class="text-right py-2 text-sm font-semibold">Harga</th>
                    <th class="text-right py-2 text-sm font-semibold">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sale->items as $item)
                <tr class="border-b border-dashed border-gray-300">
                    <td class="py-2 text-sm">{{ $item->product_name }}</td>
                    <td class="py-2 text-sm text-center">{{ $item->quantity }}</td>
                    <td class="py-2 text-sm text-right">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                    <td class="py-2 text-sm text-right">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="border-t border-dashed border-gray-400 pt-4">
            <div class="flex justify-between mb-2">
                <span class="text-sm font-semibold">Subtotal:</span>
                <span class="text-sm">Rp {{ number_format($sale->subtotal, 0, ',', '.') }}</span>
            </div>
            @if($sale->discount > 0)
            <div class="flex justify-between mb-2">
                <span class="text-sm">Diskon:</span>
                <span class="text-sm">- Rp {{ number_format($sale->discount, 0, ',', '.') }}</span>
            </div>
            @endif
            @if($sale->tax > 0)
            <div class="flex justify-between mb-2">
                <span class="text-sm">Pajak:</span>
                <span class="text-sm">Rp {{ number_format($sale->tax, 0, ',', '.') }}</span>
            </div>
            @endif
            <div class="flex justify-between py-2 border-t border-dashed border-gray-400">
                <span class="text-lg font-bold">TOTAL:</span>
                <span class="text-lg font-bold">Rp {{ number_format($sale->grand_total, 0, ',', '.') }}</span>
            </div>
            @if($sale->payment_method !== 'qris' && $sale->payment_method !== 'transfer')
            <div class="flex justify-between mb-2">
                <span class="text-sm">Tunai:</span>
                <span class="text-sm">Rp {{ number_format($sale->paid_amount, 0, ',', '.') }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-sm">Kembalian:</span>
                <span class="text-sm">Rp {{ number_format($sale->change_amount, 0, ',', '.') }}</span>
            </div>
            @endif
        </div>

        <div class="text-center mt-8">
            <p class="text-sm text-gray-600">Terima kasih atas kunjungan Anda!</p>
        </div>
    </div>
</body>
</html>
