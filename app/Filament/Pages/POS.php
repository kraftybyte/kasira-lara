<?php

namespace App\Filament\Pages;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Table;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Throwable;

class POS extends Page
{
    protected string $view = 'filament.pages.p-o-s';

    protected static ?string $title = 'POS';

    protected static ?string $slug = 'pos';

    protected static bool $shouldRegisterNavigation = true;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    public function getHeader(): ?View
    {
        return view('filament.pages.pos-header', [
            'availableTables' => $this->availableTables,
            'activeTables' => $this->activeTables,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | MOUNT
    |--------------------------------------------------------------------------
    */

    public function mount(): void
    {
        // Handle table parameter from URL
        $tableId = Request::query('table');

        if ($tableId) {
            $this->selectTable((int) $tableId);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | POS STATE
    |--------------------------------------------------------------------------
    */

    public array $cart = [];

    public string $search = '';

    public ?int $customerId = null;

    public string $paymentMethod = 'cash';

    public float|int|string $paidAmount = 0;

    public string $paymentNotes = '';

    public bool $showCheckout = false;

    public bool $showAddCustomer = false;

    public string $newCustomerName = '';

    public string $newCustomerEmail = '';

    public string $newCustomerPhone = '';

    public string $customerSearch = '';

    public ?int $tableId = null;

    public ?int $activeTableId = null;

    public ?int $currentBillId = null;

    public ?int $selectedCategoryId = null;

    /*
    |--------------------------------------------------------------------------
    | SEARCH PRODUCT
    |--------------------------------------------------------------------------
    */

    public function searchProduct(): void
    {
        // Produk menggunakan computed property $this->products
    }

    /*
    |--------------------------------------------------------------------------
    | ADD TO CART
    |--------------------------------------------------------------------------
    */

    public function addToCart(Product $product): void
    {
        $tenant = Filament::getTenant();

        if (! $tenant) {

            Notification::make()
                ->title('Tenant tidak ditemukan')
                ->danger()
                ->send();

            return;
        }

        $tenantId = (int) $tenant->getKey();

        /*
        |--------------------------------------------------------------------------
        | PASTIKAN PRODUK MILIK TENANT
        |--------------------------------------------------------------------------
        */

        if ((int) $product->tenant_id !== $tenantId) {

            Notification::make()
                ->title('Produk tidak valid')
                ->danger()
                ->send();

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | PRODUK AKTIF
        |--------------------------------------------------------------------------
        */

        if ((int) $product->is_active !== 1) {

            Notification::make()
                ->title('Produk tidak aktif')
                ->danger()
                ->send();

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | CEK STOK (HANYA UNTUK FIXED TYPE)
        |--------------------------------------------------------------------------
        */

        if (
            $product->rate_type !== 'duration'
            && (float) $product->stock <= 0
        ) {

            Notification::make()
                ->title('Stok habis')
                ->body(
                    "Stok {$product->name} sudah habis."
                )
                ->danger()
                ->send();

            return;
        }

        $productId = (int) $product->id;

        /*
        |--------------------------------------------------------------------------
        | PRODUCT SUDAH ADA DI CART
        |--------------------------------------------------------------------------
        */

        if (isset($this->cart[$productId])) {

            $currentQuantity =
                (float) $this->cart[$productId]['quantity'];

            if (
                $currentQuantity + 1 >
                (float) $product->stock
            ) {

                Notification::make()
                    ->title('Stok tidak mencukupi')
                    ->body(
                        "Stok {$product->name} tersedia ".
                        (int) $product->stock
                    )
                    ->warning()
                    ->send();

                return;
            }

            $this->cart[$productId]['quantity'] =
                $currentQuantity + 1;

            $this->recalculateCartItem($productId);

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | PRODUCT BARU
        |--------------------------------------------------------------------------
        */

        $sellingPrice =
            (float) $product->selling_price;

        $isDuration = $product->rate_type === 'duration';

        $this->cart[$productId] = [

            'product_id' => $productId,

            'product_name' => $product->name,

            'sku' => $product->sku,

            'unit_price' => $sellingPrice,

            'quantity' => 1,

            'subtotal' => $sellingPrice,

            'total' => $sellingPrice,

            'is_duration' => $isDuration,

            'rate_type' => $product->rate_type,

            'rate' => (float) $product->rate,

        ];
    }

    /*
    |--------------------------------------------------------------------------
    | INCREASE QUANTITY
    |--------------------------------------------------------------------------
    */

    public function increaseQuantity(
        int $productId
    ): void {

        if (! isset($this->cart[$productId])) {
            return;
        }

        $tenant = Filament::getTenant();

        if (! $tenant) {
            return;
        }

        $tenantId = (int) $tenant->getKey();

        $product = Product::query()
            ->where(
                'id',
                $productId
            )
            ->where(
                'tenant_id',
                $tenantId
            )
            ->first();

        if (! $product) {
            return;
        }

        $quantity =
            (float) $this->cart[$productId]['quantity'];

        if ($product->rate_type !== 'duration' && $quantity + 1 > (float) $product->stock) {

            Notification::make()
                ->title('Stok tidak mencukupi')
                ->body(
                    'Stok '.$product->name.' tersedia '.(int) $product->stock
                )
                ->warning()
                ->send();

            return;
        }

        $this->cart[$productId]['quantity'] =
            $quantity + 1;

        $this->recalculateCartItem(
            $productId
        );
    }

    /*
    |--------------------------------------------------------------------------
    | DECREASE QUANTITY
    |--------------------------------------------------------------------------
    */

    public function decreaseQuantity(
        int $productId
    ): void {

        if (! isset($this->cart[$productId])) {
            return;
        }

        $quantity =
            (float) $this->cart[$productId]['quantity'];

        if ($quantity <= 1) {

            unset(
                $this->cart[$productId]
            );

            return;
        }

        $this->cart[$productId]['quantity'] =
            $quantity - 1;

        $this->recalculateCartItem(
            $productId
        );
    }

    /*
    |--------------------------------------------------------------------------
    | REMOVE CART
    |--------------------------------------------------------------------------
    */

    public function removeFromCart(
        int $productId
    ): void {

        unset(
            $this->cart[$productId]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | INCREMENT QUANTITY
    |--------------------------------------------------------------------------
    */

    public function incrementQuantity(int $productId): void
    {
        if (isset($this->cart[$productId])) {
            $this->cart[$productId]['quantity']++;
            $this->recalculateCartItem($productId);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DECREMENT QUANTITY
    |--------------------------------------------------------------------------
    */

    public function decrementQuantity(int $productId): void
    {
        if (isset($this->cart[$productId])) {
            if ($this->cart[$productId]['quantity'] > 1) {
                $this->cart[$productId]['quantity']--;
                $this->recalculateCartItem($productId);
            } else {
                $this->removeFromCart($productId);
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CLEAR CART
    |--------------------------------------------------------------------------
    */

    public function clearCart(): void
    {
        $this->cart = [];

        $this->showCheckout = false;

        $this->paidAmount = 0;

        $this->paymentMethod = 'cash';

        $this->customerId = null;

        $this->paymentNotes = '';
    }

    /*
    |--------------------------------------------------------------------------
    | OPEN CHECKOUT
    |--------------------------------------------------------------------------
    */

    public function openCheckout(): void
    {
        if (empty($this->cart)) {

            Notification::make()
                ->title('Keranjang kosong')
                ->warning()
                ->send();

            return;
        }

        $this->showCheckout = true;

        /*
        |--------------------------------------------------------------------------
        | DEFAULT CASH = TOTAL
        |--------------------------------------------------------------------------
        */

        if ($this->paymentMethod === 'cash') {

            $this->paidAmount =
                $this->total;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CLOSE CHECKOUT
    |--------------------------------------------------------------------------
    */

    public function closeCheckout(): void
    {
        $this->showCheckout = false;
    }

    /*
    |--------------------------------------------------------------------------
    | PAYMENT METHOD UPDATED
    |--------------------------------------------------------------------------
    */

    public function updatedPaymentMethod(): void
    {
        if (
            $this->paymentMethod !== 'cash'
        ) {

            $this->paidAmount =
                $this->total;

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | CASH KEMBALI KE 0
        |--------------------------------------------------------------------------
        */

        $this->paidAmount = 0;
    }

    /*
    |--------------------------------------------------------------------------
    | PROCESS PAYMENT
    |--------------------------------------------------------------------------
    */

    public function processPayment(): void
    {
        /*
        |--------------------------------------------------------------------------
        | CART CHECK
        |--------------------------------------------------------------------------
        */

        if (empty($this->cart)) {

            Notification::make()
                ->title('Keranjang kosong')
                ->danger()
                ->send();

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | TENANT CHECK
        |--------------------------------------------------------------------------
        */

        $tenant = Filament::getTenant();

        if (! $tenant) {

            Notification::make()
                ->title('Tenant tidak ditemukan')
                ->danger()
                ->send();

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | AMBIL TENANT ID SEBAGAI INTEGER
        |--------------------------------------------------------------------------
        |
        | Ini menghindari problem Intelephense:
        | Undefined method 'id'
        |
        */

        $tenantId =
            (int) $tenant->getKey();

        /*
        |--------------------------------------------------------------------------
        | PAYMENT CALCULATION
        |--------------------------------------------------------------------------
        */

        $subtotal =
            (float) $this->subtotal;

        $taxAmount =
            (float) $this->taxAmount;

        $grandTotal =
            (float) $this->total;

        $paidAmount =
            (float) $this->paidAmount;

        /*
        |--------------------------------------------------------------------------
        | TOTAL CHECK
        |--------------------------------------------------------------------------
        */

        if ($grandTotal <= 0) {

            Notification::make()
                ->title('Total transaksi tidak valid')
                ->danger()
                ->send();

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | CASH PAYMENT CHECK
        |--------------------------------------------------------------------------
        */

        if (
            $this->paymentMethod === 'cash'
            && $paidAmount < $grandTotal
        ) {

            Notification::make()
                ->title('Pembayaran kurang')
                ->body(
                    'Kurang Rp '.
                    number_format(
                        $grandTotal - $paidAmount,
                        0,
                        ',',
                        '.'
                    )
                )
                ->danger()
                ->send();

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | NON CASH PAYMENT
        |--------------------------------------------------------------------------
        */

        if (
            $this->paymentMethod !== 'cash'
        ) {

            $paidAmount =
                $grandTotal;
        }

        /*
        |--------------------------------------------------------------------------
        | CHANGE
        |--------------------------------------------------------------------------
        */

        $changeAmount =
            max(
                0,
                $paidAmount - $grandTotal
            );

        try {

            /*
            |--------------------------------------------------------------------------
            | DATABASE TRANSACTION
            |--------------------------------------------------------------------------
            */

            $result = DB::transaction(
                function () use (
                    $tenantId,
                    $subtotal,
                    $taxAmount,
                    $grandTotal,
                    $paidAmount,
                    $changeAmount
                ) {

                    /*
                    |--------------------------------------------------------------------------
                    | LOCK PRODUCTS
                    |--------------------------------------------------------------------------
                    */

                    $products = [];

                    foreach (
                        $this->cart as $item
                    ) {

                        $product =
                            Product::query()
                                ->where(
                                    'id',
                                    $item['product_id']
                                )
                                ->where(
                                    'tenant_id',
                                    $tenantId
                                )
                                ->lockForUpdate()
                                ->first();

                        if (! $product) {

                            throw new \Exception(
                                "Produk {$item['product_name']} tidak ditemukan."
                            );
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | ACTIVE CHECK
                        |--------------------------------------------------------------------------
                        */

                        if (
                            ! (bool)
                            $product->is_active
                        ) {

                            throw new \Exception(
                                "Produk {$product->name} sudah tidak aktif."
                            );
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | QUANTITY
                        |--------------------------------------------------------------------------
                        */

                        $quantity =
                            (float) $item['quantity'];

                        if ($quantity <= 0) {

                            throw new \Exception(
                                "Jumlah produk {$product->name} tidak valid."
                            );
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | STOCK CHECK (HANYA UNTUK FIXED TYPE)
                        |--------------------------------------------------------------------------
                        */

                        if (
                            $product->rate_type !== 'duration'
                            && (float) $product->stock < $quantity
                        ) {

                            throw new \Exception(
                                "Stok {$product->name} tidak mencukupi. ".
                                'Stok tersedia: '.(int) $product->stock.'.'
                            );
                        }

                        $products[
                            (int) $product->id
                        ] = [

                            'product' => $product,

                            'quantity' => $quantity,

                        ];
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | INVOICE NUMBER
                    |--------------------------------------------------------------------------
                    */

                    // Check if this is a table checkout (updating existing bill)
                    $isTableCheckout = $this->currentBillId !== null;
                    $existingBill = null;
                    $table = null;

                    if ($isTableCheckout) {
                        $existingBill = Sale::where('id', $this->currentBillId)->first();
                        if ($existingBill && $existingBill->table_id) {
                            $table = Table::where('id', $existingBill->table_id)->first();
                        }
                    }

                    $invoiceNumber =
                        $this->generateInvoiceNumber(
                            $tenantId
                        );

                    /*
                    |--------------------------------------------------------------------------
                    | CREATE OR UPDATE SALE
                    |--------------------------------------------------------------------------
                    */

                    if ($isTableCheckout && $existingBill) {
                        // Update existing bill to completed
                        $existingBill->update([
                            'status' => 'completed',
                            'payment_method' => $this->paymentMethod,
                            'paid_amount' => $paidAmount,
                            'change_amount' => $changeAmount,
                            'notes' => $this->paymentNotes ?: null,
                        ]);

                        // Update table to available
                        if ($table) {
                            $table->update(['status' => 'available']);
                        }

                        $sale = $existingBill;
                    } else {
                        $sale =
                            Sale::create([

                                'tenant_id' => $tenantId,

                                'customer_id' => $this->customerId
                                        ?: null,

                                'user_id' => Auth::id(),

                                'invoice_number' => $invoiceNumber,

                                'status' => 'completed',

                                'payment_method' => $this->paymentMethod,

                                'subtotal' => $subtotal,

                                'discount' => 0,

                                'tax' => $taxAmount,

                                'grand_total' => $grandTotal,

                                'paid_amount' => $paidAmount,

                                'change_amount' => $changeAmount,

                                'notes' => $this->paymentNotes
                                        ?: null,

                            ]);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | SALE ITEMS
                    |--------------------------------------------------------------------------
                    */

                    foreach (
                        $this->cart as $item
                    ) {

                        $productId =
                            (int)
                            $item['product_id'];

                        if (
                            ! isset(
                                $products[$productId]
                            )
                        ) {

                            throw new \Exception(
                                'Produk dalam keranjang tidak valid.'
                            );
                        }

                        $productData =
                            $products[$productId];

                        $product =
                            $productData['product'];

                        $quantity =
                            (float)
                            $productData['quantity'];

                        $unitPrice =
                            (float)
                            $item['unit_price'];

                        if ($unitPrice < 0) {

                            throw new \Exception(
                                "Harga produk {$product->name} tidak valid."
                            );
                        }

                        $subtotal =
                            $unitPrice *
                            $quantity;

                        /*
                        |--------------------------------------------------------------------------
                        | CREATE SALE ITEM
                        |--------------------------------------------------------------------------
                        */

                        SaleItem::create([

                            'sale_id' => $sale->id,

                            'product_id' => $product->id,

                            'product_name' => $product->name,

                            'sku' => $product->sku,

                            'quantity' => $quantity,

                            'unit_price' => $unitPrice,

                            'discount' => 0,

                            'tax' => 0,

                            'subtotal' => $subtotal,

                            'total' => $subtotal,

                        ]);

                        /*
                        |--------------------------------------------------------------------------
                        | REDUCE STOCK (HANYA UNTUK FIXED TYPE)
                        |--------------------------------------------------------------------------
                        */

                        if ($product->rate_type !== 'duration') {
                            // Reduce direct product stock
                            $product->decrement(
                                'stock',
                                $quantity
                            );

                            // Reduce ingredient stock
                            foreach ($product->ingredients as $ingredient) {
                                $ingredientQuantity = (float) $ingredient->pivot->quantity * $quantity;

                                if ($ingredientQuantity > 0) {
                                    $ingredient->decrement('stock', $ingredientQuantity);
                                }
                            }
                        }
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | PAYMENT
                    |--------------------------------------------------------------------------
                    */

                    Payment::create([

                        'sale_id' => $sale->id,

                        'method' => $this->paymentMethod,

                        'amount' => $paidAmount,

                        'reference' => null,

                        'paid_at' => now(),

                        'notes' => $this->paymentNotes
                                ?: null,

                    ]);

                    /*
                    |--------------------------------------------------------------------------
                    | MEMBER / CUSTOMER
                    |--------------------------------------------------------------------------
                    */

                    $pointsEarned = 0;

                    if ($this->customerId) {

                        $customer =
                            Customer::query()
                                ->where(
                                    'tenant_id',
                                    $tenantId
                                )
                                ->where(
                                    'id',
                                    $this->customerId
                                )
                                ->lockForUpdate()
                                ->first();

                        /*
                        |--------------------------------------------------------------------------
                        | CUSTOMER VALIDATION
                        |--------------------------------------------------------------------------
                        */

                        if (! $customer) {

                            throw new \Exception(
                                'Customer tidak ditemukan.'
                            );
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | MEMBER POINT
                        |--------------------------------------------------------------------------
                        |
                        | Rp 1.000 = 1 Point
                        |
                        | Contoh:
                        |
                        | Rp 125.000 = 125 Points
                        |
                        */

                        if (
                            (bool)
                            $customer->is_member
                        ) {

                            $pointsEarned =
                                (int)
                                floor(
                                    $grandTotal / 1000
                                );

                            if (
                                $pointsEarned > 0
                            ) {

                                $customer->increment(
                                    'points',
                                    $pointsEarned
                                );
                            }
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | CUSTOMER STATISTICS
                        |--------------------------------------------------------------------------
                        */

                        $customer->increment(
                            'total_transactions'
                        );

                        $customer->increment(
                            'total_spent',
                            $grandTotal
                        );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | RETURN TRANSACTION RESULT
                    |--------------------------------------------------------------------------
                    */

                    return [

                        'sale' => $sale,

                        'pointsEarned' => $pointsEarned,

                    ];
                }
            );

            /*
            |--------------------------------------------------------------------------
            | RESULT
            |--------------------------------------------------------------------------
            */

            /** @var Sale $sale */
            $sale =
                $result['sale'];

            $pointsEarned =
                (int)
                $result['pointsEarned'];

            /*
            |--------------------------------------------------------------------------
            | RECEIPT URL
            |--------------------------------------------------------------------------
            */

            $receiptUrl =
                route(
                    'receipt.show',
                    [
                        'tenant' => $tenant->getRouteKey(),

                        'sale' => $sale->getRouteKey(),
                    ]
                )
                .'?size=80mm';

            /*
            |--------------------------------------------------------------------------
            | RESET POS
            |--------------------------------------------------------------------------
            */

            $this->cart = [];

            $this->search = '';

            $this->showCheckout = false;

            $this->paymentMethod = 'cash';

            $this->paidAmount = 0;

            $this->customerId = null;

            $this->paymentNotes = '';

            // Reset table state
            $this->tableId = null;

            $this->activeTableId = null;

            $this->currentBillId = null;

            /*
            |--------------------------------------------------------------------------
            | PAYMENT SUCCESS EVENT
            |--------------------------------------------------------------------------
            |
            | Event ini ditangkap oleh Alpine di Blade:
            |
            | x-on:payment-success.window
            |
            */

            $this->dispatch(
                'payment-success',

                invoice: $sale->invoice_number,

                total: $grandTotal,

                change: $changeAmount,

                receiptUrl: $receiptUrl,

                pointsEarned: $pointsEarned
            );

        } catch (Throwable $e) {

            /*
            |--------------------------------------------------------------------------
            | TRANSACTION ERROR
            |--------------------------------------------------------------------------
            */

            Notification::make()
                ->title('Transaksi gagal')
                ->body(
                    $e->getMessage()
                )
                ->danger()
                ->send();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GENERATE INVOICE NUMBER
    |--------------------------------------------------------------------------
    */

    protected function generateInvoiceNumber(
        int $tenantId
    ): string {

        $prefix =
            'INV-'.
            now()->format('Ymd').
            '-';

        $lastInvoice =
            Sale::query()
                ->where(
                    'tenant_id',
                    $tenantId
                )
                ->where(
                    'invoice_number',
                    'like',
                    $prefix.'%'
                )
                ->orderByDesc('id')
                ->value(
                    'invoice_number'
                );

        if (! $lastInvoice) {

            $number = 1;

        } else {

            $lastNumber =
                (int)
                str_replace(
                    $prefix,
                    '',
                    $lastInvoice
                );

            $number =
                $lastNumber + 1;
        }

        return $prefix.
            str_pad(
                (string) $number,
                4,
                '0',
                STR_PAD_LEFT
            );
    }

    /*
    |--------------------------------------------------------------------------
    | RECALCULATE CART ITEM
    |--------------------------------------------------------------------------
    */

    protected function recalculateCartItem(
        int $productId
    ): void {

        if (
            ! isset(
                $this->cart[$productId]
            )
        ) {

            return;
        }

        $quantity =
            (float)
            $this->cart[$productId]['quantity'];

        $unitPrice =
            (float)
            $this->cart[$productId]['unit_price'];

        $subtotal =
            $quantity *
            $unitPrice;

        $this->cart[$productId]['subtotal'] =
            $subtotal;

        $this->cart[$productId]['total'] =
            $subtotal;
    }

    /*
    |--------------------------------------------------------------------------
    | SUBTOTAL
    |--------------------------------------------------------------------------
    */

    public function getSubtotalProperty(): float
    {
        return (float)
            collect($this->cart)
                ->sum(
                    function ($item) {

                        return (float)
                            ($item['total'] ?? 0);
                    }
                );
    }

    /*
    |--------------------------------------------------------------------------
    | CART COUNT
    |--------------------------------------------------------------------------
    */

    public function getCartCountProperty(): int
    {
        return (int)
            collect($this->cart)
                ->sum(
                    function ($item) {

                        return (float)
                        (
                            $item['quantity']
                            ?? 0
                        );
                    }
                );
    }

    /*
    |--------------------------------------------------------------------------
    | DURATION PRODUCTS (Hour Rate)
    |--------------------------------------------------------------------------
    */

    public function getCartHasDurationProductsProperty(): bool
    {
        foreach ($this->cart as $item) {
            if (isset($item['is_duration']) && $item['is_duration']) {
                return true;
            }
        }

        return false;
    }

    public function getCartDurationTotalProperty(): int
    {
        $total = 0;
        foreach ($this->cart as $item) {
            if (isset($item['is_duration']) && $item['is_duration']) {
                $total += (int) ($item['quantity'] ?? 0);
            }
        }

        return $total;
    }

    public function formatDuration(int $minutes): string
    {
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;

        if ($hours > 0 && $mins > 0) {
            return "{$hours}j {$mins}m";
        } elseif ($hours > 0) {
            return "{$hours} jam";
        } else {
            return "{$mins} menit";
        }
    }

    /*
    |--------------------------------------------------------------------------
    | TAX RATE
    |--------------------------------------------------------------------------
    */

    public function getTaxRateProperty(): float
    {
        $tenant = Filament::getTenant();

        if (! $tenant) {
            return 11.00;
        }

        $receiptSetting = $tenant->receiptSetting;

        if (! $receiptSetting) {
            return 11.00;
        }

        return (float) $receiptSetting->tax_rate;
    }

    public function getShowTaxProperty(): bool
    {
        // Ambil dari tenant settings
        $tenant = Filament::getTenant();

        if (! $tenant) {
            return false;
        }

        $receiptSetting = $tenant->receiptSetting;

        if (! $receiptSetting) {
            return false;
        }

        return (bool) $receiptSetting->show_tax;
    }

    /*
    |--------------------------------------------------------------------------
    | TAX AMOUNT
    |--------------------------------------------------------------------------
    */

    public function getTaxAmountProperty(): float
    {
        // Harga sudah termasuk PPN, hitung backwards
        // PPN = Total × (rate / (100 + rate))
        // Contoh: 111.000 × 11/111 = 11.000
        if (! $this->showTax || $this->subtotal <= 0) {
            return 0;
        }

        $rate = $this->taxRate;
        $divisor = 100 + $rate;

        return round($this->subtotal * ($rate / $divisor), 2);
    }

    /*
    |--------------------------------------------------------------------------
    | TOTAL (Include Tax - Harga Sudah Termasuk)
    |--------------------------------------------------------------------------
    */

    public function getTotalProperty(): float
    {
        // Total = Subtotal (harga jual yang dibayar customer)
        return $this->subtotal;
    }

    /*
    |--------------------------------------------------------------------------
    | SUBTOTAL BEFORE TAX (Harga Sebelum PPN)
    |--------------------------------------------------------------------------
    */

    public function getSubtotalBeforeTaxProperty(): float
    {
        // Subtotal sebelum PPN = Total - PPN
        return round($this->subtotal - $this->taxAmount, 2);
    }

    /*
    |--------------------------------------------------------------------------
    | CHANGE AMOUNT
    |--------------------------------------------------------------------------
    */

    public function getChangeAmountProperty(): float
    {
        $paid =
            (float)
            $this->paidAmount;

        $total =
            (float)
            $this->total;

        return max(
            0,
            $paid - $total
        );
    }

    /*
    |--------------------------------------------------------------------------
    | REMAINING PAYMENT
    |--------------------------------------------------------------------------
    */

    public function getRemainingPaymentProperty(): float
    {
        $paid =
            (float)
            $this->paidAmount;

        $total =
            (float)
            $this->total;

        return max(
            0,
            $total - $paid
        );
    }

    /*
    |--------------------------------------------------------------------------
    | PRINT RECEIPT
    |--------------------------------------------------------------------------
    */

    public function printReceipt(
        int $saleId,
        string $paperSize = '80mm'
    ): void {

        $tenant =
            Filament::getTenant();

        if (! $tenant) {
            return;
        }

        $tenantId =
            (int)
            $tenant->getKey();

        $sale =
            Sale::query()
                ->where(
                    'tenant_id',
                    $tenantId
                )
                ->find(
                    $saleId
                );

        if (! $sale) {
            return;
        }

        if (
            ! in_array(
                $paperSize,
                [
                    '58mm',
                    '80mm',
                ],
                true
            )
        ) {

            $paperSize =
                '80mm';
        }

        $url =
            route(
                'receipt.show',
                [
                    'tenant' => $tenant->getRouteKey(),

                    'sale' => $sale->getRouteKey(),
                ]
            )
            .'?size='.
            urlencode(
                $paperSize
            );

        $this->dispatch(
            'open-receipt',
            url: $url
        );
    }

    /*
    |--------------------------------------------------------------------------
    | PRODUCTS
    |--------------------------------------------------------------------------
    */

    public function getProductsProperty(): Collection
    {
        $tenant =
            Filament::getTenant();

        if (! $tenant) {
            return collect();
        }

        $tenantId =
            (int)
            $tenant->getKey();

        return Product::query()
            ->where(
                'tenant_id',
                $tenantId
            )
            ->where(
                'is_active',
                true
            )
            ->when(
                $this->selectedCategoryId,
                fn ($query) => $query->where('category_id', $this->selectedCategoryId)
            )
            ->when(
                filled($this->search),
                function ($query) {

                    $search =
                        trim(
                            $this->search
                        );

                    $query->where(
                        function ($query) use ($search) {

                            $query
                                ->where(
                                    'name',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'sku',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'barcode',
                                    'like',
                                    "%{$search}%"
                                );
                        }
                    );
                }
            )
            ->orderBy('name')
            ->limit(50)
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | CATEGORIES
    |--------------------------------------------------------------------------
    */

    public function getCategoriesProperty(): Collection
    {
        $tenant = Filament::getTenant();

        if (! $tenant) {
            return collect();
        }

        return Category::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | OPEN ADD CUSTOMER
    |--------------------------------------------------------------------------
    */

    public function openAddCustomer(): void
    {
        $this->showAddCustomer = true;
        $this->newCustomerName = '';
        $this->newCustomerEmail = '';
        $this->newCustomerPhone = '';
    }

    /*
    |--------------------------------------------------------------------------
    | CLOSE ADD CUSTOMER
    |--------------------------------------------------------------------------
    */

    public function closeAddCustomer(): void
    {
        $this->showAddCustomer = false;
    }

    /*
    |--------------------------------------------------------------------------
    | SAVE CUSTOMER
    |--------------------------------------------------------------------------
    */

    public function saveCustomer(): void
    {
        $tenant = Filament::getTenant();

        if (! $tenant) {
            Notification::make()
                ->title('Tenant tidak ditemukan')
                ->danger()
                ->send();

            return;
        }

        if (empty(trim($this->newCustomerName))) {
            Notification::make()
                ->title('Nama customer wajib diisi')
                ->danger()
                ->send();

            return;
        }

        if (empty(trim($this->newCustomerPhone))) {
            Notification::make()
                ->title('Nomor telepon wajib diisi')
                ->danger()
                ->send();

            return;
        }

        $tenantId = (int) $tenant->getKey();

        $customer = Customer::create([
            'tenant_id' => $tenantId,
            'name' => trim($this->newCustomerName),
            'email' => ! empty(trim($this->newCustomerEmail)) ? trim($this->newCustomerEmail) : null,
            'phone' => trim($this->newCustomerPhone),
            'is_active' => true,
            'is_member' => false,
        ]);

        $this->customerId = (int) $customer->id;
        $this->showAddCustomer = false;

        Notification::make()
            ->title('Customer berhasil ditambahkan')
            ->success()
            ->send();
    }

    /*
    |--------------------------------------------------------------------------
    | CUSTOMERS
    |--------------------------------------------------------------------------
    */

    public function getCustomersProperty(): Collection
    {
        $tenant =
            Filament::getTenant();

        if (! $tenant) {
            return collect();
        }

        $tenantId =
            (int)
            $tenant->getKey();

        $query = Customer::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true);

        if (! empty(trim($this->customerSearch))) {
            $search = trim($this->customerSearch);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('name')->limit(50)->get();
    }

    /*
    |--------------------------------------------------------------------------
    | TABLES
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | AVAILABLE TABLES
    |--------------------------------------------------------------------------
    */

    public function getAvailableTablesProperty(): Collection
    {
        $tenant = Filament::getTenant();

        if (! $tenant) {
            return collect();
        }

        $tenantId = (int) $tenant->getKey();

        return Table::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where('status', 'available')
            ->orderBy('name')
            ->get();
    }

    public function getTablesProperty(): Collection
    {
        $tenant = Filament::getTenant();

        if (! $tenant) {
            return collect();
        }

        $tenantId = (int) $tenant->getKey();

        return Table::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where('status', 'available')
            ->orderBy('name')
            ->get();
    }

    public function getActiveTablesProperty(): Collection
    {
        $tenant = Filament::getTenant();

        if (! $tenant) {
            return collect();
        }

        $tenantId = (int) $tenant->getKey();

        return Table::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where('status', 'active')
            ->with(['sales' => function ($query) {
                $query->whereIn('status', ['pending', 'completed'])
                    ->orderByDesc('created_at');
            }])
            ->orderBy('name')
            ->get();
    }

    public function getSelectedTableProperty(): ?Table
    {
        if (! $this->tableId) {
            return null;
        }

        $tenant = Filament::getTenant();

        if (! $tenant) {
            return null;
        }

        return Table::query()
            ->where('tenant_id', $tenant->id)
            ->where('id', $this->tableId)
            ->first();
    }

    public function getCurrentBillProperty(): ?Sale
    {
        if (! $this->currentBillId) {
            return null;
        }

        return Sale::with(['items', 'table'])
            ->where('id', $this->currentBillId)
            ->first();
    }

    /*
    |--------------------------------------------------------------------------
    | SELECT TABLE
    |--------------------------------------------------------------------------
    */

    public function selectTable(?int $tableId): void
    {
        $this->tableId = $tableId;

        if (! $tableId) {
            $this->currentBillId = null;
            $this->activeTableId = null;
            $this->cart = [];

            return;
        }

        $tenant = Filament::getTenant();

        if (! $tenant) {
            return;
        }

        $table = Table::query()
            ->where('tenant_id', $tenant->id)
            ->where('id', $tableId)
            ->first();

        if (! $table) {
            return;
        }

        // Always check for pending orders (status='open', 'pending' = unpaid)
        $pendingOrders = Sale::query()
            ->where('table_id', $tableId)
            ->whereIn('status', ['open', 'pending'])
            ->with(['items.product'])
            ->get();

        if ($pendingOrders->count() > 0) {
            // Has pending cash orders - load them into cart
            $this->activeTableId = $tableId;
            $this->currentBillId = $pendingOrders->first()->id;
            $this->cart = [];

            // Merge all pending order items into cart
            foreach ($pendingOrders as $order) {
                foreach ($order->items as $item) {
                    $productId = (int) $item->product_id;
                    $isDuration = $item->product && $item->product->rate_type === 'duration';

                    if (isset($this->cart[$productId])) {
                        // Add to existing quantity
                        $this->cart[$productId]['quantity'] += (float) $item->quantity;
                        $this->cart[$productId]['subtotal'] = $this->cart[$productId]['quantity'] * $this->cart[$productId]['unit_price'];
                        $this->cart[$productId]['total'] = $this->cart[$productId]['subtotal'];
                    } else {
                        $this->cart[$productId] = [
                            'product_id' => $productId,
                            'product_name' => $item->product_name ?? $item->product?->name ?? 'Item',
                            'sku' => $item->sku,
                            'unit_price' => (float) $item->unit_price,
                            'quantity' => (float) $item->quantity,
                            'subtotal' => (float) $item->subtotal,
                            'total' => (float) $item->total,
                            'sale_item_id' => $item->id,
                            'sale_id' => $order->id,
                            'is_duration' => $isDuration,
                            'rate_type' => $item->product?->rate_type ?? 'fixed',
                            'rate' => (float) ($item->product?->rate ?? 1),
                        ];
                    }
                }
            }
        } elseif ($table->status === 'active') {
            // Table has orders but no unpaid cash bill (all QRIS/Transfer paid)
            // Can add new items - start fresh cart
            $this->currentBillId = null;
            $this->activeTableId = $tableId;
            $this->cart = [];
        } elseif ($pendingOrders->count() === 0) {
            // No pending orders at all - start fresh cart
            $this->currentBillId = null;
            $this->activeTableId = null;
            $this->cart = [];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | LOAD BILL TO CART
    |--------------------------------------------------------------------------
    */

    protected function loadBillToCart(Sale $sale): void
    {
        $this->cart = [];

        foreach ($sale->items as $item) {
            $productId = (int) $item->product_id;
            $isDuration = $item->product && $item->product->rate_type === 'duration';

            $this->cart[$productId] = [
                'product_id' => $productId,
                'product_name' => $item->product_name ?? $item->product?->name ?? 'Item',
                'sku' => $item->sku,
                'unit_price' => (float) $item->unit_price,
                'quantity' => (float) $item->quantity,
                'subtotal' => (float) $item->subtotal,
                'total' => (float) $item->total,
                'sale_item_id' => $item->id,
                'is_duration' => $isDuration,
                'rate_type' => $item->product?->rate_type ?? 'fixed',
                'rate' => (float) ($item->product?->rate ?? 1),
            ];
        }

        $this->customerId = $sale->customer_id;
    }

    /*
    |--------------------------------------------------------------------------
    | SAVE TO TABLE
    |--------------------------------------------------------------------------
    */

    public function saveToTable(): void
    {
        if (empty($this->cart)) {
            Notification::make()
                ->title('Keranjang kosong')
                ->warning()
                ->send();

            return;
        }

        if (! $this->tableId) {
            Notification::make()
                ->title('Pilih meja terlebih dahulu')
                ->warning()
                ->send();

            return;
        }

        $tenant = Filament::getTenant();

        if (! $tenant) {
            Notification::make()
                ->title('Tenant tidak ditemukan')
                ->danger()
                ->send();

            return;
        }

        $tenantId = (int) $tenant->getKey();

        $table = Table::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $this->tableId)
            ->first();

        if (! $table) {
            Notification::make()
                ->title('Meja tidak ditemukan')
                ->danger()
                ->send();

            return;
        }

        // Check for unpaid cash bill
        $activeBill = $table->activeBill;

        if ($table->status === 'active' && $activeBill) {
            // Table has unpaid cash bill, add items to it
            $this->addItemsToExistingBill($activeBill);

            return;
        }

        // Create new bill (for new table or table with only paid orders)
        $this->createNewBillForTable($table);
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE NEW BILL FOR TABLE
    |--------------------------------------------------------------------------
    */

    protected function createNewBillForTable(Table $table): void
    {
        $tenant = Filament::getTenant();
        $tenantId = (int) $tenant->getKey();

        DB::beginTransaction();

        try {
            // Calculate totals
            $subtotal = 0;
            foreach ($this->cart as $item) {
                $subtotal += (float) $item['subtotal'];
            }

            // Generate invoice number
            $invoiceNumber = 'INV-'.date('Ymd').'-'.str_pad(Sale::where('tenant_id', $tenantId)->count() + 1, 4, '0', STR_PAD_LEFT);

            // Create sale
            $sale = Sale::create([
                'tenant_id' => $tenantId,
                'table_id' => $this->tableId,
                'customer_id' => $this->customerId,
                'user_id' => auth()->id(),
                'invoice_number' => $invoiceNumber,
                'status' => 'open',
                'started_at' => now(),
                'subtotal' => $subtotal,
                'discount' => 0,
                'tax' => 0,
                'grand_total' => $subtotal,
                'paid_amount' => 0,
                'change_amount' => 0,
            ]);

            // Create sale items
            foreach ($this->cart as $item) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'sku' => $item['sku'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['subtotal'],
                    'total' => $item['total'],
                ]);
            }

            // Update table status
            $table->update(['status' => 'active']);

            DB::commit();

            $this->currentBillId = $sale->id;
            $this->activeTableId = $this->tableId;

            Notification::make()
                ->title('Bill disimpan ke meja '.$table->name)
                ->success()
                ->send();

        } catch (Throwable $e) {
            DB::rollBack();

            Notification::make()
                ->title('Gagal menyimpan bill')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | ADD ITEMS TO EXISTING BILL
    |--------------------------------------------------------------------------
    */

    protected function addItemsToExistingBill(?Sale $sale): void
    {
        if (! $sale) {
            return;
        }

        DB::beginTransaction();

        try {
            $additionalSubtotal = 0;

            // Create new sale items
            foreach ($this->cart as $item) {
                // Check if product already exists in bill
                $existingItem = SaleItem::where('sale_id', $sale->id)
                    ->where('product_id', $item['product_id'])
                    ->first();

                if ($existingItem) {
                    // Update quantity
                    $newQuantity = $existingItem->quantity + $item['quantity'];
                    $existingItem->update([
                        'quantity' => $newQuantity,
                        'subtotal' => $newQuantity * $existingItem->unit_price,
                        'total' => $newQuantity * $existingItem->unit_price,
                    ]);
                    $additionalSubtotal += $item['subtotal'];
                } else {
                    // Create new item
                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_id' => $item['product_id'],
                        'product_name' => $item['product_name'],
                        'sku' => $item['sku'] ?? null,
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'subtotal' => $item['subtotal'],
                        'total' => $item['total'],
                    ]);
                    $additionalSubtotal += $item['subtotal'];
                }
            }

            // Update sale totals
            $newSubtotal = (float) $sale->subtotal + $additionalSubtotal;
            $sale->update([
                'subtotal' => $newSubtotal,
                'grand_total' => $newSubtotal,
            ]);

            DB::commit();

            // Reload cart
            $this->loadBillToCart($sale->fresh(['items']));

            Notification::make()
                ->title('Item ditambahkan ke bill')
                ->success()
                ->send();

        } catch (Throwable $e) {
            DB::rollBack();

            Notification::make()
                ->title('Gagal menambahkan item')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CLEAR TABLE / RESET
    |--------------------------------------------------------------------------
    */

    public function clearTable(): void
    {
        $this->tableId = null;
        $this->activeTableId = null;
        $this->currentBillId = null;
        $this->cart = [];
        $this->customerId = null;
        $this->paymentMethod = 'cash';
        $this->paidAmount = 0;
        $this->paymentNotes = '';
        $this->showCheckout = false;
    }
}
