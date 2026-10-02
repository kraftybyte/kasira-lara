<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Tenant;

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

        $receiptSetting = $tenant->receiptSetting;

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
