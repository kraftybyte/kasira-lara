<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran - {{ $tenant->name }} - Meja {{ $table->name }}</title>
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
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="max-w-md mx-auto bg-white min-h-screen">

        {{-- Header --}}
        <div class="sticky top-0 z-50 bg-white border-b border-gray-100">
            <div class="px-4 py-4">
                <div class="text-center">
                    <p class="text-sm text-gray-500">Order {{ $sale->invoice_number }}</p>
                    <h1 class="text-xl font-bold text-gray-900">Pembayaran</h1>
                    <p class="text-sm text-gray-500">Meja {{ $table->name }}</p>
                </div>
            </div>
        </div>

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
                <div class="border-t border-gray-200 mt-3 pt-3 flex justify-between">
                    <span class="font-semibold text-gray-900">Total</span>
                    <span class="font-bold text-xl text-primary">Rp {{ number_format($sale->grand_total, 0, ',', '.') }}</span>
                </div>
            </div>

            {{-- QRIS Payment --}}
            @if($sale->payment_method === 'qris')
                <div class="text-center">
                    <div class="bg-white border-2 border-gray-100 rounded-2xl p-6 mb-6">
                        <p class="text-sm text-gray-500 mb-4">Scan QR code berikut dengan aplikasi pembayaran</p>

                        {{-- QR Code --}}
                        <div class="bg-white rounded-2xl p-4 inline-block shadow-inner mb-4">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=PAY-{{ $sale->invoice_number }}-{{ $sale->grand_total }}" alt="QRIS Payment" class="w-56 h-56 mx-auto">
                        </div>

                        <p class="text-sm text-gray-500 mb-4">atau gunakan kode di bawah</p>

                        {{-- Payment Code --}}
                        <div class="bg-gray-50 rounded-xl p-3">
                            <p class="text-xs text-gray-500 mb-1">Kode Pembayaran</p>
                            <p class="font-mono font-bold text-lg text-gray-900 tracking-wider">PAY{{ str_replace('#', '', $sale->invoice_number) }}{{ $sale->grand_total }}</p>
                        </div>
                    </div>

                    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-6">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-amber-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            <div>
                                <p class="font-medium text-amber-800">Penting!</p>
                                <p class="text-sm text-amber-700">Setelah melakukan pembayaran, klik tombol "Sudah Bayar" di bawah untuk mengkonfirmasi pesanan Anda.</p>
                            </div>
                        </div>
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
                                <p class="text-sm text-amber-700">Setelah transfer, klik tombol "Sudah Bayar" di bawah untuk mengkonfirmasi pesanan Anda.</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Confirm Button (Fixed Bottom) --}}
        <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 shadow-lg z-50">
            <div class="max-w-md mx-auto p-4">
                <form action="{{ route('customer.order.payment.confirm', [$tenant->slug ?? $tenant->id, $table->id, $sale->id]) }}" method="POST">
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

        {{-- Spacer for fixed button --}}
        <div class="h-24"></div>

    </div>
</body>
</html>
