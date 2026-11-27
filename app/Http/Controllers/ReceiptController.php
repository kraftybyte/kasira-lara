<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Tenant;
use App\Models\TenantSetting;

class ReceiptController extends Controller
{
    public function show(Tenant $tenant, Sale $sale)
    {
        /*
        |--------------------------------------------------------------------------
        | VALIDASI TENANT
        |--------------------------------------------------------------------------
        */

        abort_unless(
            (int) $sale->tenant_id === (int) $tenant->id,
            404
        );

        // User harus anggota tenant ini, bukan sekadar login
        abort_unless(auth()->user()?->canAccessTenant($tenant), 404);

        /*
        |--------------------------------------------------------------------------
        | LOAD DATA STRUK
        |--------------------------------------------------------------------------
        */

        $sale->load([
            'tenant',
            'customer',
            'user',
            'items',
            'payments',
        ]);

        /*
        |--------------------------------------------------------------------------
        | RECEIPT SETTING
        |--------------------------------------------------------------------------
        |
        | Setting khusus tenant.
        | Kalau belum ada, tetap null.
        |
        */

        // Pengaturan struk dan PPN dari tenant_settings, sama dengan POS dan halaman pelanggan
        $receiptSetting = TenantSetting::getOrCreateForTenant($tenant->id);

        /*
        |--------------------------------------------------------------------------
        | RETURN RECEIPT
        |--------------------------------------------------------------------------
        */

        return response()->view(
            'receipts.pos',
            [
                'sale' => $sale,
                'tenant' => $tenant,
                'receiptSetting' => $receiptSetting,
            ]
        );
    }
}
