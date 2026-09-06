<div>
    @if($isOpen)
        <div
            x-data="{ show: @entangle('isOpen') }"
            x-show="show"
            x-on:keydown.escape.window="show = false"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
        >
            {{-- Backdrop --}}
            <div
                x-show="show"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="absolute inset-0 bg-gray-800/80 backdrop-blur-sm"
                x-on:click="show = false"
            ></div>

            {{-- Modal --}}
            <div
                x-show="show"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="relative w-full max-w-sm bg-white rounded-2xl shadow-[0_25px_50px_-12px_rgba(0,0,0,0.25)] overflow-hidden"
            >
                {{-- Header --}}
                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                    <div class="flex items-center gap-3">
                        <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-red-500 text-white">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-gray-900">Meja {{ $selectedTableName }}</h3>
                            <p class="text-xs text-gray-500">{{ count($orders) }} pesanan</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        wire:click="closeModal"
                        class="p-1.5 rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Content --}}
                <div class="overflow-y-auto" style="max-height: 50vh;">
                    <div class="p-3 space-y-2">
                        @forelse($orders as $order)
                            <div class="border border-gray-200 rounded-lg overflow-hidden">
                                {{-- Header --}}
                                <div class="flex items-center justify-between px-3 py-2 bg-gray-50 border-b border-gray-200">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-semibold text-gray-900">{{ $order['invoice_number'] }}</span>
                                        @if($order['has_duration'])
                                            <span class="px-1.5 py-0.5 text-[9px] font-bold rounded-full bg-amber-200 text-amber-800">⏱ DURASI</span>
                                        @elseif($order['served_at'])
                                            <span class="px-1.5 py-0.5 text-[9px] font-bold rounded-full bg-emerald-200 text-emerald-800">✓ SUDAH</span>
                                        @elseif($order['status'] === 'completed')
                                            <span class="px-1.5 py-0.5 text-[9px] font-bold rounded-full bg-teal-200 text-teal-800">○ SIAP</span>
                                        @else
                                            <span class="px-1.5 py-0.5 text-[9px] font-bold rounded-full bg-amber-200 text-amber-800">◐ TUNDA</span>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-2">
                                        @if($order['has_duration'] && isset($order['created_at']))
                                            @php
                                                $elapsed = \Carbon\Carbon::parse($order['created_at'])->diffInMinutes(now());
                                                $hours = floor($elapsed / 60);
                                                $mins = $elapsed % 60;
                                                $elapsedStr = $hours > 0 ? "{$hours}j {$mins}m" : "{$mins}m";
                                            @endphp
                                            <span class="text-[10px] font-medium text-amber-600">{{ $elapsedStr }}</span>
                                        @endif
                                        <span class="px-1.5 py-0.5 text-[9px] font-bold rounded-full
                                            @if($order['payment_method'] === 'qris') bg-sky-200 text-sky-800
                                            @elseif($order['payment_method'] === 'transfer') bg-purple-200 text-purple-800
                                            @elseif($order['payment_method'] === 'cash') bg-green-200 text-green-800
                                            @else bg-gray-200 text-gray-800 @endif">
                                            {{ strtoupper($order['payment_method']) }}
                                        </span>
                                    </div>
                                </div>

                                {{-- Items --}}
                                <div class="px-3 py-2 space-y-1">
                                    @foreach($order['items'] as $item)
                                        <div class="flex items-center justify-between text-xs">
                                            <div class="flex items-center gap-2">
                                                <span class="flex items-center justify-center w-4 h-4 text-[9px] font-bold rounded bg-gray-100 text-gray-600">{{ (int) $item['quantity'] }}</span>
                                                <span class="text-gray-700">{{ $item['product_name'] }}</span>
                                            </div>
                                            <span class="text-gray-700">Rp {{ number_format($item['subtotal'], 0, ',', '.') }}</span>
                                        </div>
                                    @endforeach
                                </div>

                                {{-- Footer --}}
                                <div class="px-3 py-3 border-t border-gray-200 bg-gray-50">
                                    <div class="flex items-center justify-between mb-3">
                                        <span class="text-xs text-gray-500">Total</span>
                                        <span class="text-sm font-bold text-gray-900">Rp {{ number_format($order['grand_total'], 0, ',', '.') }}</span>
                                    </div>

                                    @if(!$order['has_duration'])
                                        {{-- Served button only for non-duration products --}}
                                        @if($order['status'] === 'completed')
                                            @if($order['served_at'])
                                                <button wire:click="markAsUnserved({{ $order['id'] }})" class="w-full py-1.5 text-xs font-medium rounded-lg bg-gray-200 text-gray-600 hover:bg-gray-300 transition-colors">Batalkan</button>
                                            @else
                                                <button wire:click="markAsServed({{ $order['id'] }})" class="w-full py-1.5 text-xs font-medium rounded-lg bg-emerald-500 text-white hover:bg-emerald-600 transition-colors">Tandai Disajikan</button>
                                            @endif
                                        @else
                                            <div class="flex gap-2">
                                                @if($order['served_at'])
                                                    <button wire:click="markAsUnserved({{ $order['id'] }})" class="flex-1 py-1.5 text-xs font-medium rounded-lg bg-gray-200 text-gray-600 hover:bg-gray-300 transition-colors">Batalkan</button>
                                                @else
                                                    <button wire:click="markAsServed({{ $order['id'] }})" class="flex-1 py-1.5 text-xs font-medium rounded-lg bg-emerald-500 text-white hover:bg-emerald-600 transition-colors">Disajikan</button>
                                                @endif
                                                <a href="/admin/{{ $selectedTenantId }}/pos?table={{ $selectedTableId }}" class="flex-1 py-1.5 text-xs font-medium rounded-lg bg-amber-500 text-white hover:bg-amber-600 transition-colors text-center">Bayar</a>
                                            </div>
                                        @endif
                                    @else
                                        {{-- Duration products - no served button, only pay --}}
                                        @if($order['status'] === 'completed')
                                            <span class="block py-1.5 text-xs font-medium text-center text-emerald-600">Selesai</span>
                                        @else
                                            <a href="/admin/{{ $selectedTenantId }}/pos?table={{ $selectedTableId }}" class="block py-1.5 text-xs font-medium rounded-lg bg-amber-500 text-white hover:bg-amber-600 transition-colors text-center">Bayar</a>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="py-8 text-center">
                                <p class="text-xs text-gray-500">Belum ada pesanan</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Footer --}}
                <div class="px-4 py-2.5 border-t border-gray-100">
                    <button type="button" wire:click="closeModal" class="w-full py-2 text-xs font-medium rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 transition-colors">Tutup</button>
                </div>
            </div>
        </div>
    @endif
</div>
