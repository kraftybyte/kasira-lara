<div>
    @if($isOpen)
        <!-- Overlay -->
        <div
            x-data="{ show: @entangle('isOpen') }"
            x-show="show"
            x-on:keydown.escape.window="show = false"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4"
        >
            <!-- Modal -->
            <div
                x-show="show"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="relative w-full max-w-lg rounded-2xl bg-white shadow-2xl overflow-hidden"
            >
                <!-- Header -->
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 bg-gradient-to-r from-red-500 to-red-600">
                    <div class="flex items-center gap-3">
                        <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-white/20 text-white">
                            @if($selectedTableId)
                                <x-heroicon-o-archive-box class="w-5 h-5"/>
                            @else
                                <x-heroicon-o-list-bullet class="w-5 h-5"/>
                            @endif
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-white">
                                {{ $selectedTableId ? 'Meja ' . $selectedTableName : 'Semua Pesanan' }}
                            </h3>
                            <p class="text-xs text-red-100">{{ count($orders) }} pesanan</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        wire:click="closeModal"
                        class="p-2 rounded-lg text-white/80 hover:bg-white/20 transition-colors"
                    >
                        <x-heroicon-o-x-mark class="w-5 h-5"/>
                    </button>
                </div>

                <!-- Bulk Pay Button -->
                @php
                    $pendingCounterOrders = collect($orders)->where('payment_method', 'counter')->where('status', 'pending');
                    $totalCounterAmount = $pendingCounterOrders->sum('grand_total');
                @endphp
                @if($pendingCounterOrders->count() > 0)
                    <div class="px-5 py-3 bg-amber-50 border-b border-amber-100">
                        <button
                            wire:click="$toggle('showBulkPayConfirm')"
                            class="w-full py-3 px-4 bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold rounded-xl flex items-center justify-center gap-2 transition-colors shadow-lg shadow-amber-500/30"
                        >
                            <x-heroicon-s-banknotes class="w-5 h-5"/>
                            <span>Bayar Semua Counter ({{ $pendingCounterOrders->count() }})</span>
                            <span class="ml-1 px-2.5 py-1 bg-amber-600 rounded-lg text-xs font-bold">
                                Rp {{ number_format($totalCounterAmount, 0, ',', '.') }}
                            </span>
                        </button>
                    </div>
                @endif

                <!-- Content (Scrollable) -->
                <div class="overflow-y-auto" style="max-height: 60vh;">
                    <div class="p-4 space-y-4">
                        @if(isset($orders[0]['orders']) && isset($orders[0]['count']))
                            {{-- Grouped by Table View --}}
                            @foreach($orders as $group)
                                <div class="rounded-xl border border-gray-200 overflow-hidden shadow-sm">
                                    {{-- Table Header --}}
                                    <div class="flex items-center justify-between px-4 py-3 bg-gradient-to-r from-purple-500 to-purple-600">
                                        <div class="flex items-center gap-3">
                                            <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-white/20 text-white">
                                                <x-heroicon-o-archive-box class="w-4 h-4"/>
                                            </div>
                                            <div>
                                                <span class="text-sm font-bold text-white">{{ $group['table_name'] }}</span>
                                                <span class="ml-2 px-2 py-0.5 text-[10px] font-semibold rounded-full bg-white/20 text-white">
                                                    {{ $group['count'] }} pesanan
                                                </span>
                                            </div>
                                        </div>
                                        <span class="text-base font-bold text-white">
                                            Rp {{ number_format($group['total'], 0, ',', '.') }}
                                        </span>
                                    </div>

                                    {{-- Orders --}}
                                    @foreach($group['orders'] as $order)
                                        <div class="border-t border-gray-100">
                                            <div class="flex items-center justify-between px-4 py-3 bg-gray-50">
                                                <div class="flex items-center gap-2">
                                                    <span class="text-xs font-semibold text-gray-900">{{ $order['invoice_number'] }}</span>
                                                    @php
                                                        $statusConfig = match($order['display_status']) {
                                                            'served' => ['bg' => 'bg-blue-100 text-blue-700', 'icon' => '✓'],
                                                            'ready' => ['bg' => 'bg-emerald-100 text-emerald-700', 'icon' => '🍽'],
                                                            'pending' => ['bg' => 'bg-gray-100 text-gray-600', 'icon' => '⏳'],
                                                            default => ['bg' => 'bg-gray-100 text-gray-600', 'icon' => '⏳'],
                                                        };
                                                    @endphp
                                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-full {{ $statusConfig['bg'] }}">
                                                        {{ $statusConfig['icon'] }}
                                                    </span>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    @php
                                                        $methodConfig = match($order['payment_method']) {
                                                            'qris' => 'bg-sky-100 text-sky-700',
                                                            'transfer' => 'bg-purple-100 text-purple-700',
                                                            'counter' => 'bg-amber-100 text-amber-700',
                                                            'cash' => 'bg-green-100 text-green-700',
                                                            default => 'bg-gray-100 text-gray-600',
                                                        };
                                                    @endphp
                                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-full {{ $methodConfig }}">
                                                        {{ strtoupper($order['payment_method']) }}
                                                    </span>
                                                    <span class="text-sm font-bold text-gray-900">Rp {{ number_format($order['grand_total'], 0, ',', '.') }}</span>
                                                </div>
                                            </div>

                                            {{-- Items Summary --}}
                                            @php
                                                $itemSummary = collect($order['items'])->take(5)->map(fn($i) => (int)$i['quantity'] . 'x ' . $i['product_name'])->implode(', ');
                                                $moreItems = count($order['items']) - 5;
                                            @endphp
                                            <div class="px-4 py-2.5 bg-white border-t border-gray-100">
                                                <p class="text-xs text-gray-600 leading-relaxed">
                                                    {{ $itemSummary }}
                                                    @if($moreItems > 0)
                                                        <span class="text-gray-400">+{{ $moreItems }} item</span>
                                                    @endif
                                                </p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        @else
                            {{-- Individual Order View --}}
                            @forelse($orders as $order)
                                <div class="rounded-xl border border-gray-200 overflow-hidden shadow-sm">
                                    {{-- Order Header --}}
                                    <div class="flex items-center justify-between px-4 py-3 bg-gray-50 border-b border-gray-200">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            @if(!$selectedTableId && isset($order['table_name']))
                                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-purple-100 text-purple-700">
                                                    {{ $order['table_name'] }}
                                                </span>
                                            @endif
                                            <span class="text-xs font-semibold text-gray-900">{{ $order['invoice_number'] ?? 'N/A' }}</span>

                                            @if($order['has_duration'])
                                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-amber-100 text-amber-700">⏱ DURASI</span>
                                            @else
                                                @php
                                                    $statusConfig = match($order['display_status']) {
                                                        'served' => ['bg' => 'bg-blue-100 text-blue-700', 'text' => '✓ DISAJIKAN'],
                                                        'ready' => ['bg' => 'bg-emerald-100 text-emerald-700', 'text' => '🍽 SIAP'],
                                                        'preparing' => ['bg' => 'bg-orange-100 text-orange-700', 'text' => '🔥 PROSES'],
                                                        'pending' => ['bg' => 'bg-gray-100 text-gray-600', 'text' => '⏳ MENUNGGU'],
                                                        default => ['bg' => 'bg-gray-100 text-gray-600', 'text' => '⏳'],
                                                    };
                                                @endphp
                                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full {{ $statusConfig['bg'] }}">
                                                    {{ $statusConfig['text'] }}
                                                </span>
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
                                                <span class="px-2 py-0.5 text-[10px] font-semibold rounded-full bg-amber-100 text-amber-700">
                                                    {{ $elapsedStr }}
                                                </span>
                                            @endif
                                            @php
                                                $methodConfig = match($order['payment_method']) {
                                                    'qris' => 'bg-sky-100 text-sky-700',
                                                    'transfer' => 'bg-purple-100 text-purple-700',
                                                    'counter' => 'bg-amber-100 text-amber-700',
                                                    'cash' => 'bg-green-100 text-green-700',
                                                    default => 'bg-gray-100 text-gray-600',
                                                };
                                            @endphp
                                            <span class="px-2 py-0.5 text-[10px] font-bold rounded-full {{ $methodConfig }}">
                                                {{ strtoupper($order['payment_method']) }}
                                            </span>
                                        </div>
                                    </div>

                                    {{-- Order Notes --}}
                                    @if(!empty($order['notes']))
                                        <div class="px-4 py-2.5 bg-amber-50 border-b border-amber-100">
                                            <div class="flex items-start gap-2 text-xs text-amber-700">
                                                <x-heroicon-o-chat-bubble-left class="w-4 h-4 shrink-0 mt-0.5"/>
                                                <span class="font-medium">Catatan:</span>
                                                <span>{{ $order['notes'] }}</span>
                                            </div>
                                        </div>
                                    @endif

                                    {{-- Items --}}
                                    <div class="p-4 bg-white">
                                        @php
                                            $itemSummary = collect($order['items'])->take(4)->map(fn($i) => (int)$i['quantity'] . 'x ' . $i['product_name'])->implode(', ');
                                            $moreItems = count($order['items']) - 4;
                                        @endphp
                                        <div class="rounded-lg bg-gray-50 p-3 mb-3">
                                            <p class="text-xs text-gray-600 leading-relaxed">
                                                {{ $itemSummary }}
                                                @if($moreItems > 0)
                                                    <span class="text-gray-400">+{{ $moreItems }} item</span>
                                                @endif
                                            </p>
                                        </div>

                                        <div class="space-y-2">
                                            @foreach($order['items'] as $item)
                                                <div class="flex items-center justify-between text-sm">
                                                    <div class="flex items-center gap-2">
                                                        <span class="flex items-center justify-center w-6 h-6 text-xs font-bold rounded-lg bg-gray-100 text-gray-600">
                                                            {{ (int) $item['quantity'] }}
                                                        </span>
                                                        <span class="text-gray-700">{{ $item['product_name'] }}</span>
                                                    </div>
                                                    <span class="text-gray-700 font-medium">Rp {{ number_format($item['subtotal'], 0, ',', '.') }}</span>
                                                </div>
                                                @if(!empty($item['notes']))
                                                    <div class="ml-8 flex items-center gap-1 text-xs text-amber-600">
                                                        <x-heroicon-o-chat-bubble-left class="w-3 h-3"/>
                                                        <span>{{ $item['notes'] }}</span>
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>

                                    {{-- Footer Actions --}}
                                    <div class="px-4 py-3 bg-gray-50 border-t border-gray-200">
                                        <div class="flex items-center justify-between mb-3">
                                            <span class="text-xs text-gray-500">Total</span>
                                            <span class="text-base font-bold text-gray-900">Rp {{ number_format($order['grand_total'], 0, ',', '.') }}</span>
                                        </div>

                                        @if(!$order['has_duration'])
                                            @if($order['status'] === 'completed')
                                                @if($order['served_at'])
                                                    <button wire:click="markAsUnserved({{ $order['id'] }})" class="w-full py-2.5 text-xs font-medium rounded-lg bg-gray-200 text-gray-600 hover:bg-gray-300 transition-colors">
                                                        Batalkan
                                                    </button>
                                                @else
                                                    <button wire:click="markAsServed({{ $order['id'] }})" class="w-full py-2.5 text-xs font-medium rounded-lg bg-emerald-500 text-white hover:bg-emerald-600 transition-colors">
                                                        Tandai Disajikan
                                                    </button>
                                                @endif
                                            @else
                                                <div class="flex gap-2">
                                                    @if($order['served_at'])
                                                        <button wire:click="markAsUnserved({{ $order['id'] }})" class="flex-1 py-2.5 text-xs font-medium rounded-lg bg-gray-200 text-gray-600 hover:bg-gray-300 transition-colors">
                                                            Batalkan
                                                        </button>
                                                    @else
                                                        <button wire:click="markAsServed({{ $order['id'] }})" class="flex-1 py-2.5 text-xs font-medium rounded-lg bg-emerald-500 text-white hover:bg-emerald-600 transition-colors">
                                                            Disajikan
                                                        </button>
                                                    @endif
                                                    <a href="/admin/{{ $selectedTenantSlug }}/pos?table={{ $selectedTableId }}" class="flex-1 py-2.5 text-xs font-medium rounded-lg bg-amber-500 text-white hover:bg-amber-600 transition-colors text-center">
                                                        Bayar
                                                    </a>
                                                </div>
                                            @endif
                                        @else
                                            @if($order['status'] === 'completed')
                                                <span class="block py-2.5 text-xs font-medium text-center text-emerald-600">
                                                    ✓ Selesai
                                                </span>
                                            @else
                                                <a href="/admin/{{ $selectedTenantSlug }}/pos?table={{ $selectedTableId }}" class="block py-2.5 text-xs font-medium rounded-lg bg-amber-500 text-white hover:bg-amber-600 transition-colors text-center">
                                                    Bayar
                                                </a>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="py-12 text-center">
                                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gray-100">
                                        <x-heroicon-o-inbox class="h-8 w-8 text-gray-400"/>
                                    </div>
                                    <p class="text-sm text-gray-500">Belum ada pesanan</p>
                                </div>
                            @endforelse
                        @endif
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-5 py-4 border-t border-gray-100 bg-gray-50">
                    <button type="button" wire:click="closeModal" class="w-full py-3 text-sm font-medium rounded-xl bg-gray-200 text-gray-700 hover:bg-gray-300 transition-colors">
                        Tutup
                    </button>
                </div>

                <!-- Bulk Pay Confirmation Modal -->
                @if($showBulkPayConfirm)
                    <div class="absolute inset-0 z-20 flex items-center justify-center bg-black/50 backdrop-blur-sm rounded-2xl">
                        <div class="w-full max-w-sm mx-4 bg-white rounded-2xl p-6 shadow-2xl">
                            <div class="text-center">
                                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-amber-100 text-amber-600">
                                    <x-heroicon-o-question-mark-circle class="h-7 w-7"/>
                                </div>
                                <h4 class="text-base font-bold text-gray-900 mb-2">Bayar Semua Counter?</h4>
                                <p class="text-sm text-gray-500 mb-1">
                                    {{ $pendingCounterOrders->count() }} pesanan
                                </p>
                                <p class="text-lg font-bold text-amber-600 mb-6">
                                    Rp {{ number_format($totalCounterAmount, 0, ',', '.') }}
                                </p>
                                <div class="flex gap-3">
                                    <button
                                        type="button"
                                        wire:click="$toggle('showBulkPayConfirm')"
                                        class="flex-1 py-3 text-sm font-medium rounded-xl border border-gray-200 bg-white text-gray-700 hover:bg-gray-50 transition-colors"
                                    >
                                        Batal
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="bulkPayCounter"
                                        class="flex-1 py-3 text-sm font-medium rounded-xl bg-amber-500 text-white hover:bg-amber-600 transition-colors"
                                    >
                                        Ya, Bayar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
