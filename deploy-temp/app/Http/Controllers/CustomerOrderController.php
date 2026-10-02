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

        if (empty($items)) {
            return redirect()->back()->with('error', 'Pilih setidaknya satu produk untuk dipesan.');
        }

        $validatedPayment = $request->validate([
            'payment_method' => 'required|in:qris,transfer,counter',
        ]);

        // Validate customer name is required
        $validatedCustomer = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'customer_email' => 'nullable|email|max:255',
        ]);

        $customerName = $validatedCustomer['customer_name'] ?? 'Customer';
        $customerPhone = $validatedCustomer['customer_phone'] ?? null;
        $customerEmail = $validatedCustomer['customer_email'] ?? null;

        // Get tenant settings for tax and Paywuz fee calculation
        $tenantSetting = TenantSetting::where('tenant_id', $tenant->id)->first();
        $showTax = $tenantSetting?->show_tax ?? false;
        $taxRate = (float) ($tenantSetting?->tax_rate ?? 0);

        // Initialize Paywuz service for fee calculation
        $paywuzFee = 0;
        $paywuzFeeByMerchant = false;
        $paywuzService = null;

        if ($validatedPayment['payment_method'] === 'qris') {
            $paywuzService = new PaywuzService($tenant->id);
            $paywuzFeeByMerchant = $paywuzService->isFeeByMerchant();
            if ($paywuzService->isConfigured()) {
                $feeCalculation = $paywuzService->calculateQrisFee(0); // Just to get the structure
                // We'll calculate after we know the subtotal
            }
        }

        $subtotal = 0;
        $saleItems = [];

        foreach ($items as $item) {
            // ============================================================
            // SECURITY FIX CWE-639: Validate product belongs to tenant
            // ============================================================
            $product = Product::where('id', $item['product_id'])
                ->where('tenant_id', $tenant->id)
                ->where('is_active', true)
                ->first();

            if (! $product) {
                return redirect()->back()->with('error', 'Produk tidak valid atau tidak tersedia.');
            }

            // Calculate modifier total
            $modifierTotal = 0;
            $itemModifiers = [];
            if (! empty($item['modifiers'])) {
                foreach ($item['modifiers'] as $modId) {
                    // ============================================================
                    // SECURITY FIX CWE-639: Validate modifier belongs to this product
                    // ============================================================
                    $modifier = ProductModifier::where('id', $modId)
                        ->where('product_id', $product->id)
                        ->first();

                    if ($modifier) {
                        $modifierTotal += (float) $modifier->price_adjustment;
                        $itemModifiers[] = $modifier;
                    }
                }
            }

            $baseTotal = $product->selling_price * $item['quantity'];
            $itemTotal = $baseTotal + ($modifierTotal * $item['quantity']);
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
                'modifiers' => $itemModifiers, // Store modifiers for later saving
            ];
        }

        // Calculate tax (PPN) if enabled
        // Using same logic as POS: harga sudah termasuk PPN (subtotal includes tax)
        $taxAmount = 0;
        if ($showTax && $taxRate > 0) {
            // Reverse calculation: tax is already included in subtotal
            // tax = subtotal * rate / (100 + rate)
            $taxAmount = round($subtotal * ($taxRate / (100 + $taxRate)), 2);
        }
        $beforeTax = $subtotal - $taxAmount;

        // Calculate Paywuz fee for QRIS
        $paywuzFeeAmount = 0;
        $paywuzFeePercent = 0;
        if ($validatedPayment['payment_method'] === 'qris' && $paywuzService && $paywuzService->isConfigured()) {
            $feeCalc = $paywuzService->calculateQrisFee($subtotal);
            $paywuzFeeAmount = $feeCalc['fee'];
            $paywuzFeePercent = $feeCalc['fee_percent'];
        }

        // Calculate grand total
        // If fee_by_merchant = true, merchant bears the fee (customer pays subtotal + tax only)
        // If fee_by_merchant = false, customer bears the fee (customer pays subtotal + tax + fee)
        $grandTotal = $beforeTax + $taxAmount;
        $customerPays = $paywuzFeeByMerchant ? $grandTotal : $grandTotal + $paywuzFeeAmount;

        // For counter payment, check if there's an existing pending counter order
        // Wrap in transaction with locking to prevent race conditions
        if ($validatedPayment['payment_method'] === 'counter') {
            $existingCounterOrder = DB::transaction(function () use ($table) {
                // Lock the table row to prevent concurrent order creation
                $lockedTable = Table::where('id', $table->id)->lockForUpdate()->first();

                return Sale::where('table_id', $table->id)
                    ->where('source', 'customer')
                    ->where('payment_method', 'counter')
                    ->where('status', 'pending')
                    ->orderByDesc('created_at')
                    ->first();
            });

            if ($existingCounterOrder) {
                // Add items to existing counter order
                return $this->addToExistingCounterOrder($existingCounterOrder, $tenant, $saleItems);
            }
        }

        // Generate unique invoice number with lockForUpdate to prevent collision
        $invoiceNumber = DB::transaction(function () use ($tenant) {
            $prefix = 'INV-'.now()->format('Ymd').'-';

            // Get highest existing number for today with lock
            $lastInvoice = Sale::where('tenant_id', $tenant->id)
                ->where('invoice_number', 'like', $prefix.'%')
                ->lockForUpdate()
                ->orderByDesc('id')
                ->first();

            $number = 0;
            if ($lastInvoice) {
                $suffix = substr($lastInvoice->invoice_number, strlen($prefix));
                // Handle format: INV-YYYYMMDD-XXXX-XXXXXX or INV-YYYYMMDD-XXXX
                $parts = explode('-', $suffix);
                if (isset($parts[0]) && is_numeric($parts[0])) {
                    $number = (int) $parts[0];
                }
            }

            // Add microsecond suffix for uniqueness
            $microSuffix = substr(now()->format('Hisu'), -6);

            return $prefix.str_pad((string) ($number + 1), 4, '0', STR_PAD_LEFT).'-'.$microSuffix;
        });

        // For QRIS payment, create Paywuz transaction first
        if ($validatedPayment['payment_method'] === 'qris') {
            return $this->processQrisPayment($request, $tenant, $table, $invoiceNumber, $customerPays, $subtotal, $taxAmount, $paywuzFeeAmount, $paywuzFeeByMerchant, $saleItems, $customerName);
        }

        // For counter payment (bayar di kasir) - create order with pending payment
        if ($validatedPayment['payment_method'] === 'counter') {
            return $this->processCounterPayment($request, $tenant, $table, $invoiceNumber, $subtotal, $taxAmount, $saleItems, $customerName);
        }

        // For other payment methods (transfer), customer bears the fee
        // Wrap in transaction to prevent orphaned records
        $sale = DB::transaction(function () use ($tenant, $table, $customerName, $validatedPayment, $invoiceNumber, $beforeTax, $taxAmount, $customerPays, $paywuzFeeAmount, $paywuzFeeByMerchant, $saleItems, $request) {
            $sale = Sale::create([
                'tenant_id' => $tenant->id,
                'table_id' => $table->id,
                'user_id' => Auth::id(),
                'invoice_number' => $invoiceNumber,
                'customer_name' => $customerName,
                'status' => 'pending',
                'source' => 'customer',
                'payment_method' => $validatedPayment['payment_method'],
                'notes' => $request->input('notes'),
                'subtotal' => $beforeTax,
                'tax' => $taxAmount,
                'discount' => 0,
                'grand_total' => $customerPays,
                'paywuz_fee' => $paywuzFeeAmount,
                'paywuz_fee_by_merchant' => $paywuzFeeByMerchant,
                'paid_amount' => 0,
                'change_amount' => 0,
                'payment_status' => 'pending',
            ]);

            // Create sale items
            foreach ($saleItems as $item) {
                $modifiers = $item['modifiers'] ?? [];
                unset($item['modifiers']); // Remove modifiers from item data

                $saleItem = SaleItem::create(array_merge(['sale_id' => $sale->id], $item));

                // Save modifiers for this sale item
                foreach ($modifiers as $modifier) {
                    SaleItemModifier::create([
                        'sale_item_id' => $saleItem->id,
                        'product_modifier_id' => $modifier->id,
                        'modifier_name' => $modifier->name,
                        'price' => $modifier->price_adjustment,
                    ]);
                }
            }

            // Update table status
            if ($table->status === 'available') {
                $table->update(['status' => 'active']);
            }

            return $sale;
        });

        return redirect()->route('customer.order.payment', [
            'tenant' => $tenant->getRouteKey(),
            'table' => $table->id,
            'sale' => $sale->id,
        ]);
    }

    /**
     * Process QRIS payment via Paywuz
     */
    protected function processQrisPayment(Request $request, Tenant $tenant, Table $table, string $invoiceNumber, float $customerPays, float $subtotal, float $taxAmount, float $paywuzFee, bool $feeByMerchant, array $saleItems, string $customerName)
    {
        // Pass tenant ID to PaywuzService so it can load tenant-specific API key
        $paywuz = new PaywuzService($tenant->id);

        // Log for debugging
        Log::info('PaywuzService initialized', [
            'tenant_id' => $tenant->id,
            'tenant_slug' => $tenant->slug,
            'is_configured' => $paywuz->isConfigured(),
            'is_enabled' => $paywuz->isEnabled(),
        ]);

        // Create Paywuz dynamic QR with customer pays amount (includes fee if not borne by merchant)
        $response = $paywuz->createDynamicQr(
            $invoiceNumber,
            $customerPays,
            $tenant->name ?? 'KasirAja'
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
                'customer_name' => $customerName,
                'status' => 'pending',
                'source' => 'customer',
                'payment_method' => 'qris',
                'notes' => $request->input('notes'),
                'subtotal' => $subtotal,
                'tax' => $taxAmount,
                'discount' => 0,
                'grand_total' => $customerPays,
                'paywuz_fee' => $paywuzFee,
                'paywuz_fee_by_merchant' => $feeByMerchant,
                'paid_amount' => 0,
                'change_amount' => 0,
                'payment_status' => 'pending',
            ]);

            foreach ($saleItems as $item) {
                $modifiers = $item['modifiers'] ?? [];
                unset($item['modifiers']);

                $saleItem = SaleItem::create(array_merge(['sale_id' => $sale->id], $item));

                // Save modifiers for this sale item
                foreach ($modifiers as $modifier) {
                    SaleItemModifier::create([
                        'sale_item_id' => $saleItem->id,
                        'product_modifier_id' => $modifier->id,
                        'modifier_name' => $modifier->name,
                        'price' => $modifier->price_adjustment,
                    ]);
                }
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
                'customer_name' => $customerName,
                'status' => 'pending',
                'source' => 'customer',
                'payment_method' => 'qris',
                'notes' => $request->input('notes'),
                'subtotal' => $subtotal,
                'tax' => $taxAmount,
                'discount' => 0,
                'grand_total' => $customerPays,
                'paywuz_fee' => $paywuzFee,
                'paywuz_fee_by_merchant' => $feeByMerchant,
                'paid_amount' => 0,
                'change_amount' => 0,
                'paywuz_transaction_id' => $response['data']['transaction_id'] ?? null,
                'paywuz_qr_url' => $response['data']['qr_url'] ?? $response['data']['qr_string'] ?? null,
                'paywuz_status' => 'pending',
                'payment_status' => 'pending',
            ]);

            // Create sale items
            foreach ($saleItems as $item) {
                $modifiers = $item['modifiers'] ?? [];
                unset($item['modifiers']);

                $saleItem = SaleItem::create(array_merge(['sale_id' => $sale->id], $item));

                // Save modifiers for this sale item
                foreach ($modifiers as $modifier) {
                    SaleItemModifier::create([
                        'sale_item_id' => $saleItem->id,
                        'product_modifier_id' => $modifier->id,
                        'modifier_name' => $modifier->name,
                        'price' => $modifier->price_adjustment,
                    ]);
                }
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

    /**
     * Process Counter payment (Bayar di Kasir)
     */
    protected function processCounterPayment(Request $request, Tenant $tenant, Table $table, string $invoiceNumber, float $subtotal, float $taxAmount, array $saleItems, string $customerName)
    {
        // For counter payment, no Paywuz fee - customer pays at cashier
        $grandTotal = $subtotal + $taxAmount;

        $sale = Sale::create([
            'tenant_id' => $tenant->id,
            'table_id' => $table->id,
            'user_id' => Auth::id(),
            'invoice_number' => $invoiceNumber,
            'customer_name' => $customerName,
            'status' => 'pending',
            'source' => 'customer',
            'payment_method' => 'counter',
            'notes' => $request->input('notes'),
            'subtotal' => $subtotal,
            'tax' => $taxAmount,
            'discount' => 0,
            'grand_total' => $grandTotal,
            'paywuz_fee' => 0,
            'paywuz_fee_by_merchant' => false,
            'paid_amount' => 0,
            'change_amount' => 0,
            'payment_status' => 'pending',
        ]);

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

        if ($table->status === 'available') {
            $table->update(['status' => 'active']);
        }

        return redirect()->route('customer.order.payment', [
            'tenant' => $tenant->slug ?? $tenant->id,
            'table' => $table->id,
            'sale' => $sale->id,
        ])->with('info', 'Pesanan berhasil! Silakan bayar di kasir.');
    }

    /**
     * Add items to existing counter order
     */
    protected function addToExistingCounterOrder(Sale $existingOrder, Tenant $tenant, array $saleItems): RedirectResponse
    {
        $additionalSubtotal = 0;

        foreach ($saleItems as $item) {
            $modifiers = $item['modifiers'] ?? [];
            unset($item['modifiers']);

            $saleItem = SaleItem::create(array_merge(['sale_id' => $existingOrder->id], $item));
            $additionalSubtotal += $item['subtotal'];

            foreach ($modifiers as $modifier) {
                SaleItemModifier::create([
                    'sale_item_id' => $saleItem->id,
                    'product_modifier_id' => $modifier->id,
                    'modifier_name' => $modifier->name,
                    'price' => $modifier->price_adjustment,
                ]);
            }
        }

        // Update existing order totals with proper tax calculation
        $newSubtotal = $existingOrder->subtotal + $additionalSubtotal;

        // Recalculate tax based on tenant settings
        $tenantSetting = TenantSetting::where('tenant_id', $existingOrder->tenant_id)->first();
        $showTax = $tenantSetting?->show_tax ?? false;
        $taxRate = (float) ($tenantSetting?->tax_rate ?? 0);

        $beforeTax = $newSubtotal;
        $taxAmount = 0;
        if ($showTax && $taxRate > 0) {
            $taxAmount = round($newSubtotal * ($taxRate / (100 + $taxRate)), 2);
        }

        $existingOrder->update([
            'subtotal' => $beforeTax,
            'tax' => $taxAmount,
            'grand_total' => $beforeTax + $taxAmount,
        ]);

        // Update table status if needed
        $existingTable = $existingOrder->table;
        if ($existingTable && is_object($existingTable) && $existingTable->status === 'available') {
            $existingTable->update(['status' => 'active']);
        }

        return redirect()->route('customer.order.payment', [
            'tenant' => $tenant->getRouteKey(),
            'table' => $existingOrder->table_id,
            'sale' => $existingOrder->id,
        ])->with('success', 'Item ditambahkan ke pesanan yang sudah ada!');
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

        // Mark as completed only if verified
        $sale->update([
            'status' => 'completed',
            'payment_status' => 'paid',
            'paid_at' => now(),
        ]);

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
