<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SaleCancellationService
{
    public function cancel(
        Sale $sale,
        int $userId,
        string $reason
    ): Sale {

        return DB::transaction(function () use (
            $sale,
            $userId,
            $reason
        ) {

            /*
            |--------------------------------------------------------------------------
            | LOCK SALE
            |--------------------------------------------------------------------------
            */

            $sale = Sale::query()
                ->whereKey($sale->id)
                ->lockForUpdate()
                ->firstOrFail();

            /*
            |--------------------------------------------------------------------------
            | CEK STATUS
            |--------------------------------------------------------------------------
            */

            if ($sale->status === 'cancelled') {
                throw new RuntimeException(
                    'Transaksi ini sudah dibatalkan sebelumnya.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | CEK TRANSAKSI
            |--------------------------------------------------------------------------
            */

            if ($sale->status !== 'completed') {
                throw new RuntimeException(
                    'Hanya transaksi yang sudah selesai yang dapat dibatalkan.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | VALIDASI ALASAN
            |--------------------------------------------------------------------------
            */

            $reason = trim($reason);

            if ($reason === '') {
                throw new RuntimeException(
                    'Alasan pembatalan wajib diisi.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | LOAD ITEMS
            |--------------------------------------------------------------------------
            */

            $sale->load('items');

            /*
            |--------------------------------------------------------------------------
            | KEMBALIKAN STOK
            |--------------------------------------------------------------------------
            */

            // Stok hanya dikembalikan kalau memang pernah dipotong (stock_deducted_at).
            foreach ($sale->stock_deducted_at ? $sale->items : [] as $item) {

                /*
                |--------------------------------------------------------------------------
                | Item tanpa product
                |--------------------------------------------------------------------------
                */

                if (! $item->product_id) {
                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Lock product
                |--------------------------------------------------------------------------
                */

                $product = Product::query()
                    ->whereKey($item->product_id)
                    ->lockForUpdate()
                    ->first();

                /*
                |--------------------------------------------------------------------------
                | Product sudah dihapus
                |--------------------------------------------------------------------------
                */

                if (! $product) {
                    throw new RuntimeException(
                        "Produk {$item->product_name} tidak ditemukan."
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Kembalikan stok
                |--------------------------------------------------------------------------
                */

                $product->increment(
                    'stock',
                    (float) $item->quantity
                );

                foreach ($product->ingredients as $ingredient) {
                    $used = (float) $ingredient->pivot->quantity * (float) $item->quantity;

                    if ($used > 0) {
                        $ingredient->increment('stock', $used);
                    }
                }
            }

            /*
            |--------------------------------------------------------------------------
            | UPDATE SALE
            |--------------------------------------------------------------------------
            */

            $sale->update([

                'status' => 'cancelled',

                'cancelled_by' => $userId,

                'cancelled_at' => now(),

                // Keluar dari layar dapur dan daftar meja: keduanya memfilter closed_at
                'closed_at' => now(),

                'cancellation_reason' => $reason,

                'stock_deducted_at' => null,

            ]);

            /*
            |--------------------------------------------------------------------------
            | RETURN
            |--------------------------------------------------------------------------
            */

            return $sale->fresh([
                'items',
                'payments',
                'customer',
                'user',
                'cancelledBy',
            ]);
        });
    }
}
