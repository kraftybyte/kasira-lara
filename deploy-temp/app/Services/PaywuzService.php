<?php

namespace App\Services;

use App\Models\TenantSetting;
use Filament\Facades\Filament;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaywuzService
{
    protected string $baseUrl;

    protected string $apiKey;

    protected ?string $callbackUrl;

    protected string $merchantName;

    protected ?int $tenantId;

    public function __construct(?int $tenantId = null)
    {
        $this->baseUrl = config('services.paywuz.base_url', 'https://api.paywuz.id/v1');
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
                // Callback URL tenant-specific
                $this->callbackUrl = url('/webhook/paywuz?tenant_id='.$tenantId);
            } else {
                $this->apiKey = '';
                $this->merchantName = 'KasirAja';
                $this->callbackUrl = config('services.paywuz.callback_url', url('/webhook/paywuz'));
            }
        } else {
            $this->apiKey = config('services.paywuz.api_key', '');
            $this->merchantName = config('services.paywuz.merchant_name', 'KasirAja');
            $this->callbackUrl = config('services.paywuz.callback_url', url('/webhook/paywuz'));
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
     * Get QRIS minimum amount from Paywuz API
     * Cached for 1 hour to reduce API calls
     */
    protected function getQrisMinAmount(): ?int
    {
        $cacheKey = 'paywuz_qris_min_amount';

        try {
            $cached = cache()->get($cacheKey);

            if ($cached !== null) {
                return $cached;
            }

            $response = Http::withToken($this->apiKey)
                ->timeout(10)
                ->connectTimeout(5)
                ->get("{$this->baseUrl}/payment-methods");

            if ($response->successful()) {
                $data = $response->json();
                $qris = collect($data['data'] ?? [])->firstWhere('code', 'QRIS');
                $minAmount = $qris['limits']['minIdr'] ?? 10000;

                // Cache for 1 hour
                cache()->put($cacheKey, $minAmount, 3600);

                return $minAmount;
            }
        } catch (\Exception $e) {
            Log::debug('Paywuz getQrisMinAmount failed, using default', [
                'error' => $e->getMessage(),
            ]);
        }

        // Default minimum if API fails
        return 10000;
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
     * Uses POST /v1/transactions with QRIS payment method
     */
    public function createDynamicQr(string $orderId, float $amount, ?string $cashierName = null): array
    {
        if (! $this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Paywuz belum dikonfigurasi',
            ];
        }

        // Validate minimum amount for QRIS
        $minAmount = $this->getQrisMinAmount();
        if ($minAmount && $amount < $minAmount) {
            return [
                'success' => false,
                'message' => 'Minimal pembayaran QRIS adalah Rp '.number_format($minAmount, 0, ',', '.'),
            ];
        }

        try {
            // Single standardized payload format (API Paywuz v1)
            $payload = [
                'orderId' => $orderId,
                'amount' => (int) $amount,
                'paymentMethod' => 'QRIS',
            ];

            $response = null;
            $lastError = null;
            $lastStatus = null;

            // Retry logic: up to 2 retries for server errors (502, 503, 504)
            $maxAttempts = 3;

            for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                try {
                    $response = Http::withToken($this->apiKey)
                        ->withHeaders(['Content-Type' => 'application/json'])
                        ->timeout(15)
                        ->connectTimeout(5)
                        ->post("{$this->baseUrl}/transactions", $payload);

                    $lastStatus = $response->status();
                    $data = $response->json() ?? [];

                    // Retry on server errors (502, 503, 504)
                    if (in_array($lastStatus, [502, 503, 504]) && $attempt < $maxAttempts) {
                        $waitSeconds = $attempt * 2; // 2s, 4s backoff
                        Log::warning("Paywuz server error, retrying in {$waitSeconds}s", [
                            'order_id' => $orderId,
                            'attempt' => $attempt,
                            'status' => $lastStatus,
                        ]);
                        sleep($waitSeconds);

                        continue;
                    }

                    if ($response->successful()) {
                        break;
                    }

                    $lastError = $data;
                } catch (\Exception $e) {
                    $lastError = ['error' => $e->getMessage()];
                    $lastStatus = 0;

                    // Retry on connection errors
                    if ($attempt < $maxAttempts) {
                        $waitSeconds = $attempt * 2;
                        Log::warning("Paywuz connection error, retrying in {$waitSeconds}s", [
                            'order_id' => $orderId,
                            'attempt' => $attempt,
                            'error' => $e->getMessage(),
                        ]);
                        sleep($waitSeconds);

                        continue;
                    }
                }
            }

            $data = $response?->json() ?? [];

            if (! $response?->successful()) {
                $status = $response?->status() ?? $lastStatus;
                Log::warning('Paywuz API Error', [
                    'order_id' => $orderId,
                    'status' => $status,
                    'body' => $data,
                    'error' => $data['error'] ?? null,
                    'message' => $data['message'] ?? null,
                ]);

                // User-friendly error messages based on Paywuz error codes
                $errorMessage = match ($data['error'] ?? null) {
                    'invalid_request' => $data['message'] ?? 'Request tidak valid',
                    'invalid_payment_method' => 'Metode pembayaran QRIS tidak tersedia',
                    'unauthorized' => 'API key Paywuz tidak valid',
                    'forbidden' => 'Akun Paywuz tidak aktif',
                    'order_id_environment_conflict' => 'Order ID sudah digunakan',
                    'gateway_error' => 'Layanan pembayaran Paywuz sedang gangguan. Silakan coba lagi.',
                    default => $data['message'] ?? "Gagal membuat QR: HTTP {$status}",
                };

                return [
                    'success' => false,
                    'message' => $errorMessage,
                ];
            }

            Log::info('Paywuz createDynamicQr success', [
                'order_id' => $orderId,
                'amount' => $amount,
                'transaction_id' => $data['data']['id'] ?? null,
                'status' => $data['data']['status'] ?? null,
            ]);

            return [
                'success' => true,
                'data' => $data['data'] ?? $data,
            ];
        } catch (ConnectionException $e) {
            Log::error('Paywuz Connection Error', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Tidak dapat terhubung ke server Paywuz. Periksa koneksi internet.',
            ];
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

            return $response->json() ?? [];
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
     * Uses GET /v1/transactions/:id
     */
    public function inquiry(string $transactionId): array
    {
        if (! $this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Paywuz belum dikonfigurasi',
            ];
        }

        $endpoints = [
            "/transactions/{$transactionId}",
            "/transaction/{$transactionId}",
            "/transactions/{$transactionId}/status",
        ];

        foreach ($endpoints as $endpoint) {
            try {
                $response = Http::withToken($this->apiKey)
                    ->timeout(15)
                    ->connectTimeout(5)
                    ->get("{$this->baseUrl}{$endpoint}");

                $data = $response->json() ?? [];

                if ($response->successful()) {
                    Log::info('Paywuz inquiry success', [
                        'transaction_id' => $transactionId,
                        'endpoint' => $endpoint,
                        'response' => $data,
                    ]);

                    return [
                        'success' => true,
                        'data' => $data['data'] ?? $data,
                    ];
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        Log::warning('Paywuz inquiry Error - all endpoints failed', [
            'transaction_id' => $transactionId,
        ]);

        return [
            'success' => false,
            'message' => 'Inquiry gagal: Transaction tidak ditemukan',
        ];
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

            return $response->json() ?? [];
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

            return $response->json() ?? [];
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

    /**
     * Check if merchant bears the fee (vs customer)
     */
    public function isFeeByMerchant(): bool
    {
        $tenantId = $this->tenantId ?? Filament::getTenant()?->id;

        if ($tenantId) {
            $settings = TenantSetting::where('tenant_id', $tenantId)->first();

            return $settings?->paywuz_fee_by_merchant ?? false;
        }

        return false;
    }

    /**
     * Calculate QRIS fee based on Paywuz tiered pricing
     *
     * Tiered pricing:
     * - Below Rp 150,000: 0.7% × amount + Rp 290
     * - Rp 150,000 and above: 0.95% × amount (no flat fee)
     */
    public function calculateQrisFee(float $amount): array
    {
        $threshold = 150000;

        if ($amount < $threshold) {
            // Below 150k: 0.7% × amount + 290
            $feePercent = 0.007;
            $flatFee = 290;
        } else {
            // 150k and above: 0.95% × amount (no flat fee)
            $feePercent = 0.0095;
            $flatFee = 0;
        }

        $fee = $flatFee + ceil($amount * $feePercent);

        $feeByMerchant = $this->isFeeByMerchant();

        if ($feeByMerchant) {
            // Merchant bears the fee
            $merchantReceives = $amount - $fee;
            $customerPays = $amount;
        } else {
            // Customer bears the fee (default)
            $merchantReceives = $amount;
            $customerPays = $amount + $fee;
        }

        return [
            'amount' => $amount,
            'fee' => $fee,
            'fee_percent' => $feePercent * 100,
            'flat_fee' => $flatFee,
            'customer_pays' => $customerPays,
            'merchant_receives' => $merchantReceives,
            'fee_by_merchant' => $feeByMerchant,
            'tier' => $amount < $threshold ? 'under_150k' : 'above_150k',
        ];
    }

    /**
     * Create Virtual Account payment
     * Uses POST /v1/transactions with VA payment method
     */
    public function createVirtualAccount(string $orderId, float $amount, string $bankCode, ?string $customerName = null, ?string $customerEmail = null, ?string $customerPhone = null): array
    {
        if (! $this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Paywuz belum dikonfigurasi',
            ];
        }

        // Map lowercase codes to Paywuz VA codes
        $bankCodeMap = [
            'bca' => 'BCAVA',
            'bni' => 'BNIVA',
            'bri' => 'BRIVA',
            'mandiri' => 'MANDIRIVA',
            'permata' => 'PERMATAVA',
            'bsi' => 'BSIVA',
            'cimb' => 'CIMBVA',
            'danamon' => 'DANAMONVA',
            'maybank' => 'MAYBANKVA',
            'ocbc' => 'OCBCVA',
        ];

        $paywuzBankCode = $bankCodeMap[strtolower($bankCode)] ?? strtoupper($bankCode).'VA';

        try {
            // Use specific bank code to get VA number directly
            $payload = [
                'orderId' => $orderId,
                'amount' => (int) $amount,
                'paymentMethod' => $paywuzBankCode,
            ];

            // Add customer info if provided
            if ($customerName) {
                $payload['metadata'] = array_merge($payload['metadata'] ?? [], [
                    'customerName' => $customerName,
                ]);
            }
            if ($customerEmail) {
                $payload['metadata'] = array_merge($payload['metadata'] ?? [], [
                    'email' => $customerEmail,
                ]);
            }
            if ($customerPhone) {
                $payload['metadata'] = array_merge($payload['metadata'] ?? [], [
                    'phone' => $customerPhone,
                ]);
            }

            $response = null;
            $lastError = null;
            $lastStatus = null;

            // Retry logic: up to 2 retries for server errors (502, 503, 504)
            $maxAttempts = 3;

            for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                try {
                    $response = Http::withToken($this->apiKey)
                        ->withHeaders(['Content-Type' => 'application/json'])
                        ->timeout(15)
                        ->connectTimeout(5)
                        ->post("{$this->baseUrl}/transactions", $payload);

                    $lastStatus = $response->status();
                    $data = $response->json() ?? [];

                    // Retry on server errors (502, 503, 504)
                    if (in_array($lastStatus, [502, 503, 504]) && $attempt < $maxAttempts) {
                        $waitSeconds = $attempt * 2;
                        Log::warning("Paywuz VA server error, retrying in {$waitSeconds}s", [
                            'order_id' => $orderId,
                            'attempt' => $attempt,
                            'status' => $lastStatus,
                        ]);
                        sleep($waitSeconds);

                        continue;
                    }

                    if ($response->successful()) {
                        break;
                    }

                    $lastError = $data;
                } catch (\Exception $e) {
                    $lastError = ['error' => $e->getMessage()];
                    $lastStatus = 0;

                    if ($attempt < $maxAttempts) {
                        $waitSeconds = $attempt * 2;
                        sleep($waitSeconds);

                        continue;
                    }
                }
            }

            $data = $response?->json() ?? [];

            if (! $response?->successful()) {
                Log::warning('Paywuz VA API Error', [
                    'order_id' => $orderId,
                    'bank_code' => $bankCode,
                    'status' => $response?->status() ?? $lastStatus,
                    'body' => $data,
                ]);

                return [
                    'success' => false,
                    'message' => $data['message'] ?? $data['error'] ?? 'Gagal membuat Virtual Account: HTTP '.$response?->status(),
                ];
            }

            Log::info('Paywuz createVirtualAccount success', [
                'order_id' => $orderId,
                'bank_code' => $bankCode,
                'amount' => $amount,
                'transaction_id' => $data['data']['id'] ?? null,
            ]);

            return [
                'success' => true,
                'data' => $data['data'] ?? $data,
            ];
        } catch (ConnectionException $e) {
            Log::error('Paywuz VA Connection Error', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Tidak dapat terhubung ke server Paywuz. Periksa koneksi internet.',
            ];
        } catch (\Exception $e) {
            Log::error('Paywuz createVirtualAccount error', [
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
     * Get available banks for Virtual Account
     */
    public function getAvailableBanks(): array
    {
        return [
            'bca' => [
                'code' => 'bca',
                'name' => 'Bank BCA',
                'icon' => 'bca',
                'color' => '#016299',
            ],
            'bni' => [
                'code' => 'bni',
                'name' => 'Bank BNI',
                'icon' => 'bni',
                'color' => '#0068B3',
            ],
            'bri' => [
                'code' => 'bri',
                'name' => 'Bank BRI',
                'icon' => 'bri',
                'color' => '#005D38',
            ],
            'mandiri' => [
                'code' => 'mandiri',
                'name' => 'Bank Mandiri',
                'icon' => 'mandiri',
                'color' => '#003C71',
            ],
            'permata' => [
                'code' => 'permata',
                'name' => 'Bank Permata',
                'icon' => 'permata',
                'color' => '#0068B3',
            ],
        ];
    }
}
