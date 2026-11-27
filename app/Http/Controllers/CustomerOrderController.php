<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductModifier;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleItemModifier;
use App\Models\Table;
use App\Models\Tenant;
use App\Models\TenantSetting;
use App\Services\PaywuzService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CustomerOrderController extends Controller
{
    public function show(string $tenantSlug, Table $table)
    {
        $tenant = Tenant::findBySlug($tenantSlug) ?? Tenant::findBySlugOrId($tenantSlug);

        abort_unless($tenant, 404, 'Tenant tidak ditemukan');

        // SECURITY: Check table ownership and active status
        if ($table->tenant_id !== $tenant->id || ! $table->is_active) {
            abort(404);
        }

        $products = Product::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->with(['category', 'modifiers'])
            ->orderBy('name')
            ->get()
            ->groupBy('category_id');

        // Check for pending/unpaid counter order for this table
        $pendingSale = Sale::where('table_id', $table->id)
            ->where('source', 'customer')
            ->where('status', 'pending')
            ->where('payment_method', 'counter')
            ->orderByDesc('created_at')
            ->first();

        // Check individual payment method settings from TenantSetting
        $tenantSetting = TenantSetting::where('tenant_id', $tenant->id)->first();

        // QR Meja settings (for customer scan)
        $paywuzService = new PaywuzService($tenant->id);
        $isQrisActive = $paywuzService->isConfigured() && $paywuzService->isEnabled() && ($tenantSetting?->table_qr_qris_auto ?? false);
        $isVaActive = $paywuzService->isConfigured() && ($tenantSetting?->table_qr_va ?? false);
        $isCounterActive = $tenantSetting?->table_qr_pay_at_counter ?? false;

        return view('customer.order', [
            'tenant' => $tenant,
            'table' => $table,
            'products' => $products,
            'completedSale' => null, // Don't show completed sale banner on order page
            'pendingSale' => $pendingSale,
            'isQrisActive' => $isQrisActive,
            'isVaActive' => $isVaActive,
            'isCounterActive' => $isCounterActive,
        ]);
    }

    public function checkout(Request $request, string $tenantSlug, Table $table)
    {
        $tenant = Tenant::findBySlug($tenantSlug) ?? Tenant::findBySlugOrId($tenantSlug);

        abort_unless($tenant, 404, 'Tenant tidak ditemukan');

        if ($table->tenant_id !== $tenant->id) {
            abort(404);
        }

        // Handle both JSON and array format
        $items = $request->input('items');

        if (is_string($items)) {
            $items = json_decode($items, true);
        }

        $request->merge(['items' => is_array($items) ? $items : []]);

        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer',
            'items.*.quantity' => 'required|integer|min:1|max:999',
            'items.*.modifiers' => 'nullable|array',
            'items.*.modifiers.*' => 'integer',
            'payment_method' => 'required|in:qris,transfer,counter',
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'customer_email' => 'nullable|email|max:255',
        ], [
            'items.required' => 'Pilih setidaknya satu produk untuk dipesan.',
            'items.*.quantity.min' => 'Jumlah pesanan tidak valid.',
        ]);

        $items = $validated['items'];
        $tenantSetting = TenantSetting::where('tenant_id', $tenant->id)->first();
        $paymentMethod = $validated['payment_method'];
        $paywuz = new PaywuzService($tenant->id);

        $subtotal = 0;
        $saleItems = [];

        foreach ($items as $item) {
            // Produk harus milik tenant ini dan aktif
            $product = Product::where('id', $item['product_id'])
                ->where('tenant_id', $tenant->id)
                ->where('is_active', true)
                ->first();

            if (! $product) {
                return redirect()->back()->with('error', 'Produk tidak valid atau tidak tersedia.');
            }

            $modifierTotal = 0;
            $itemModifiers = [];
            foreach ($item['modifiers'] ?? [] as $modId) {
                // Modifier harus milik produk ini
                $modifier = ProductModifier::where('id', $modId)
                    ->where('product_id', $product->id)
                    ->first();

                if (! $modifier) {
                    return redirect()->back()->with('error', "Pilihan tambahan untuk {$product->name} tidak valid.");
                }

                $modifierTotal += (float) $modifier->price_adjustment;
                $itemModifiers[] = $modifier;
            }

            $itemTotal = ($product->selling_price + $modifierTotal) * $item['quantity'];
            $subtotal += $itemTotal;

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
                'modifiers' => $itemModifiers,
            ];
        }

        // Harga sudah termasuk PPN: pajak dihitung mundur sekali di sini dan dipakai semua metode.
        $taxAmount = $this->taxFromGross($subtotal, $tenantSetting);

        $paywuzFee = 0;
        $feeByMerchant = $paywuz->isFeeByMerchant();
        if ($paymentMethod === 'qris' && $paywuz->isConfigured()) {
            $paywuzFee = $paywuz->calculateQrisFee($subtotal)['fee'];
        }

        $attributes = [
            'payment_method' => $paymentMethod,
            'notes' => $request->input('notes'),
            'subtotal' => $subtotal - $taxAmount,
            'tax' => $taxAmount,
            'grand_total' => $subtotal,
            'paywuz_fee' => 0,
            'paywuz_fee_by_merchant' => false,
        ];

        if ($paymentMethod === 'counter') {
            if ($appended = $this->appendToCounterOrder($tenant, $table, $saleItems, $tenantSetting)) {
                return $appended;
            }

            $sale = $this->createSale($tenant, $table, $attributes, $saleItems);

            return $this->redirectToPayment($tenant, $table, $sale)->with('info', 'Pesanan berhasil! Silakan bayar di kasir.');
        }

        if ($paymentMethod === 'transfer') {
            $sale = $this->createSale($tenant, $table, $attributes, $saleItems);

            return $this->redirectToPayment($tenant, $table, $sale);
        }

        // QRIS via Paywuz: pelanggan membayar tagihan plus biaya gateway jika toko tidak menanggungnya.
        $sale = $this->createSale($tenant, $table, array_merge($attributes, [
            'grand_total' => $feeByMerchant ? $subtotal : $subtotal + $paywuzFee,
            'paywuz_fee' => $paywuzFee,
            'paywuz_fee_by_merchant' => $feeByMerchant,
        ]), $saleItems);

        if (! $paywuz->isConfigured()) {
            return $this->redirectToPayment($tenant, $table, $sale)->with('warning', 'Paywuz belum dikonfigurasi. Silakan bayar di kasir.');
        }

        $response = $paywuz->createDynamicQr($sale->invoice_number, (float) $sale->grand_total, $tenant->name ?? 'KasirAja');

        if (! ($response['success'] ?? false)) {
            Log::error('Paywuz QR Creation Failed', $response);
            $sale->items()->delete();
            $sale->delete();

            return redirect()->back()->with('error', 'Gagal membuat QR payment: '.($response['message'] ?? 'Unknown error'));
        }

        $sale->update([
            'paywuz_transaction_id' => $response['data']['id'] ?? null,
            'paywuz_qr_url' => $response['data']['paymentUrl'] ?? null,
            'paywuz_status' => 'pending',
        ]);

        return $this->redirectToPayment($tenant, $table, $sale);
    }

    /**
     * PPN dihitung mundur dari harga yang sudah termasuk pajak.
     */
    protected function taxFromGross(float $gross, ?TenantSetting $setting): float
    {
        $rate = (float) ($setting?->tax_rate ?? 0);

        if (! ($setting?->show_tax ?? false) || $rate <= 0) {
            return 0.0;
        }

        return round($gross * ($rate / (100 + $rate)), 2);
    }

    /**
     * Sale baru beserta item-itemnya dalam satu transaksi; nomor invoice dibuat di transaksi yang sama.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<int, array<string, mixed>>  $saleItems
     */
    protected function createSale(Tenant $tenant, Table $table, array $attributes, array $saleItems): Sale
    {
        return DB::transaction(function () use ($tenant, $table, $attributes, $saleItems): Sale {
            $sale = Sale::create(array_merge([
                'tenant_id' => $tenant->id,
                'table_id' => $table->id,
                'user_id' => Auth::id(),
                'invoice_number' => $this->nextInvoiceNumber($tenant),
                'status' => 'pending',
                'source' => 'customer',
                'discount' => 0,
                'paid_amount' => 0,
                'change_amount' => 0,
                'payment_status' => 'pending',
            ], $attributes));

            $this->createSaleItems($sale, $saleItems);

            if ($table->status === 'available') {
                $table->update(['status' => 'active']);
            }

            return $sale;
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $saleItems
     */
    protected function createSaleItems(Sale $sale, array $saleItems): void
    {
        foreach ($saleItems as $item) {
            $modifiers = $item['modifiers'] ?? [];
            unset($item['modifiers']);

            $saleItem = SaleItem::create(array_merge(['sale_id' => $sale->id], $item));

            foreach ($modifiers as $modifier) {
                SaleItemModifier::create([
                    'sale_item_id' => $saleItem->id,
                    'product_modifier_id' => $modifier->id,
                    'modifier_name' => $modifier->name,
                    'price' => $modifier->price_adjustment,
                ]);
            }
        }
    }

    /**
     * Nomor urut harian per tenant. Harus dipanggil di dalam transaksi yang sama dengan Sale::create.
     */
    protected function nextInvoiceNumber(Tenant $tenant): string
    {
        $prefix = 'INV-'.now()->format('Ymd').'-';

        $number = Sale::where('tenant_id', $tenant->id)
            ->where('invoice_number', 'like', $prefix.'%')
            ->lockForUpdate()
            ->pluck('invoice_number')
            ->map(fn (string $invoice) => (int) explode('-', substr($invoice, strlen($prefix)))[0])
            ->max() ?? 0;

        return $prefix.str_pad((string) ($number + 1), 4, '0', STR_PAD_LEFT).'-'.substr(now()->format('Hisu'), -6);
    }

    /**
     * Tambahkan item ke pesanan kasir yang masih pending di meja ini, jika ada.
     *
     * @param  array<int, array<string, mixed>>  $saleItems
     */
    protected function appendToCounterOrder(Tenant $tenant, Table $table, array $saleItems, ?TenantSetting $tenantSetting): ?RedirectResponse
    {
        $existingOrder = DB::transaction(function () use ($table, $saleItems, $tenantSetting): ?Sale {
            $existingOrder = Sale::where('table_id', $table->id)
                ->where('source', 'customer')
                ->where('payment_method', 'counter')
                ->where('status', 'pending')
                ->orderByDesc('created_at')
                ->lockForUpdate()
                ->first();

            if (! $existingOrder) {
                return null;
            }

            $this->createSaleItems($existingOrder, $saleItems);

            $gross = (float) $existingOrder->subtotal + (float) $existingOrder->tax + (float) array_sum(array_column($saleItems, 'subtotal'));
            $tax = $this->taxFromGross($gross, $tenantSetting);

            $existingOrder->update([
                'subtotal' => $gross - $tax,
                'tax' => $tax,
                'grand_total' => $gross,
            ]);

            return $existingOrder;
        });

        if (! $existingOrder) {
            return null;
        }

        if ($table->status === 'available') {
            $table->update(['status' => 'active']);
        }

        return $this->redirectToPayment($tenant, $table, $existingOrder)->with('success', 'Item ditambahkan ke pesanan yang sudah ada!');
    }

    protected function redirectToPayment(Tenant $tenant, Table $table, Sale $sale): RedirectResponse
    {
        return redirect()->route('customer.order.payment', [
            'tenant' => $tenant->getRouteKey(),
            'table' => $table->id,
            'sale' => $sale->id,
        ]);
    }

    public function payment(string $tenantSlug, Table $table, Sale $sale)
    {
        $tenant = Tenant::findBySlug($tenantSlug) ?? Tenant::findBySlugOrId($tenantSlug);

        abort_unless($tenant, 404, 'Tenant tidak ditemukan');

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

        // Get tenant settings for dynamic bank accounts
        $tenantSetting = TenantSetting::where('tenant_id', $tenant->id)->first();

        return view('customer.payment', [
            'tenant' => $tenant,
            'table' => $table,
            'sale' => $sale,
            'qrData' => $qrData,
            'tenantSetting' => $tenantSetting,
        ]);
    }

    public function paymentConfirm(Request $request, string $tenantSlug, Table $table, Sale $sale)
    {
        $tenant = Tenant::findBySlug($tenantSlug) ?? Tenant::findBySlugOrId($tenantSlug);

        abort_unless($tenant, 404, 'Tenant tidak ditemukan');

        if ($table->tenant_id !== $tenant->id || $sale->table_id !== $table->id) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Not found'], 404);
            }
            abort(404);
        }

        // Check if already paid or completed (staff marked it as completed)
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
            $paywuz = new PaywuzService($tenant->id);
            $status = $paywuz->inquiry($sale->paywuz_transaction_id);

            if (isset($status['data']['status'])) {
                $paywuzStatus = $status['data']['status'];

                // Update local status
                $sale->update([
                    'paywuz_status' => $paywuzStatus,
                ]);

                if ($paywuzStatus === 'success') {
                    $sale->markPaid('qris', $sale->paywuz_transaction_id);

                    // NOTE: Table stays occupied until kasir explicitly closes it
                    // DO NOT auto-release table after payment

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

        // ============================================================
        // SECURITY FIX CWE-841: Payment bypass prevention
        // For transfer payment, require staff confirmation - cannot auto-verify
        // ============================================================
        if ($sale->payment_method === 'transfer') {
            // Transfer payments require manual staff verification
            // DO NOT auto-mark as paid - this prevents fake payment claims
            Log::warning('CustomerOrderController: Transfer payment confirmation attempted - requires staff verification', [
                'sale_id' => $sale->id,
                'invoice' => $sale->invoice_number,
                'table_id' => $table->id,
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pembayaran transfer memerlukan verifikasi dari staff. Silakan tunjukkan bukti transfer ke kasir.',
                ], 403);
            }

            return redirect()->back()->with('error', 'Pembayaran transfer memerlukan verifikasi dari staff. Silakan tunjukkan bukti transfer ke kasir.');
        }

        // ============================================================
        // Counter payment (Bayar di Kasir) - cannot be confirmed by customer
        // Staff will mark as paid from POS system
        // ============================================================
        if ($sale->payment_method === 'counter') {
            Log::warning('CustomerOrderController: Counter payment confirmation attempted - requires staff verification', [
                'sale_id' => $sale->id,
                'invoice' => $sale->invoice_number,
                'table_id' => $table->id,
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pembayaran counter harus diverifikasi oleh staff kasir.',
                ], 403);
            }

            return redirect()->back()->with('error', 'Pembayaran counter harus diverifikasi oleh staff kasir. Silakan bayar di kasir terlebih dahulu.');
        }

        // For QRIS without Paywuz transaction (demo/fallback mode only)
        // Only allow if explicitly in demo mode (paywuz_transaction_id starts with DEMO-)
        if ($sale->payment_method === 'qris' && empty($sale->paywuz_transaction_id)) {
            Log::warning('CustomerOrderController: QRIS payment without transaction ID - blocked', [
                'sale_id' => $sale->id,
                'invoice' => $sale->invoice_number,
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pembayaran QRIS tidak valid. Silakan coba lagi.',
                ], 400);
            }

            return redirect()->back()->with('error', 'Pembayaran QRIS tidak valid. Silakan coba lagi.');
        }

        // SECURITY: DEMO mode should require manual verification by staff
        // DO NOT auto-complete payments in demo mode - this prevents fake payments
        if (empty($sale->paywuz_transaction_id) || str_starts_with($sale->paywuz_transaction_id, 'DEMO-')) {
            Log::warning('CustomerOrderController: DEMO mode payment blocked - requires manual verification', [
                'sale_id' => $sale->id,
                'invoice' => $sale->invoice_number,
                'payment_method' => $sale->payment_method,
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Demo mode - silakan verifikasi manual di kasir.',
                ], 403);
            }

            return redirect()->back()->with('error', 'Demo mode - silakan verifikasi manual di kasir.');
        }

        // Gateway belum melaporkan sukses (pending, atau inquiry gagal): jangan pernah menandai lunas dari sini.
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'status' => $sale->paywuz_status ?? 'pending',
                'message' => 'Pembayaran belum terkonfirmasi.',
            ]);
        }

        return redirect()->back()->with('info', 'Pembayaran belum terkonfirmasi. Silakan selesaikan pembayaran.');
    }

    public function success(string $tenantSlug, Table $table, Sale $sale)
    {
        $tenant = Tenant::findBySlug($tenantSlug) ?? Tenant::findBySlugOrId($tenantSlug);

        abort_unless($tenant, 404, 'Tenant tidak ditemukan');

        if ($table->tenant_id !== $tenant->id || $sale->table_id !== $table->id) {
            abort(404);
        }

        // SECURITY: Only allow success page if payment is actually paid
        // Redirect to payment page if not paid (prevents bypassing webhook)
        if ($sale->payment_status !== 'paid') {
            return redirect()->route('customer.order.payment', [
                'tenant' => $tenant->slug ?? $tenant->id,
                'table' => $table->id,
                'sale' => $sale->id,
            ])->with('error', 'Pembayaran belum terkonfirmasi. Silakan bayar terlebih dahulu.');
        }

        return view('customer.success', [
            'tenant' => $tenant,
            'table' => $table,
            'sale' => $sale,
        ]);
    }
}
