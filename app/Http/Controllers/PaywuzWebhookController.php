<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\TenantSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Webhook Paywuz. Format resmi: {event, data: {id, orderId, amount, fee, totalPayment, paymentMethod, status, paidAt}, timestamp}
 * dengan header X-Paywuz-Signature = "sha256=" + HMAC-SHA256(raw body, API key proyek), X-Paywuz-Event, X-Paywuz-Delivery.
 * Format lama {transaction_id, status} tetap diterima.
 */
class PaywuzWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->json()->all() ?: $request->all();
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : $payload;
        $transactionId = (string) ($data['id'] ?? $payload['transaction_id'] ?? '');
        $orderId = (string) ($data['orderId'] ?? '');
        $status = strtolower((string) ($data['status'] ?? $payload['status'] ?? ''));
        $event = strtolower((string) ($request->header('X-Paywuz-Event') ?: ($payload['event'] ?? '')));

        if ($transactionId === '' && $orderId === '') {
            return response()->json(['success' => false, 'message' => 'Invalid payload'], 400);
        }

        $sale = $this->findSale($transactionId, $orderId);

        if (! $this->verifySignature($request, $sale)) {
            Log::warning('Paywuz Webhook: tanda tangan tidak valid', ['ip' => $request->ip(), 'transaction_id' => $transactionId, 'order_id' => $orderId]);

            return response()->json(['success' => false, 'message' => 'Invalid signature'], 401);
        }

        if (! $sale) {
            Log::warning('Paywuz Webhook: sale tidak ditemukan', ['transaction_id' => $transactionId, 'order_id' => $orderId]);

            return response()->json(['success' => false, 'message' => 'Sale not found'], 404);
        }

        // Sale yang sudah lunas tidak boleh diubah lagi oleh notifikasi susulan (ulangan, atau 'expired' yang terlambat).
        if ($sale->payment_status === 'paid') {
            return response()->json(['success' => true, 'message' => 'Already paid']);
        }

        $isPaid = in_array($status, ['success', 'paid', 'settlement'], true) || in_array($event, ['transaction.paid', 'transaction.settlement'], true);
        $isFailed = in_array($status, ['failed', 'expired', 'cancelled'], true) || in_array($event, ['transaction.failed', 'transaction.cancelled'], true);

        if ($isPaid) {
            if (! $this->verifyWithPaywuzApi($sale)) {
                return response()->json(['success' => false, 'message' => 'Status verification failed'], 422);
            }

            $sale->update(['paywuz_status' => 'success', 'paywuz_transaction_id' => $sale->paywuz_transaction_id ?: ($transactionId ?: null)]);
            $sale->markPaid($sale->payment_method ?: 'qris', $sale->paywuz_transaction_id);

            if ($sale->table_id) {
                $sale->table?->update(['status' => 'active']);
            }

            Log::info('Paywuz Webhook: lunas', ['sale_id' => $sale->id, 'invoice' => $sale->invoice_number, 'delivery' => $request->header('X-Paywuz-Delivery')]);

            return response()->json(['success' => true]);
        }

        if ($isFailed) {
            $sale->update(['payment_status' => 'failed', 'paywuz_status' => $status ?: 'failed']);
            Log::info('Paywuz Webhook: gagal/kedaluwarsa', ['sale_id' => $sale->id, 'invoice' => $sale->invoice_number, 'status' => $status, 'event' => $event]);

            return response()->json(['success' => true]);
        }

        Log::info('Paywuz Webhook: status lain', ['sale_id' => $sale->id, 'status' => $status, 'event' => $event]);

        return response()->json(['success' => true]);
    }

    protected function findSale(string $transactionId, string $orderId): ?Sale
    {
        if ($transactionId !== '' && ($sale = Sale::where('paywuz_transaction_id', $transactionId)->first())) {
            return $sale;
        }

        return $orderId !== '' ? Sale::where('invoice_number', $orderId)->whereIn('payment_method', ['qris', 'va'])->first() : null;
    }

    /**
     * HMAC-SHA256 atas raw body dengan API key tenant pemilik sale (fallback key global), perbandingan timing-safe.
     */
    protected function verifySignature(Request $request, ?Sale $sale): bool
    {
        $signature = (string) $request->header('X-Paywuz-Signature', '');
        $apiKey = $sale ? $this->getApiKeyFromSale($sale) : config('services.paywuz.api_key');

        if ($signature === '' || empty($apiKey)) {
            return false;
        }

        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $apiKey);

        return hash_equals($expected, $signature);
    }

    /**
     * Verifikasi ganda ke API (GET /transactions/:orderId): status harus lunas dan nominal sama dengan tagihan. Gagal = tolak.
     */
    protected function verifyWithPaywuzApi(Sale $sale): bool
    {
        try {
            $apiKey = $this->getApiKeyFromSale($sale);

            if (empty($apiKey)) {
                return false;
            }

            $baseUrl = rtrim(config('services.paywuz.base_url', 'https://api.paywuz.id/v1'), '/');
            $response = Http::withToken($apiKey)->timeout(10)->get("{$baseUrl}/transactions/{$sale->invoice_number}");

            if (! $response->successful()) {
                Log::warning('Paywuz Webhook: verifikasi API gagal', ['invoice' => $sale->invoice_number, 'http' => $response->status()]);

                return false;
            }

            $data = $response->json();
            $apiStatus = strtolower((string) ($data['data']['status'] ?? $data['status'] ?? ''));
            $apiAmount = $data['data']['amount'] ?? $data['amount'] ?? null;

            if ($apiAmount !== null && (int) round((float) $apiAmount) !== (int) round((float) $sale->grand_total)) {
                Log::warning('Paywuz Webhook: nominal tidak cocok', ['invoice' => $sale->invoice_number, 'api_amount' => $apiAmount, 'grand_total' => $sale->grand_total]);

                return false;
            }

            if (in_array($apiStatus, ['success', 'paid', 'settlement'], true)) {
                return true;
            }

            Log::warning('Paywuz Webhook: status API belum lunas', ['invoice' => $sale->invoice_number, 'api_status' => $apiStatus]);

            return false;
        } catch (\Throwable $e) {
            Log::error('Paywuz Webhook: verifikasi API error', ['invoice' => $sale->invoice_number, 'error' => $e->getMessage()]);

            return false;
        }
    }

    protected function getApiKeyFromSale(Sale $sale): ?string
    {
        return TenantSetting::where('tenant_id', $sale->tenant_id)->value('paywuz_api_key') ?: config('services.paywuz.api_key');
    }
}
