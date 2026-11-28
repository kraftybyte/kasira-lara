<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductModifier;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleItemModifier;
use App\Models\Tenant;
use App\Models\TenantSetting;
use App\Services\ReceiptText;

function receiptSale(): Sale
{
    $tenant = Tenant::factory()->create(['name' => 'Warung Contoh']);
    TenantSetting::create(['tenant_id' => $tenant->id, 'store_name' => 'Warung Contoh Rasa Nusantara', 'address' => 'Jl. Melati No. 10, Bandung', 'footer_text' => "Terima kasih\nSampai jumpa lagi", 'show_tax' => true, 'tax_rate' => 11]);
    $sale = Sale::create(['tenant_id' => $tenant->id, 'invoice_number' => 'INV-2026-0001', 'status' => 'completed', 'payment_status' => 'paid', 'payment_method' => 'cash', 'subtotal' => 25000, 'tax' => 2750, 'grand_total' => 27750, 'paid_amount' => 50000, 'change_amount' => 22250, 'customer_name' => 'Budi']);
    $item = SaleItem::create(['sale_id' => $sale->id, 'product_name' => 'Nasi Goreng Spesial Kampung Pedas Level 3', 'quantity' => 2, 'unit_price' => 10000, 'subtotal' => 20000, 'total' => 20000, 'notes' => 'tanpa bawang']);
    $category = Category::create(['tenant_id' => $tenant->id, 'name' => 'Makanan', 'slug' => 'makanan']);
    $product = Product::create(['tenant_id' => $tenant->id, 'category_id' => $category->id, 'sku' => 'NG-1', 'name' => 'Nasi Goreng', 'selling_price' => 10000, 'stock' => 10, 'is_active' => true]);
    $modifier = ProductModifier::create(['product_id' => $product->id, 'name' => 'Extra telur', 'price_adjustment' => 0]);
    SaleItemModifier::create(['sale_item_id' => $item->id, 'product_modifier_id' => $modifier->id, 'modifier_name' => 'Extra telur', 'price' => 0]);
    SaleItem::create(['sale_id' => $sale->id, 'product_name' => 'Es Teh', 'quantity' => 1, 'unit_price' => 5000, 'subtotal' => 5000, 'total' => 5000]);

    return $sale;
}

test('receipt text fits 58 mm paper and carries every section', function () {
    $lines = ReceiptText::lines(receiptSale(), 32);
    $text = implode("\n", $lines);

    expect(collect($lines)->max(fn (string $l) => mb_strlen($l)))->toBeLessThanOrEqual(32)
        ->and($text)->toContain('Warung Contoh Rasa Nusantara')
        ->toContain('INV-2026-0001')
        ->toContain('Budi')
        ->toContain('Nasi Goreng Spesial Kampung')
        ->toContain('2 x Rp 10.000')
        ->toContain('+ Extra telur')
        ->toContain('* tanpa bawang')
        ->toContain('PPN 11%')
        ->toContain('Rp 27.750')
        ->toContain('Tunai')
        ->toContain('Rp 22.250')
        ->toContain('Sampai jumpa lagi');
});

test('receipt text uses the full 80 mm width', function () {
    $lines = ReceiptText::lines(receiptSale(), 48);

    expect(collect($lines)->max(fn (string $l) => mb_strlen($l)))->toBe(48)
        ->and($lines[0])->toContain('Warung Contoh Rasa Nusantara');
});
