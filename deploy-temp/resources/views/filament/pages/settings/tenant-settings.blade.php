<x-filament-panels::page>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Pengaturan</h1>
                <p class="mt-2 h-12 text-sm text-gray-500">Kelola pengaturan toko</p>
            </div>
            <x-filament::button wire:click="save" color="primary">
                <span wire:loading.remove wire:target="save">Simpan</span>
                <span wire:loading wire:target="save">Menyimpan...</span>
            </x-filament::button>
        </div>

        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-8">
            {{ $this->form }}
        </div>
    </div>
</x-filament-panels::page>
