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
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-black/50 backdrop-blur-sm"
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
                class="relative w-full max-w-sm overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-gray-900"
            >
                {{-- Header --}}
                <div class="flex items-center justify-between border-b border-gray-100 p-4 dark:border-gray-800">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">QR Code Meja</h3>
                        <p class="text-sm text-gray-500">Meja {{ $selectedTableName }}</p>
                    </div>
                    <button
                        type="button"
                        wire:click="closeModal"
                        class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-800"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Content --}}
                <div class="p-6">
                    <div class="flex flex-col items-center">
                        {{-- QR Code Image --}}
                        @if($this->qrUrl())
                            <div class="rounded-2xl bg-white p-4 shadow-inner">
                                <img
                                    src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data={{ urlencode($this->qrUrl()) }}"
                                    alt="QR Code untuk Meja {{ $selectedTableName }}"
                                    class="h-56 w-56"
                                >
                            </div>
                        @else
                            <div class="flex h-56 w-56 items-center justify-center rounded-2xl bg-gray-100">
                                <p class="text-sm text-gray-500">QR Code tidak tersedia</p>
                            </div>
                        @endif

                        {{-- Instructions --}}
                        <div class="mt-6 text-center">
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Scan QR code ini untuk melihat menu dan memesan langsung dari meja Anda
                            </p>
                        </div>

                        {{-- URL --}}
                        @if($this->qrUrl())
                            <div class="mt-4 w-full">
                                <div class="flex items-center gap-2 rounded-xl bg-gray-50 p-3 dark:bg-gray-800">
                                    <input
                                        type="text"
                                        value="{{ $this->qrUrl() }}"
                                        readonly
                                        class="flex-1 bg-transparent text-xs text-gray-600 outline-none dark:text-gray-400"
                                    >
                                    <button
                                        type="button"
                                        onclick="navigator.clipboard.writeText('{{ $this->qrUrl() }}'); alert('URL disalin!')"
                                        class="rounded-lg bg-primary px-3 py-1.5 text-xs font-medium text-white hover:bg-red-600"
                                    >
                                        Salin
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Footer --}}
                <div class="border-t border-gray-100 p-4 dark:border-gray-800">
                    <button
                        type="button"
                        wire:click="closeModal"
                        class="w-full rounded-xl bg-gray-100 py-3 text-sm font-medium text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
