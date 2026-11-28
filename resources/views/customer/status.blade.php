<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Pesanan - {{ $tenant->name }}</title>
    @if(! in_array($stage, ['done', 'cancelled']))
        <meta http-equiv="refresh" content="10">
    @endif
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-gray-50 min-h-screen">
    @php
        $headline = [
            'unpaid' => 'Menunggu pembayaran',
            'cooking' => 'Sedang disiapkan',
            'ready' => 'Siap disajikan',
            'done' => 'Pesanan selesai',
            'cancelled' => 'Pesanan dibatalkan',
        ][$stage];
        $steps = [
            ['Dibayar', in_array($stage, ['cooking', 'ready', 'done'])],
            ['Disiapkan dapur', in_array($stage, ['ready', 'done'])],
            ['Diantar ke meja', $stage === 'done'],
        ];
    @endphp
    <div class="max-w-md mx-auto bg-white min-h-screen flex flex-col">
        <div class="flex-1 px-6 py-10">
            <p class="text-sm text-gray-500">{{ $tenant->name }} &middot; {{ $table->name }}</p>
            <h1 class="mt-1 text-2xl font-bold {{ $stage === 'cancelled' ? 'text-red-600' : 'text-gray-900' }}">{{ $headline }}</h1>
            <p class="mt-1 text-sm text-gray-500">No. Order {{ $sale->invoice_number }}</p>

            @if($stage === 'unpaid')
                <div class="mt-6 rounded-xl bg-amber-50 border border-amber-200 p-4">
                    <p class="text-sm text-amber-800">Pesanan baru diproses setelah pembayaran diterima.</p>
                    <a href="{{ route('customer.order.payment', [$tenant->getRouteKey(), $table->id, $sale->id]) }}" class="mt-3 block w-full py-3 bg-amber-500 text-white text-center rounded-lg font-semibold">Bayar Sekarang</a>
                </div>
            @elseif($stage === 'cooking' && $sale->payment_status !== 'paid')
                <div class="mt-6 rounded-xl bg-gray-50 border border-gray-200 p-4">
                    <p class="text-sm text-gray-700">Bayar di kasir saat pesanan selesai.</p>
                </div>
            @endif

            @if($stage !== 'cancelled')
                <ol class="mt-8 space-y-4">
                    @foreach($steps as [$label, $reached])
                        <li class="flex items-center gap-3">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full {{ $reached ? 'bg-green-500 text-white' : 'border-2 border-gray-300 text-gray-400' }} text-sm font-bold">
                                @if($reached)
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                @else
                                    {{ $loop->iteration }}
                                @endif
                            </span>
                            <span class="text-sm font-medium {{ $reached ? 'text-gray-900' : 'text-gray-500' }}">{{ $label }}</span>
                        </li>
                    @endforeach
                </ol>
            @endif

            <div class="mt-8 rounded-2xl border border-gray-100 p-4">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Item Pesanan</h3>
                <div class="space-y-2">
                    @foreach($sale->items as $item)
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">{{ $item->product_name }}</span>
                            <span class="text-gray-500">x{{ (int) $item->quantity }}</span>
                        </div>
                    @endforeach
                </div>
                <div class="mt-3 flex justify-between border-t border-gray-100 pt-3 text-sm">
                    <span class="font-semibold text-gray-700">Total</span>
                    <span class="font-bold text-gray-900">Rp {{ number_format($sale->grand_total, 0, ',', '.') }}</span>
                </div>
            </div>

            @if(! in_array($stage, ['done', 'cancelled']))
                <p class="mt-6 text-center text-xs text-gray-400">Halaman ini diperbarui otomatis tiap 10 detik.</p>
            @endif
        </div>

        <div class="p-4 border-t border-gray-100">
            <a href="{{ route('customer.order', [$tenant->getRouteKey(), $table->id]) }}" class="block w-full py-4 bg-gray-100 text-gray-700 font-bold rounded-xl text-center">Pesan Lagi</a>
        </div>
    </div>
</body>
</html>
