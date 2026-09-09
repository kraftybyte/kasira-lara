<?php

namespace App\Services;

use App\Models\TenantSetting;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaywuzService
{
    protected string $baseUrl;

    protected string $apiKey;

    protected string $callbackUrl;

    protected string $merchantName;

    protected ?int $tenantId;

    public function __construct(?int $tenantId = null)
    {
        $this->baseUrl = config('services.paywuz.base_url', 'https://paywuz.id/api/v1');
        $this->callbackUrl = config('services.paywuz.callback_url', '/webhook/paywuz');
        $this->tenantId = $tenantId;

        // Load from tenant settings if available
        $this->loadFromTenantSettings();
    }

    /**
     * Load settings from tenant settings database
     */
    protected function loadFromTenantSettings(): void
    {
        $tenantId = $this->tenantId ?? Filament::getTenant()?->id;

        if ($tenantId) {
            $settings = TenantSetting::where('tenant_id', $tenantId)->first();

            if ($settings) {
                $this->apiKey = $settings->paywuz_api_key ?? '';
                $this->merchantName = $settings->paywuz_merchant_name ?? 'KasirAja';
            } else {
                $this->apiKey = '';
                $this->merchantName = 'KasirAja';
            }
        } else {
            $this->apiKey = config('services.paywuz.api_key', '');
            $this->merchantName = config('services.paywuz.merchant_name', 'KasirAja');
        }
    }

    /**
     * Check if Paywuz is configured and enabled
     */
    public function isConfigured(): bool
    {
        return ! empty($this->apiKey);
    }

    /**
     * Check if Paywuz is enabled for tenant
     */
    public function isEnabled(): bool
    {
        $tenantId = $this->tenantId ?? Filament::getTenant()?->id;

        if ($tenantId) {
            $settings = TenantSetting::where('tenant_id', $tenantId)->first();

            return $settings?->paywuz_enabled && $this->isConfigured();
        }

        return $this->isConfigured();
    }

    /**
     * Create dynamic QRIS payment
     */
    public function createDynamicQr(string $orderId, float $amount, ?string $cashierName = null): array
    {
        if (! $this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Paywuz belum dikonfigurasi',
            ];
        }

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(30)
                ->post("{$this->baseUrl}/qris/dynamic", [
                    'order_id' => $orderId,
                    'amount' => $amount,
                    'cashier_name' => $cashierName ?? $this->merchantName,
                    'callback_url' => $this->callbackUrl,
                ]);

            $data = $response->json();

            Log::info('Paywuz createDynamicQr', [
                'order_id' => $orderId,
                'response' => $data,
            ]);

            return $data;
        } catch (\Exception $e) {
            Log::error('Paywuz createDynamicQr error', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create static QRIS (fixed amount)
     */
    public function createStaticQr(string $orderId, float $amount, ?string $cashierName = null): array
    {
        if (! $this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Paywuz belum dikonfigurasi',
            ];
        }

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(30)
                ->post("{$this->baseUrl}/qris/static", [
                    'order_id' => $orderId,
                    'amount' => $amount,
                    'cashier_name' => $cashierName ?? $this->merchantName,
                    'callback_url' => $this->callbackUrl,
                ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Paywuz createStaticQr error', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Inquiry transaction status
     */
    public function inquiry(string $transactionId): array
    {
        if (! $this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Paywuz belum dikonfigurasi',
            ];
        }

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(30)
                ->post("{$this->baseUrl}/transaction/inquiry", [
                    'transaction_id' => $transactionId,
                ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Paywuz inquiry error', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get QR image by transaction ID
     */
    public function getQrImage(string $transactionId): ?string
    {
        if (! $this->isConfigured()) {
            return null;
        }

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(30)
                ->get("{$this->baseUrl}/qris/{$transactionId}/image");

            if ($response->successful()) {
                return $response->body();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Paywuz getQrImage error', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Cancel transaction
     */
    public function cancel(string $transactionId): array
    {
        if (! $this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Paywuz belum dikonfigurasi',
            ];
        }

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(30)
                ->post("{$this->baseUrl}/transaction/cancel", [
                    'transaction_id' => $transactionId,
                ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Paywuz cancel error', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get transaction history
     */
    public function getHistory(array $params = []): array
    {
        if (! $this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Paywuz belum dikonfigurasi',
            ];
        }

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(30)
                ->get("{$this->baseUrl}/transaction/history", $params);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Paywuz getHistory error', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get API key
     */
    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    /**
     * Get merchant name
     */
    public function getMerchantName(): string
    {
        return $this->merchantName;
    }
}
