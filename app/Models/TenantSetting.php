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
        'paywuz_fee_by_merchant', // false = customer bears fee, true = merchant bears fee

        // Loyalty/Points Settings
        'loyalty_enabled',
        'loyalty_points_per_rupiah',
        'loyalty_points_value',
        'loyalty_minimum_redeem',

        // Bank Account for Manual Transfer
        'bank_name',
        'bank_account',
        'bank_account_name',
        'bank_qr_image',

        // Payment Methods Enabled (POS Modal)
        'payment_qris_auto',
        'payment_va',
        'payment_transfer',
        'payment_qris_manual',
        'payment_cash',

        // QR Meja Payment Methods
        'table_qr_qris_auto',
        'table_qr_va',
        'table_qr_pay_at_counter',
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
        'paywuz_fee_by_merchant' => 'boolean',

        'loyalty_enabled' => 'boolean',
        'loyalty_points_per_rupiah' => 'decimal:2',
        'loyalty_points_value' => 'decimal:2',
        'loyalty_minimum_redeem' => 'integer',

        'bank_qr_image' => 'array',

        // Payment Methods
        'payment_qris_auto' => 'boolean',
        'payment_va' => 'boolean',
        'payment_transfer' => 'boolean',
        'payment_qris_manual' => 'boolean',
        'payment_cash' => 'boolean',

        // QR Meja Payment Methods
        'table_qr_qris_auto' => 'boolean',
        'table_qr_va' => 'boolean',
        'table_qr_pay_at_counter' => 'boolean',
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

    /**
     * Check if loyalty is enabled and configured
     */
    public function isLoyaltyEnabled(): bool
    {
        return $this->loyalty_enabled
            && $this->loyalty_points_per_rupiah > 0;
    }

    /**
     * Calculate points earned from amount
     */
    public function calculatePoints(float $amount): int
    {
        if (! $this->isLoyaltyEnabled()) {
            return 0;
        }

        return (int) floor($amount / $this->loyalty_points_per_rupiah);
    }

    /**
     * Calculate redemption value
     */
    public function calculateRedemptionValue(int $points): float
    {
        if (! $this->isLoyaltyEnabled() || $points <= 0) {
            return 0;
        }

        return $points * $this->loyalty_points_value;
    }

    /**
     * Get default settings for new tenants
     */
    public static function getDefaults(): array
    {
        return [
            'loyalty_enabled' => false,
            'loyalty_points_per_rupiah' => 1000, // 1 point per 1000 rupiah
            'loyalty_points_value' => 1,         // 1 point = Rp 1
            'loyalty_minimum_redeem' => 100,     // Min 100 points to redeem
        ];
    }
}
