<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantReceiptSetting extends Model
{
    protected $fillable = [
        'tenant_id',

        'store_name',
        'logo',
        'address',
        'phone',
        'email',

        'show_logo',
        'show_address',
        'show_phone',
        'show_customer',
        'show_cashier',
        'show_invoice_number',
        'show_payment_method',
        'show_footer',

        'footer_text',

        'tax_rate',
        'show_tax',
    ];

    protected function casts(): array
    {
        return [
            'show_logo' => 'boolean',
            'show_address' => 'boolean',
            'show_phone' => 'boolean',
            'show_customer' => 'boolean',
            'show_cashier' => 'boolean',
            'show_invoice_number' => 'boolean',
            'show_payment_method' => 'boolean',
            'tax_rate' => 'decimal:2',
            'show_tax' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
