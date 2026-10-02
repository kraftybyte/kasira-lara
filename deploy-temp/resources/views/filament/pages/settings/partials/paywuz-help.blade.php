<div class="space-y-3">
    {{-- How to get API Key --}}
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3">
        <p class="text-sm text-blue-800 dark:text-blue-300 mb-2 font-semibold">Cara mendapatkan API Key:</p>
        <ol class="text-xs text-blue-700 dark:text-blue-400 space-y-1 list-decimal list-inside">
            <li>Login ke <a href="https://paywuz.id" target="_blank" class="underline font-medium">paywuz.id</a></li>
            <li>Pilih menu <strong>API Keys</strong></li>
            <li>Copy & paste API Key</li>
        </ol>
    </div>

    {{-- Fee Explanation --}}
    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-3">
        <p class="text-sm text-amber-800 dark:text-amber-300 mb-2 font-semibold">📋 Mekanisme Biaya Admin QRIS</p>
        <p class="text-xs text-amber-700 dark:text-amber-400 mb-3">
            Setiap transaksi QRIS dikenakan biaya admin oleh Paywuz. Ada 2 pilihan:
        </p>

        {{-- Option 1: Customer bears fee --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg p-2 mb-2 border border-amber-100 dark:border-amber-700">
            <p class="text-xs font-semibold text-amber-800 dark:text-amber-300 mb-1">
                ❌ OFF (Default) - Biaya dibebankan ke Customer
            </p>
            <p class="text-[11px] text-amber-700 dark:text-amber-400">
                Customer membayar <strong>amount + biaya admin</strong>.
                <br>Tenant terima <strong>seuai amount</strong> (tanpa potong biaya).
            </p>
            <div class="mt-2 bg-amber-100 dark:bg-amber-800/50 rounded p-1.5 text-[10px]">
                <strong>Contoh:</strong> Pembelian Rp 50.000<br>
                Customer bayar: <span class="font-bold">Rp 50.640</span><br>
                Tenant terima: <span class="font-bold">Rp 50.000</span>
            </div>
        </div>

        {{-- Option 2: Merchant bears fee --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg p-2 border border-amber-100 dark:border-amber-700">
            <p class="text-xs font-semibold text-amber-800 dark:text-amber-300 mb-1">
                ✅ ON - Tenant menyerap Biaya Admin
            </p>
            <p class="text-[11px] text-amber-700 dark:text-amber-400">
                Customer membayar <strong>sesuai amount</strong> (tanpa tambahan).
                <br>Tenant terima <strong>amount - biaya admin</strong>.
            </p>
            <div class="mt-2 bg-amber-100 dark:bg-amber-800/50 rounded p-1.5 text-[10px]">
                <strong>Contoh:</strong> Pembelian Rp 50.000<br>
                Customer bayar: <span class="font-bold">Rp 50.000</span><br>
                Tenant terima: <span class="font-bold">Rp 49.360</span> <span class="text-red-500">(-Rp 640)</span>
            </div>
        </div>
    </div>

    {{-- Tiered Pricing Info --}}
    <div class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-3">
        <p class="text-sm text-gray-700 dark:text-gray-300 mb-2 font-semibold">💰 Detail Biaya Berjenjang</p>
        <div class="space-y-1.5 text-[11px] text-gray-600 dark:text-gray-400">
            <div class="flex justify-between">
                <span>Di bawah Rp 150.000:</span>
                <span class="font-medium">0.7% × amount + Rp 290</span>
            </div>
            <div class="flex justify-between">
                <span>Rp 150.000 ke atas:</span>
                <span class="font-medium">0.95% × amount (tanpa biaya tetap)</span>
            </div>
        </div>
        <div class="mt-2 bg-gray-100 dark:bg-gray-700 rounded p-1.5 text-[10px]">
            <strong>Contoh:</strong><br>
            QRIS Rp 50.000 → biaya <span class="font-bold">Rp 640</span><br>
            QRIS Rp 150.000 → biaya <span class="font-bold">Rp 1.425</span><br>
            QRIS Rp 500.000 → biaya <span class="font-bold">Rp 4.750</span>
        </div>
    </div>
</div>
