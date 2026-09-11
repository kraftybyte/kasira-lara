<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductModifier;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Table;
use App\Models\Tenant;
use App\Services\PaywuzService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CustomerOrderController extends Controller
{
    public function show(string $tenantSlug, Table $table)
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();

        if ($table->tenant_id !== $tenant->id) {
            abort(404);
        }

        $products = Product::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->with(['category', 'modifiers'])
            ->orderBy('name')
            ->get()
            ->groupBy('category_id');

        return view('customer.order', [
            'tenant' => $tenant,
            'table' => $table,
            'products' => $products,
            'completedSale' => null,
            'pendingSale' => null,
        ]);
    }

    public function checkout(Request $request, string $tenantSlug, Table $table)
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();

        if ($table->tenant_id !== $tenant->id) {
            abort(404);
        }

        // Handle both JSON and array format
        $items = $request->input('items');

        if (is_string($items)) {
            $items = json_decode($items, true);
        }

        if (empty($items)) {
            return redirect()->back()->with('error', 'Pilih setidaknya satu produk untuk dipesan.');
        }

        $validatedPayment = $request->validate([
            'payment_method' => 'required|in:qris,transfer',
        ]);

        $grandTotal = 0;
        $saleItems = [];

        foreach ($items as $item) {
            $product = Product::findOrFail($item['product_id']);

            // Calculate modifier total
            $modifierTotal = 0;
            $itemModifiers = [];
            if (! empty($item['modifiers'])) {
                foreach ($item['modifiers'] as $modId) {
                    $modifier = ProductModifier::find($modId);
                    if ($modifier) {
                        $modifierTotal += (float) $modifier->price_adjustment;
                        $itemModifiers[] = $modifier;
                    }
                }
            }

            $baseTotal = $product->selling_price * $item['quantity'];
            $itemTotal = $baseTotal + ($modifierTotal * $item['quantity']);
            $grandTotal += $itemTotal;

            $saleItems[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'sku' => $product->sku,
                'quantity' => $item['quantity'],
                'unit_price' => $product->selling_price,
                'discount' => 0,
                'tax' => 0,
                'subtotal' => $itemTotal,
                'total' => $itemTotal,
            ];
        }

        // Generate unique invoice number
        $invoiceNumber = 'INV-'.date('Ymd').'-'.strtoupper(substr(md5(uniqid()), 0, 6));

        // For QRIS payment, create Paywuz transaction first
        if ($validatedPayment['payment_method'] === 'qris') {
            return $this->processQrisPayment($request, $tenant, $table, $invoiceNumber, $grandTotal, $saleItems);
        }

        // For other payment methods, create pending order
        $sale = Sale::create([
            'tenant_id' => $tenant->id,
            'table_id' => $table->id,
            'user_id' => Auth::id(),
            'invoice_number' => $invoiceNumber,
            'customer_name' => $request->input('customer_name', 'Customer'),
            'status' => 'pending',
            'payment_method' => $validatedPayment['payment_method'],
            'notes' => $request->input('notes'),
            'subtotal' => $grandTotal,
            'tax' => 0,
            'discount' => 0,
            'grand_total' => $grandTotal,
            'paid_amount' => 0,
            'change_amount' => 0,
            'payment_status' => 'pending',
        ]);

        // Create sale items
        foreach ($saleItems as $item) {
            SaleItem::create(array_merge(['sale_id' => $sale->id], $item));
        }

        // Update table status
        if ($table->status === 'available') {
            $table->update(['status' => 'active']);
        }

        return redirect()->route('customer.order.payment', [
            'tenant' => $tenant->slug ?? $tenant->id,
            'table' => $table->id,
            'sale' => $sale->id,
        ]);
    }

    /**
     * Process QRIS payment via Paywuz
     */
    protected function processQrisPayment(Request $request, Tenant $tenant, Table $table, string $invoiceNumber, float $grandTotal, array $saleItems)
    {
        $paywuz = new PaywuzService;

        // Create Paywuz dynamic QR
        $response = $paywuz->createDynamicQr(
            $invoiceNumber,
            $grandTotal,
            config('services.paywuz.merchant_name', 'KasirAja')
        );

        Log::info('Paywuz QR Response', $response);

        // Check if Paywuz is configured
        if (! $paywuz->isConfigured()) {
            // Paywuz not configured, create order without payment
            $sale = Sale::create([
                'tenant_id' => $tenant->id,
                'table_id' => $table->id,
                'user_id' => Auth::id(),
                'invoice_number' => $invoiceNumber,
                'customer_name' => $request->input('customer_name', 'Customer'),
                'status' => 'pending',
                'payment_method' => 'qris',
                'notes' => $request->input('notes'),
                'subtotal' => $grandTotal,
                'tax' => 0,
                'discount' => 0,
                'grand_total' => $grandTotal,
                'paid_amount' => 0,
                'change_amount' => 0,
                'payment_status' => 'pending',
            ]);

            foreach ($saleItems as $item) {
                SaleItem::create(array_merge(['sale_id' => $sale->id], $item));
            }

            if ($table->status === 'available') {
                $table->update(['status' => 'active']);
            }

            return redirect()->route('customer.order.payment', [
                'tenant' => $tenant->slug ?? $tenant->id,
                'table' => $table->id,
                'sale' => $sale->id,
            ])->with('warning', 'Paywuz belum dikonfigurasi. Silakan bayar di kasir.');
        }

        // Check if Paywuz returned success
        if (isset($response['success']) && $response['success']) {
            // Create sale with Paywuz transaction ID
            $sale = Sale::create([
                'tenant_id' => $tenant->id,
                'table_id' => $table->id,
                'user_id' => Auth::id(),
                'invoice_number' => $invoiceNumber,
                'customer_name' => $request->input('customer_name', 'Customer'),
                'status' => 'pending',
                'payment_method' => 'qris',
                'notes' => $request->input('notes'),
                'subtotal' => $grandTotal,
                'tax' => 0,
                'discount' => 0,
                'grand_total' => $grandTotal,
                'paid_amount' => 0,
                'change_amount' => 0,
                'paywuz_transaction_id' => $response['data']['transaction_id'] ?? null,
                'paywuz_qr_url' => $response['data']['qr_url'] ?? $response['data']['qr_string'] ?? null,
                'paywuz_status' => 'pending',
                'payment_status' => 'pending',
            ]);

            // Create sale items
            foreach ($saleItems as $item) {
                SaleItem::create(array_merge(['sale_id' => $sale->id], $item));
            }

            // Update table status
            if ($table->status === 'available') {
                $table->update(['status' => 'active']);
            }

            // Redirect to payment page with QR
            return redirect()->route('customer.order.payment', [
                'tenant' => $tenant->slug ?? $tenant->id,
                'table' => $table->id,
                'sale' => $sale->id,
            ]);
        }

        // Paywuz error
        Log::error('Paywuz QR Creation Failed', $response);

        return redirect()->back()
            ->with('error', 'Gagal membuat QR payment: '.($response['message'] ?? 'Unknown error'));
    }

    public function payment(string $tenantSlug, Table $table, Sale $sale)
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();

        if ($table->tenant_id !== $tenant->id || $sale->table_id !== $table->id) {
            abort(404);
        }

        // Check if already paid
        if ($sale->status === 'completed' || $sale->payment_status === 'paid') {
            return redirect()->route('customer.order.success', [
                'tenant' => $tenant->slug ?? $tenant->id,
                'table' => $table->id,
                'sale' => $sale->id,
            ]);
        }

        // Get QR data if Paywuz transaction exists
        $qrData = null;
        if ($sale->paywuz_transaction_id && $sale->payment_method === 'qris') {
            $paywuz = new PaywuzService;

            // If no qr_url stored, try to get it
            if (! $sale->paywuz_qr_url) {
                $qrData = $sale->paywuz_qr_url;
            } else {
                $qrData = $sale->paywuz_qr_url;
            }
        }

        return view('customer.payment', [
            'tenant' => $tenant,
            'table' => $table,
            'sale' => $sale,
            'qrData' => $qrData,
        ]);
    }

    public function paymentConfirm(Request $request, string $tenantSlug, Table $table, Sale $sale)
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();

        if ($table->tenant_id !== $tenant->id || $sale->table_id !== $table->id) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Not found'], 404);
            }
            abort(404);
        }

        // Check if already paid
        if ($sale->payment_status === 'paid' || $sale->status === 'completed') {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'redirect' => route('customer.order.success', [
                        'tenant' => $tenant->slug ?? $tenant->id,
                        'table' => $table->id,
                        'sale' => $sale->id,
                    ]),
                ]);
            }

            return redirect()->route('customer.order.success', [
                'tenant' => $tenant->slug ?? $tenant->id,
                'table' => $table->id,
                'sale' => $sale->id,
            ]);
        }

        // Check payment status via Paywuz if transaction exists
        if ($sale->paywuz_transaction_id) {
            $paywuz = new PaywuzService;
            $status = $paywuz->inquiry($sale->paywuz_transaction_id);

            if (isset($status['data']['status'])) {
                $paywuzStatus = $status['data']['status'];

                // Update local status
                $sale->update([
                    'paywuz_status' => $paywuzStatus,
                ]);

                if ($paywuzStatus === 'success') {
                    $sale->update([
                        'status' => 'completed',
                        'payment_status' => 'paid',
                        'paid_at' => now(),
                    ]);

                    // Only set table to available if no more pending orders exist
                    $hasPendingOrders = Sale::where('table_id', $table->id)
                        ->where('id', '!=', $sale->id)
                        ->where('status', '!=', 'completed')
                        ->exists();

                    if (! $hasPendingOrders && $table->status === 'active') {
                        $table->update(['status' => 'available']);
                    }

                    $redirectUrl = route('customer.order.success', [
                        'tenant' => $tenant->slug ?? $tenant->id,
                        'table' => $table->id,
                        'sale' => $sale->id,
                    ]);

                    if ($request->expectsJson()) {
                        return response()->json(['success' => true, 'redirect' => $redirectUrl]);
                    }

                    return redirect($redirectUrl)->with('success', 'Pembayaran berhasil!');
                }

                if ($paywuzStatus === 'expired' || $paywuzStatus === 'failed') {
                    if ($request->expectsJson()) {
                        return response()->json(['success' => false, 'message' => 'Payment expired atau failed'], 400);
                    }

                    return redirect()->back()->with('error', 'Payment expired atau failed. Silakan coba lagi.');
                }
            }
        }

        // If no Paywuz, mark as completed (cash/manual)
        $sale->update([
            'status' => 'completed',
            'payment_status' => 'paid',
            'paid_at' => now(),
        ]);

        // Only set table to available if no more pending orders exist
        $hasPendingOrders = Sale::where('table_id', $table->id)
            ->where('id', '!=', $sale->id)
            ->where('status', '!=', 'completed')
            ->exists();

        if (! $hasPendingOrders && $table->status === 'active') {
            $table->update(['status' => 'available']);
        }

        $redirectUrl = route('customer.order.success', [
            'tenant' => $tenant->slug ?? $tenant->id,
            'table' => $table->id,
            'sale' => $sale->id,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'redirect' => $redirectUrl]);
        }

        return redirect($redirectUrl)->with('success', 'Pembayaran berhasil!');
    }

    public function success(string $tenantSlug, Table $table, Sale $sale)
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();

        if ($table->tenant_id !== $tenant->id || $sale->table_id !== $table->id) {
            abort(404);
        }

        return view('customer.success', [
            'tenant' => $tenant,
            'table' => $table,
            'sale' => $sale,
        ]);
    }
}
