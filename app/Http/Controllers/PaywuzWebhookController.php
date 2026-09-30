<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\TenantSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaywuzWebhookController extends Controller
{
    /**
     * Handle Paywuz payment webhook notification
     */
    public function handle(Request $request)
    {
        $payload = $request->all();

        Log::info('Paywuz Webhook Received', $payload);

        // ============================================================
        // CRITICAL: Verify webhook signature BEFORE processing
        // ============================================================
        if (! $this->verifySignature($request)) {
            Log::warning('Paywuz Webhook: Invalid signature - REJECTED', [
                'ip' => $request->ip(),
                'headers' => $request->headers->all(),
            ]);

            return response()->json(['success' => false, 'message' => 'Invalid signature'], 401);
        }

        Log::info('Paywuz Webhook: Signature verified');

        // Validate required fields
        if (! isset($payload['transaction_id']) || ! isset($payload['status'])) {
            return response()->json(['success' => false, 'message' => 'Invalid payload'], 400);
        }

        $transactionId = $payload['transaction_id'];
        $status = $payload['status']; // pending, success, failed, expired

        // Find sale by transaction_id
        $sale = Sale::where('paywuz_transaction_id', $transactionId)->first();

        if (! $sale) {
            Log::warning('Paywuz Webhook: Sale not found', ['transaction_id' => $transactionId]);

            return response()->json(['success' => false, 'message' => 'Sale not found'], 404);
        }

        // ============================================================
        // DOUBLE VERIFICATION: Query Paywuz API to confirm status
        // Only accept 'success' if we verify with Paywuz API
        // ============================================================
        if ($status === 'success') {
            if (! $this->verifyWithPaywuzApi($transactionId, $sale)) {
                Log::warning('Paywuz Webhook: Status verification failed - possible fraud', [
                    'sale_id' => $sale->id,
                    'transaction_id' => $transactionId,
                ]);

                return response()->json(['success' => false, 'message' => 'Status verification failed'], 422);
            }
        }

        // Update sale based on payment status
        switch ($status) {
            case 'success':
                $sale->update([
                    'payment_status' => 'paid',
                    'paywuz_status' => 'success',
                    // Keep status='pending' so order goes to Kitchen for processing
                ]);

                // Update table status to active (customer is being served)
                if ($sale->table_id) {
                    $sale->table->update(['status' => 'active']);
                }

                Log::info('Paywuz Payment Success - Order Ready for Kitchen', [
                    'sale_id' => $sale->id,
                    'invoice' => $sale->invoice_number,
                    'amount' => $sale->grand_total,
                ]);
                break;

            case 'failed':
            case 'expired':
                $sale->update([
                    'payment_status' => 'failed',
                    'paywuz_status' => $status,
                ]);

                Log::info('Paywuz Payment '.strtoupper($status), [
                    'sale_id' => $sale->id,
                    'invoice' => $sale->invoice_number,
                ]);
                break;

            default:
                Log::info('Paywuz Payment Status: '.$status, [
                    'sale_id' => $sale->id,
                    'invoice' => $sale->invoice_number,
                ]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Verify webhook signature from Paywuz
     *
     * Format: X-Paywuz-Signature: sha256=<hex_digest>
     * Secret: HMAC-SHA256(request_body, api_key)
     */
    protected function verifySignature(Request $request): bool
    {
        $signature = $request->header('X-Paywuz-Signature');

        if (! $signature) {
            Log::warning('Paywuz Webhook: No signature header');

            return false;
        }

        // Get raw body for signature calculation
        $rawBody = $request->getContent();

        // Extract API key from tenant settings or config
        $apiKey = $this->getApiKey($request);

        if (empty($apiKey)) {
            Log::warning('Paywuz Webhook: No API key configured');

            return false;
        }

        // Calculate expected signature: HMAC-SHA256(rawBody, apiKey)
        $expectedSignature = 'sha256='.hash_hmac('sha256', $rawBody, $apiKey);

        // Timing-safe comparison to prevent timing attacks
        try {
            return hash_equals($expectedSignature, $signature);
        } catch (\Exception $e) {
            Log::error('Paywuz Webhook: Signature comparison error', ['error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Get API key for verification
     * Tries to get from sale's tenant settings first, then falls back to config
     */
    protected function getApiKey(Request $request): ?string
    {
        // Try to get transaction_id to find tenant
        $payload = $request->all();
        $transactionId = $payload['transaction_id'] ?? null;

        if ($transactionId) {
            $sale = Sale::where('paywuz_transaction_id', $transactionId)->first();

            if ($sale) {
                $settings = TenantSetting::where('tenant_id', $sale->tenant_id)->first();

                if ($settings?->paywuz_api_key) {
                    return $settings->paywuz_api_key;
                }
            }
        }

        // Fallback to global config
        return config('services.paywuz.api_key');
    }

    /**
     * Double verification with Paywuz API
     * This prevents fraud even if signature is somehow compromised
     */
    protected function verifyWithPaywuzApi(string $transactionId, Sale $sale): bool
    {
        try {
            $apiKey = $this->getApiKeyFromSale($sale);

            // SECURITY: Fail closed - reject if we can't verify
            if (empty($apiKey)) {
                Log::warning('Paywuz Webhook: Cannot verify - no API key, rejecting');

                return false;
            }

            $baseUrl = config('services.paywuz.base_url', 'https://api.paywuz.id/v1');

            $response = Http::withToken($apiKey)
                ->timeout(10)
                ->get("{$baseUrl}/transactions/{$transactionId}");

            // SECURITY: Fail closed - reject on API failure
            if (! $response->successful()) {
                Log::warning('Paywuz Webhook: API verification request failed, rejecting');

                return false;
            }

            $data = $response->json();
            $apiStatus = $data['data']['status'] ?? $data['status'] ?? null;

            // Only accept if API confirms success
            if ($apiStatus === 'success' || $apiStatus === 'paid') {
                return true;
            }

            Log::warning('Paywuz Webhook: Status mismatch', [
                'webhook_status' => 'success',
                'api_status' => $apiStatus,
                'transaction_id' => $transactionId,
            ]);

            return false;

        } catch (\Exception $e) {
            Log::error('Paywuz Webhook: API verification error', [
                'error' => $e->getMessage(),
                'transaction_id' => $transactionId,
            ]);

            // SECURITY: Fail closed - reject on exception
            return false;
        }
    }

    /**
     * Get API key from sale's tenant
     */
    protected function getApiKeyFromSale(Sale $sale): ?string
    {
        $settings = TenantSetting::where('tenant_id', $sale->tenant_id)->first();

        return $settings?->paywuz_api_key ?? config('services.paywuz.api_key');
    }
}
