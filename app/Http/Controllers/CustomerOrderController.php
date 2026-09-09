<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductModifier;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Table;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        // Generate unique invoice number using timestamp + random
        $invoiceNumber = 'INV-'.date('Ymd').'-'.strtoupper(substr(md5(uniqid()), 0, 6));

        // For instant payment (QRIS/Transfer), mark as paid but pending kitchen preparation
        // Kitchen will mark as preparing → ready → completed
        // Cash orders also start as pending for kitchen
        $isInstantPayment = in_array($validatedPayment['payment_method'], ['qris', 'transfer']);
        $saleStatus = 'pending';

        // Create order/sale
        $sale = Sale::create([
            'tenant_id' => $tenant->id,
            'table_id' => $table->id,
            'user_id' => Auth::id(),
            'invoice_number' => $invoiceNumber,
            'customer_name' => $request->input('customer_name', 'Customer'),
            'status' => $saleStatus,
            'payment_method' => $validatedPayment['payment_method'],
            'notes' => $request->input('notes'),
            'subtotal' => $grandTotal,
            'tax' => 0,
            'discount' => 0,
            'grand_total' => $grandTotal,
            'paid_amount' => $isInstantPayment ? $grandTotal : 0,
            'change_amount' => 0,
        ]);

        // Create sale items
        foreach ($saleItems as $item) {
            SaleItem::create(array_merge(['sale_id' => $sale->id], $item));
        }

        // Update table status if needed
        if ($table->status === 'available') {
            $table->update(['status' => 'active']);
        }

        // For instant payment (QRIS/Transfer), go directly to success
        // For Cash, redirect to payment page
        if ($isInstantPayment) {
            return redirect()->route('customer.order.success', [
                'tenant' => $tenant->slug ?? $tenant->id,
                'table' => $table->id,
                'sale' => $sale->id,
            ])->with('success', 'Pembayaran berhasil!');
        }

        // Redirect to payment page for Cash
        return redirect()->route('customer.order.payment', [
            'tenant' => $tenant->slug ?? $tenant->id,
            'table' => $table->id,
            'sale' => $sale->id,
        ]);
    }

    public function payment(string $tenantSlug, Table $table, Sale $sale)
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();

        if ($table->tenant_id !== $tenant->id || $sale->table_id !== $table->id) {
            abort(404);
        }

        return view('customer.payment', [
            'tenant' => $tenant,
            'table' => $table,
            'sale' => $sale,
        ]);
    }

    public function paymentConfirm(Request $request, string $tenantSlug, Table $table, Sale $sale)
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();

        if ($table->tenant_id !== $tenant->id || $sale->table_id !== $table->id) {
            abort(404);
        }

        // Update sale status to completed
        $sale->update(['status' => 'completed']);

        return redirect()->route('customer.order.success', [
            'tenant' => $tenant->slug ?? $tenant->id,
            'table' => $table->id,
            'sale' => $sale->id,
        ])->with('success', 'Pembayaran berhasil!');
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
