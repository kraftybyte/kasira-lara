<div class="text-center">
    <x-filament::button
        type="button"
        color="gray"
        wire:click="testPaywuzConnection"
        wire:loading.attr="disabled"
        size="sm">
        <span wire:loading.remove wire:target="testPaywuzConnection">Test Koneksi</span>
        <span wire:loading wire:target="testPaywuzConnection">Menguji...</span>
    </x-filament::button>
</div>
