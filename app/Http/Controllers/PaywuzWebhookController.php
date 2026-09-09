<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\Http\Request;
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

        // Update sale based on payment status
        switch ($status) {
            case 'success':
                $sale->update([
                    'status' => 'completed',
                    'payment_status' => 'paid',
                    'paid_at' => now(),
                ]);

                // Update table status if applicable
                if ($sale->table_id) {
                    $sale->table->update(['status' => 'available']);
                }

                Log::info('Paywuz Payment Success', [
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
     * Verify webhook signature (if paywuz provides one)
     */
    public function verify(Request $request): bool
    {
        $signature = $request->header('X-Paywuz-Signature');

        if (! $signature) {
            return false;
        }

        // If paywuz uses HMAC signature verification
        $payload = $request->getContent();
        $secret = config('services.paywuz.api_key');

        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }
}
