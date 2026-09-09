<?php

namespace App\Models;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantSetting extends Model
{
    protected $fillable = [
        'tenant_id',

        // Store Info
        'store_name',
        'logo',
        'address',
        'phone',
        'email',

        // Receipt Settings
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

        // Paywuz Settings
        'paywuz_enabled',
        'paywuz_merchant_name',
        'paywuz_api_key',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'show_logo' => 'boolean',
        'show_address' => 'boolean',
        'show_phone' => 'boolean',
        'show_customer' => 'boolean',
        'show_cashier' => 'boolean',
        'show_invoice_number' => 'boolean',
        'show_payment_method' => 'boolean',
        'show_footer' => 'boolean',
        'show_tax' => 'boolean',
        'tax_rate' => 'decimal:2',

        'paywuz_enabled' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get or create settings for current tenant
     */
    public static function getOrCreateForTenant(?int $tenantId = null): self
    {
        $tenantId ??= Filament::getTenant()?->id;

        $settings = static::where('tenant_id', $tenantId)->first();

        if (! $settings && $tenantId) {
            $settings = static::create([
                'tenant_id' => $tenantId,
                'store_name' => Filament::getTenant()?->name ?? 'Toko Saya',
                'paywuz_enabled' => false,
            ]);
        }

        return $settings;
    }

    /**
     * Check if Paywuz is enabled and configured
     */
    public function isPaywuzConfigured(): bool
    {
        return $this->paywuz_enabled
            && ! empty($this->paywuz_api_key);
    }
}
