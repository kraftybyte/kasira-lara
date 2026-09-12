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

        try {
            // Try different payload formats
            $payloads = [
                [
                    'orderId' => $orderId,
                    'amount' => (int) $amount,
                    'paymentMethod' => 'QRIS',
                    'callbackUrl' => $this->callbackUrl,
                ],
                [
                    'order_id' => $orderId,
                    'amount' => (int) $amount,
                    'payment_method' => 'QRIS',
                    'callback_url' => $this->callbackUrl,
                ],
                [
                    'orderId' => $orderId,
                    'amount' => (int) $amount,
                    'paymentMethod' => 'QRIS',
                ],
            ];

            $response = null;
            $lastError = null;

            foreach ($payloads as $payload) {
                try {
                    $response = Http::withToken($this->apiKey)
                        ->timeout(15)
                        ->connectTimeout(5)
                        ->post("{$this->baseUrl}/transactions", $payload);

                    if ($response->successful()) {
                        break;
                    }

                    $lastError = $response->json();
                } catch (\Exception $e) {
                    $lastError = ['error' => $e->getMessage()];

                    continue;
                }
            }

            $data = $response?->json() ?? [];

            if (! $response?->successful()) {
                Log::warning('Paywuz API Error', [
                    'order_id' => $orderId,
                    'status' => $response?->status(),
                    'body' => $data,
                ]);

                return [
                    'success' => false,
                    'message' => $data['message'] ?? $data['error'] ?? 'Gagal membuat QR: HTTP '.$response?->status(),
                ];
            }

            Log::info('Paywuz createDynamicQr', [
                'order_id' => $orderId,
                'response' => $data,
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
     * Create Virtual Account payment
     * Uses POST /v1/va/create
     */
    public function createVirtualAccount(string $orderId, float $amount, string $bankCode, ?string $customerName = null, ?string $customerEmail = null, ?string $customerPhone = null): array
    {
        if (! $this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Paywuz belum dikonfigurasi',
            ];
        }

        try {
            // Try different payload formats and endpoints
            $payloads = [
                // snake_case format
                [
                    'order_id' => $orderId,
                    'amount' => $amount,
                    'bank_code' => strtoupper($bankCode),
                    'callback_url' => $this->callbackUrl,
                ],
                // camelCase format
                [
                    'orderId' => $orderId,
                    'amount' => (int) $amount,
                    'bankCode' => strtoupper($bankCode),
                    'callbackUrl' => $this->callbackUrl,
                ],
            ];

            $endpoints = [
                '/va/create',
                '/virtual-account',
                '/transfer/va',
            ];

            $lastError = null;

            foreach ($payloads as $payloadIndex => $basePayload) {
                // Add optional fields
                $payload = $basePayload;
                if ($customerName) {
                    $payload[$payloadIndex === 0 ? 'customer_name' : 'customerName'] = $customerName;
                }
                if ($customerEmail) {
                    $payload[$payloadIndex === 0 ? 'customer_email' : 'customerEmail'] = $customerEmail;
                }
                if ($customerPhone) {
                    $payload[$payloadIndex === 0 ? 'customer_phone' : 'customerPhone'] = $customerPhone;
                }

                foreach ($endpoints as $endpoint) {
                    try {
                        $response = Http::withToken($this->apiKey)
                            ->timeout(15)
                            ->connectTimeout(5)
                            ->post("{$this->baseUrl}{$endpoint}", $payload);

                        if ($response->successful()) {
                            $data = $response->json() ?? [];

                            Log::info('Paywuz createVirtualAccount', [
                                'order_id' => $orderId,
                                'bank_code' => $bankCode,
                                'endpoint' => $endpoint,
                                'payload_format' => $payloadIndex === 0 ? 'snake_case' : 'camelCase',
                                'response' => $data,
                            ]);

                            return [
                                'success' => true,
                                'data' => $data['data'] ?? $data,
                            ];
                        }

                        $lastError = $response->json() ?? ['error' => 'HTTP '.$response->status()];
                    } catch (\Exception $e) {
                        $lastError = ['error' => $e->getMessage()];

                        continue;
                    }
                }
            }

            Log::warning('Paywuz VA API Error - All endpoints failed', [
                'order_id' => $orderId,
                'bank_code' => $bankCode,
                'endpoints_tried' => $endpoints,
                'last_error' => $lastError,
            ]);

            return [
                'success' => false,
                'message' => $lastError['error'] ?? $lastError['message'] ?? 'Endpoint Virtual Account tidak tersedia',
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
