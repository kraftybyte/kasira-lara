<div>
    {{-- DEBUG: isOpen=$isOpen, selectedTableId=$selectedTableId, orders=count($orders) --}}
    @if($isOpen)
        <!-- Overlay -->
        <div
            x-data="{ show: @entangle('isOpen') }"
            x-show="show"
            x-on:keydown.escape.window="show = false"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
        >
            <!-- Backdrop -->
            <div
                x-show="show"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="absolute inset-0 bg-black/80"
                x-on:click="show = false"
            ></div>

            <!-- Modal -->
            <div
                x-show="show"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="relative w-full max-w-md rounded-2xl bg-white shadow-2xl"
            >
                <!-- Header -->
                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                    <div class="flex items-center gap-3">
                        <div class="flex items-center justify-center w-9 h-9 rounded-lg bg-red-500 text-white">
                            @if($selectedTableId)
                                <x-heroicon-o-document-text class="w-4 h-4"/>
                            @else
                                <x-heroicon-o-list-bullet class="w-4 h-4"/>
                            @endif
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-gray-900">{{ $selectedTableId ? 'Meja ' . $selectedTableName : 'Semua Pesanan' }}</h3>
                            <p class="text-xs text-gray-500">{{ count($orders) }} pesanan</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        wire:click="closeModal"
                        class="p-2 rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors"
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
                    <div class="px-4 py-3 bg-amber-50 border-b border-amber-200">
                        <button
                            wire:click="$toggle('showBulkPayConfirm')"
                            class="w-full py-2.5 px-4 bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold rounded-xl flex items-center justify-center gap-2 transition-colors"
                        >
                            <x-heroicon-s-banknotes class="w-5 h-5"/>
                            Bayar Semua Counter ({{ $pendingCounterOrders->count() }})
                            <span class="ml-1 px-2 py-0.5 bg-amber-600 rounded-lg text-xs">
                                Rp {{ number_format($totalCounterAmount, 0, ',', '.') }}
                            </span>
                        </button>
                    </div>
                @endif

                <!-- Content (Scrollable) -->
                <div class="overflow-y-auto" style="max-height: 50vh;">
                    <div class="p-3">
                        {{-- DEBUG: count={{ count($orders) }}, orders0_keys={{ implode(',', is_array($orders[0] ?? []) ? array_keys($orders[0]) : [] }}, orders0_has_orders={{ isset($orders[0]['orders']) ? 'yes' : 'no' }} --}}
                        @if(isset($orders[0]['orders']) && isset($orders[0]['count']))
                            {{-- Grouped by Table View --}}
                            @foreach($orders as $group)
                                <div class="border border-gray-200 rounded-xl overflow-hidden bg-white shadow-sm">
                                    {{-- Table Header --}}
                                    <div class="flex items-center justify-between px-3 py-2 bg-purple-50 border-b border-purple-200">
                                        <div class="flex items-center gap-2">
                                            <x-heroicon-o-archive-box class="w-4 h-4 text-purple-600"/>
                                            <span class="text-sm font-bold text-purple-800">{{ $group['table_name'] }}</span>
                                            <span class="px-1.5 py-0.5 text-[10px] font-medium rounded-full bg-purple-200 text-purple-700">{{ $group['count'] }} pesanan</span>
                                        </div>
                                        <span class="text-sm font-bold text-purple-700">Rp {{ number_format($group['total'], 0, ',', '.') }}</span>
                                    </div>
                                    {{-- Orders for this table --}}
                                    @foreach($group['orders'] as $order)
                                        <div class="border-b border-gray-100 last:border-b-0">
                                            <div class="flex items-center justify-between px-3 py-2 bg-gray-50/50">
                                                <div class="flex items-center gap-2">
                                                    <span class="text-xs font-semibold text-gray-900">{{ $order['invoice_number'] }}</span>
                                                    @if($order['display_status'] === 'served')
                                                        <span class="px-1.5 py-0.5 text-[9px] font-bold rounded-full bg-blue-200 text-blue-800">✓</span>
                                                    @elseif($order['display_status'] === 'ready')
                                                        <span class="px-1.5 py-0.5 text-[9px] font-bold rounded-full bg-teal-200 text-teal-800">🍽</span>
                                                    @elseif($order['display_status'] === 'pending')
                                                        <span class="px-1.5 py-0.5 text-[9px] font-bold rounded-full bg-gray-200 text-gray-800">⏳</span>
                                                    @endif
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    @php
                                                        $badgeClass = match($order['payment_method']) {
                                                            'qris' => 'bg-sky-200 text-sky-800',
                                                            'transfer' => 'bg-purple-200 text-purple-800',
                                                            'counter' => 'bg-amber-200 text-amber-800',
                                                            default => 'bg-gray-200 text-gray-800',
                                                        };
                                                    @endphp
                                                    <span class="px-1.5 py-0.5 text-[9px] font-bold rounded-full {{ $badgeClass }}">
                                                        {{ strtoupper($order['payment_method']) }}
                                                    </span>
                                                    <span class="text-xs font-bold text-gray-900">Rp {{ number_format($order['grand_total'], 0, ',', '.') }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        @else
                            {{-- Individual Order View (single table) --}}
                            @forelse($orders as $order)
                            <div class="border border-gray-200 rounded-xl overflow-hidden bg-white shadow-sm">
                                <div class="flex items-center justify-between px-3 py-2 bg-gray-50/80 border-b border-gray-200">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        @if(!$selectedTableId && isset($order['table_name']))
                                            <span class="px-1.5 py-0.5 text-[9px] font-bold rounded-full bg-purple-200 text-purple-800">{{ $order['table_name'] }}</span>
                                        @endif
                                        <span class="text-xs font-semibold text-gray-900">{{ $order['invoice_number'] ?? 'N/A' }}</span>
                                        @if($order['has_duration'])
                                            <span class="px-1.5 py-0.5 text-[9px] font-bold rounded-full bg-amber-200 text-amber-800">⏱ DURASI</span>
                                        @elseif($order['display_status'] === 'served')
                                            <span class="px-1.5 py-0.5 text-[9px] font-bold rounded-full bg-blue-200 text-blue-800">✓ DISAJIKAN</span>
                                        @elseif($order['display_status'] === 'ready')
                                            <span class="px-1.5 py-0.5 text-[9px] font-bold rounded-full bg-teal-200 text-teal-800">🍽 SIAP</span>
                                        @elseif($order['display_status'] === 'preparing')
                                            <span class="px-1.5 py-0.5 text-[9px] font-bold rounded-full bg-orange-200 text-orange-800">🔥</span>
                                        @elseif($order['display_status'] === 'pending')
                                            <span class="px-1.5 py-0.5 text-[9px] font-bold rounded-full bg-gray-200 text-gray-800">⏳</span>
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
                                        @php
                                            $badgeClass = match($order['payment_method']) {
                                                'qris' => 'bg-sky-200 text-sky-800',
                                                'transfer' => 'bg-purple-200 text-purple-800',
                                                'counter' => 'bg-amber-200 text-amber-800',
                                                'cash' => 'bg-green-200 text-green-800',
                                                default => 'bg-gray-200 text-gray-800',
                                            };
                                            $badgeLabel = match($order['payment_method']) {
                                                'counter' => 'COUNTER',
                                                default => strtoupper($order['payment_method']),
                                            };
                                        @endphp
                                        <span class="px-1.5 py-0.5 text-[9px] font-bold rounded-full {{ $badgeClass }}">
                                            {{ $badgeLabel }}
                                        </span>
                                    </div>
                                </div>

                                {{-- Order Notes --}}
                                @if(!empty($order['notes']))
                                    <div class="px-3 py-2 bg-amber-50 border-t border-amber-200">
                                        <div class="flex items-start gap-2 text-xs text-amber-700">
                                            <x-heroicon-o-chat-bubble-left class="w-3.5 h-3.5 shrink-0 mt-0.5"/>
                                            <span class="font-medium">Catatan:</span>
                                            <span>{{ $order['notes'] }}</span>
                                        </div>
                                    </div>
                                @endif

                                {{-- Items --}}
                                <div class="px-3 py-2.5 space-y-2">
                                    @foreach($order['items'] as $item)
                                        <div>
                                            <div class="flex items-center justify-between text-xs">
                                                <div class="flex items-center gap-2">
                                                    <span class="flex items-center justify-center w-5 h-5 text-[10px] font-bold rounded bg-gray-100 text-gray-600">{{ (int) $item['quantity'] }}</span>
                                                    <span class="text-gray-700">{{ $item['product_name'] }}</span>
                                                </div>
                                                <span class="text-gray-700 font-medium">Rp {{ number_format($item['subtotal'], 0, ',', '.') }}</span>
                                            </div>
                                            @if(!empty($item['notes']))
                                                <div class="ml-7 mt-0.5 flex items-center gap-1 text-[10px] text-amber-600">
                                                    <x-heroicon-o-chat-bubble-left class="w-3 h-3 shrink-0"/>
                                                    <span>{{ $item['notes'] }}</span>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>

                                {{-- Footer --}}
                                <div class="px-3 py-3 border-t border-gray-200 bg-gray-50/80">
                                    <div class="flex items-center justify-between mb-3">
                                        <span class="text-xs text-gray-500">Total</span>
                                        <span class="text-sm font-bold text-gray-900">Rp {{ number_format($order['grand_total'], 0, ',', '.') }}</span>
                                    </div>

                                    @if(!$order['has_duration'])
                                        {{-- Served button only for non-duration products --}}
                                        @if($order['status'] === 'completed')
                                            @if($order['served_at'])
                                                <button wire:click="markAsUnserved({{ $order['id'] }})" class="w-full py-2 text-xs font-medium rounded-lg bg-gray-200 text-gray-600 hover:bg-gray-300 transition-colors">Batalkan</button>
                                            @else
                                                <button wire:click="markAsServed({{ $order['id'] }})" class="w-full py-2 text-xs font-medium rounded-lg bg-emerald-500 text-white hover:bg-emerald-600 transition-colors">Tandai Disajikan</button>
                                            @endif
                                        @else
                                            <div class="flex gap-2">
                                                @if($order['served_at'])
                                                    <button wire:click="markAsUnserved({{ $order['id'] }})" class="flex-1 py-2 text-xs font-medium rounded-lg bg-gray-200 text-gray-600 hover:bg-gray-300 transition-colors">Batalkan</button>
                                                @else
                                                    <button wire:click="markAsServed({{ $order['id'] }})" class="flex-1 py-2 text-xs font-medium rounded-lg bg-emerald-500 text-white hover:bg-emerald-600 transition-colors">Disajikan</button>
                                                @endif
                                                <a href="/admin/{{ $selectedTenantSlug }}/pos?table={{ $selectedTableId }}" class="flex-1 py-2 text-xs font-medium rounded-lg bg-amber-500 text-white hover:bg-amber-600 transition-colors text-center">Bayar</a>
                                            </div>
                                        @endif
                                    @else
                                        {{-- Duration products - no served button, only pay --}}
                                        @if($order['status'] === 'completed')
                                            <span class="block py-2 text-xs font-medium text-center text-emerald-600">Selesai</span>
                                        @else
                                            <a href="/admin/{{ $selectedTenantSlug }}/pos?table={{ $selectedTableId }}" class="block py-2 text-xs font-medium rounded-lg bg-amber-500 text-white hover:bg-amber-600 transition-colors text-center">Bayar</a>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="py-8 text-center">
                                <x-heroicon-o-inbox class="w-12 h-12 mx-auto text-gray-300 mb-2"/>
                                <p class="text-xs text-gray-500">Belum ada pesanan</p>
                            </div>
                        @endforelse
                        @endif
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-4 py-3 border-t border-gray-100">
                    <button type="button" wire:click="closeModal" class="w-full py-2 text-sm font-medium rounded-xl bg-gray-100 text-gray-700 hover:bg-gray-200 transition-colors">Tutup</button>
                </div>

                <!-- Bulk Pay Confirmation Modal -->
                @if($showBulkPayConfirm)
                    <div class="absolute inset-0 z-20 flex items-center justify-center bg-black/50 backdrop-blur-sm rounded-2xl">
                        <div class="w-full max-w-xs mx-4 bg-white rounded-2xl p-5 shadow-xl">
                            <div class="text-center">
                                <div class="mx-auto mb-3 flex h-11 w-11 items-center justify-center rounded-full bg-amber-100 text-amber-600">
                                    <x-heroicon-o-question-mark-circle class="h-5 w-5"/>
                                </div>
                                <h4 class="text-sm font-bold text-gray-900 mb-1">Bayar Semua Counter?</h4>
                                <p class="text-xs text-gray-500 mb-4">
                                    {{ $pendingCounterOrders->count() }} pesanan totaling
                                    <span class="font-semibold text-amber-600">Rp {{ number_format($totalCounterAmount, 0, ',', '.') }}</span>
                                </p>
                                <div class="flex gap-2">
                                    <button
                                        type="button"
                                        wire:click="$toggle('showBulkPayConfirm')"
                                        class="flex-1 rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                                    >
                                        Batal
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="bulkPayCounter"
                                        class="flex-1 rounded-xl bg-amber-500 px-3 py-2 text-sm font-medium text-white transition hover:bg-amber-600"
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
