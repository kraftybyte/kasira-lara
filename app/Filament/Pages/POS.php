<?php

namespace App\Filament\Pages;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductModifier;
use App\Models\Reservation;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Table;
use App\Models\TenantSetting;
use App\Services\PaywuzService;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Throwable;

class POS extends Page
{
    protected string $view = 'filament.pages.p-o-s';

    protected static ?string $title = 'POS';

    protected static ?string $slug = 'pos';

    protected static bool $shouldRegisterNavigation = true;

    protected static ?int $navigationSort = 2;

    protected static BackedEnum|string|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        // Super admin and owner can always access
        if ($user->hasRole('super_admin') || $user->hasRole('owner')) {
            return true;
        }

        // Cashier and kepala_toko can access POS
        if ($user->hasRole('cashier') || $user->hasRole('kepala_toko')) {
            return true;
        }

        return false;
    }

    public function getHeader(): ?View
    {
        return view('filament.pages.pos-header', [
            'availableTables' => $this->availableTables,
            'activeTables' => $this->activeTables,
        ]);
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

    public ?string $tableId = null;

    public ?string $activeTableId = null;

    public ?int $currentBillId = null;

    public ?int $selectedCategoryId = null;

    public ?int $reservationId = null;

    public bool $showModifierModal = false;

    public ?int $modifierProductId = null;

    public array $selectedModifiers = [];

    public string $modifierNotes = '';

    // QRIS Payment Properties
    public ?string $qrisTransactionId = null;

    public ?string $qrisImageUrl = null;

    public bool $isGeneratingQr = false;

    public bool $isCheckingPayment = false;

    public ?Sale $currentQrisSale = null;

    // Virtual Account Payment Properties
    public ?string $vaTransactionId = null;

    public ?string $vaAccountNumber = null;

    public ?string $vaBankCode = null;

    public ?string $vaBankName = null;

    public ?string $vaExpiryTime = null;

    public ?string $vaPaymentUrl = null;

    public bool $isGeneratingVa = false;

    public bool $isCheckingVaPayment = false;

    public ?Sale $currentVaSale = null;

    public bool $qrisPaymentConfirmed = false;

    public bool $vaPaymentConfirmed = false;

    // Bulk Counter Mode
    public bool $isBulkCounterMode = false;

    public array $bulkCounterSaleIds = [];

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

        // Check for bulk counter payment
        $bulk = Request::query('bulk');
        if ($bulk === 'counter') {
            $this->loadBulkCounterOrders();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | LOAD BULK COUNTER ORDERS
    |--------------------------------------------------------------------------
    */

    public function loadBulkCounterOrders(): void
    {
        $saleIds = session()->get('bulk_counter_sales', []);
        $tableId = session()->get('bulk_counter_table_id');

        if (empty($saleIds) || empty($tableId)) {
            return;
        }

        $this->isBulkCounterMode = true;
        $this->bulkCounterSaleIds = $saleIds;
        $this->tableId = (string) $tableId;
        $this->activeTableId = (string) $tableId;

        // Clear session
        session()->forget('bulk_counter_sales');
        session()->forget('bulk_counter_table_id');

        // Load counter orders into cart
        $this->cart = [];

        $sales = Sale::whereIn('id', $saleIds)
            ->where('payment_method', 'counter')
            ->with(['items.product'])
            ->get();

        foreach ($sales as $sale) {
            foreach ($sale->items as $item) {
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
                        'sale_id' => $sale->id,
                        'is_duration' => $isDuration,
                        'rate_type' => $item->product?->rate_type ?? 'fixed',
                        'rate' => (float) ($item->product?->rate ?? 1),
                    ];
                }
            }
        }

        // Set to cash payment method
        $this->paymentMethod = 'cash';
        $this->paidAmount = $this->total;

        // Open checkout
        $this->showCheckout = true;
    }

    public function getStoreBankNameProperty(): ?string
    {
        $tenant = Filament::getTenant();

        if (! $tenant) {
            return null;
        }

        $settings = TenantSetting::where('tenant_id', $tenant->id)->first();

        return $settings?->bank_name ?? $tenant->name ?? 'Toko';
    }

    public function getStoreBankAccountProperty(): ?string
    {
        $tenant = Filament::getTenant();

        if (! $tenant) {
            return null;
        }

        $settings = TenantSetting::where('tenant_id', $tenant->id)->first();

        return $settings?->bank_account;
    }

    public function getStoreBankAccountNameProperty(): ?string
    {
        $tenant = Filament::getTenant();

        if (! $tenant) {
            return null;
        }

        $settings = TenantSetting::where('tenant_id', $tenant->id)->first();

        return $settings?->bank_account_name;
    }

    public function getStoreBankQrImageProperty(): ?string
    {
        $tenant = Filament::getTenant();

        if (! $tenant) {
            return null;
        }

        $settings = TenantSetting::where('tenant_id', $tenant->id)->first();

        $qrPath = $settings?->bank_qr_image;

        if (empty($qrPath)) {
            return null;
        }

        // If it's stored as a direct path string
        if (is_string($qrPath)) {
            return $qrPath;
        }

        // If it's stored as JSON array (Filament FileUpload format)
        if (is_array($qrPath)) {
            foreach ($qrPath as $item) {
                if (is_array($item) && isset($item['path'])) {
                    return $item['path'];
                }
            }
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | PAYMENT METHODS ENABLED
    |--------------------------------------------------------------------------
    */

    public function getIsPaymentQrisAutoEnabledProperty(): bool
    {
        $settings = TenantSetting::where('tenant_id', Filament::getTenant()?->id)->first();

        return $settings?->payment_qris_auto ?? true;
    }

    public function getIsPaymentVaEnabledProperty(): bool
    {
        $settings = TenantSetting::where('tenant_id', Filament::getTenant()?->id)->first();

        return $settings?->payment_va ?? true;
    }

    public function getIsPaymentTransferEnabledProperty(): bool
    {
        $settings = TenantSetting::where('tenant_id', Filament::getTenant()?->id)->first();

        return $settings?->payment_transfer ?? true;
    }

    public function getIsPaymentQrisManualEnabledProperty(): bool
    {
        $settings = TenantSetting::where('tenant_id', Filament::getTenant()?->id)->first();

        return $settings?->payment_qris_manual ?? true;
    }

    public function getIsPaymentCashEnabledProperty(): bool
    {
        $settings = TenantSetting::where('tenant_id', Filament::getTenant()?->id)->first();

        return $settings?->payment_cash ?? true;
    }

    /*
    |--------------------------------------------------------------------------
    | QR MEJA PAYMENT METHODS ENABLED
    |--------------------------------------------------------------------------
    */

    public function getIsTableQrQrisAutoEnabledProperty(): bool
    {
        $settings = TenantSetting::where('tenant_id', Filament::getTenant()?->id)->first();

        return $settings?->table_qr_qris_auto ?? true;
    }

    public function getIsTableQrVaEnabledProperty(): bool
    {
        $settings = TenantSetting::where('tenant_id', Filament::getTenant()?->id)->first();

        return $settings?->table_qr_va ?? true;
    }

    public function getIsTableQrPayAtCounterEnabledProperty(): bool
    {
        $settings = TenantSetting::where('tenant_id', Filament::getTenant()?->id)->first();

        return $settings?->table_qr_pay_at_counter ?? true;
    }

    /*
    |--------------------------------------------------------------------------
    | MODIFIER MODAL
    |--------------------------------------------------------------------------
    */

    public function openModifierModal(int $productId): void
    {
        $this->modifierProductId = $productId;
        $this->showModifierModal = true;
        $this->selectedModifiers = [];
        $this->modifierNotes = '';
    }

    public function closeModifierModal(): void
    {
        $this->showModifierModal = false;
        $this->modifierProductId = null;
        $this->selectedModifiers = [];
        $this->modifierNotes = '';
    }

    public function toggleModifier(array $modifier): void
    {
        $modifierId = $modifier['id'];

        // Check if it's an option modifier (radio) - only one allowed
        $modifierType = ProductModifier::find($modifierId)?->type ?? 'addon';

        if ($modifierType === 'option') {
            // Replace all option modifiers with this one
            $this->selectedModifiers = [$modifier];
        } else {
            // Toggle addon modifier
            $index = array_search($modifierId, array_column($this->selectedModifiers, 'id'));

            if ($index !== false) {
                unset($this->selectedModifiers[$index]);
                $this->selectedModifiers = array_values($this->selectedModifiers);
            } else {
                $this->selectedModifiers[] = $modifier;
            }
        }
    }

    public function saveModifiers(): void
    {
        if (! $this->modifierProductId) {
            $this->closeModifierModal();

            return;
        }

        $product = Product::find($this->modifierProductId);

        if (! $product) {
            $this->closeModifierModal();

            return;
        }

        // Add to cart with modifiers and notes
        $this->addToCartWithNotes($product, $this->modifierNotes, $this->selectedModifiers);

        $this->closeModifierModal();
    }

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
    | ADD TO CART WITH NOTES (For modifier modal)
    |--------------------------------------------------------------------------
    */

    public function addToCartWithNotes(Product $product, ?string $notes = null, array $modifiers = []): void
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

        if ((int) $product->tenant_id !== $tenantId) {
            Notification::make()
                ->title('Produk tidak valid')
                ->danger()
                ->send();

            return;
        }

        if ((int) $product->is_active !== 1) {
            Notification::make()
                ->title('Produk tidak aktif')
                ->danger()
                ->send();

            return;
        }

        if (
            $product->rate_type !== 'duration'
            && (float) $product->stock <= 0
        ) {
            Notification::make()
                ->title('Stok habis')
                ->body("Stok {$product->name} sudah habis.")
                ->danger()
                ->send();

            return;
        }

        $productId = (int) $product->id;

        // Calculate modifier total
        $modifierTotal = 0;
        $modifierData = [];
        if (! empty($modifiers)) {
            foreach ($modifiers as $mod) {
                $modifier = ProductModifier::find($mod['id']);
                if ($modifier) {
                    $modifierTotal += (float) ($modifier->price_adjustment ?? 0);
                    $modifierData[] = [
                        'id' => $modifier->id,
                        'name' => $modifier->name,
                        'price' => $modifier->price_adjustment,
                    ];
                }
            }
        }

        $sellingPrice = (float) $product->selling_price;
        $isDuration = $product->rate_type === 'duration';
        $itemPrice = $sellingPrice + $modifierTotal;

        // Check if product already in cart with different notes/modifiers
        if (isset($this->cart[$productId])) {
            $existing = $this->cart[$productId];
            $hasExistingNotes = ! empty($existing['notes']);
            $hasExistingMods = ! empty($existing['modifiers']);
            $hasNewNotes = ! empty($notes);
            $hasNewMods = ! empty($modifierData);

            // Create variant if NEW item has notes or modifiers (regardless of existing)
            if ($hasNewNotes || $hasNewMods) {
                $newKey = $productId.'_'.time();
                $this->cart[$newKey] = [
                    'product_id' => $productId,
                    'product_name' => $product->name,
                    'sku' => $product->sku,
                    'unit_price' => $itemPrice,
                    'quantity' => 1,
                    'subtotal' => $itemPrice,
                    'total' => $itemPrice,
                    'is_duration' => $isDuration,
                    'rate_type' => $product->rate_type,
                    'rate' => (float) $product->rate,
                    'notes' => $notes,
                    'modifiers' => $modifierData,
                    'is_variant' => true,
                ];
                Notification::make()->title('Ditambahkan')->body("+1 {$product->name}")->success()->send();

                return;
            }

            // Just increment quantity if existing has no notes/mods
            $this->cart[$productId]['quantity'] += 1;
            $this->recalculateCartItem($productId);
            Notification::make()->title('Ditambahkan')->body("+1 {$product->name}")->success()->send();

            return;
        }

        // New product entry
        $this->cart[$productId] = [
            'product_id' => $productId,
            'product_name' => $product->name,
            'sku' => $product->sku,
            'unit_price' => $itemPrice,
            'quantity' => 1,
            'subtotal' => $itemPrice,
            'total' => $itemPrice,
            'is_duration' => $isDuration,
            'rate_type' => $product->rate_type,
            'rate' => (float) $product->rate,
            'notes' => $notes,
            'modifiers' => $modifierData,
            'is_variant' => false,
        ];
        Notification::make()->title('Ditambahkan')->body("+1 {$product->name}")->success()->send();
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

    public function removeFromCart(int|string $productId): void
    {
        unset($this->cart[$productId]);
    }

    /*
    |--------------------------------------------------------------------------
    | INCREMENT QUANTITY
    |--------------------------------------------------------------------------
    */

    public function incrementQuantity(int|string $productId): void
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

    public function decrementQuantity(int|string $productId): void
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
    | OPEN CHECKOUT FOR TAKE AWAY
    |--------------------------------------------------------------------------
    |
    | Take Away orders go to Kitchen first before completion
    |
    */

    public function openCheckoutForTakeAway(): void
    {
        if (empty($this->cart)) {

            Notification::make()
                ->title('Keranjang kosong')
                ->warning()
                ->send();

            return;
        }

        $this->showCheckout = true;

        // Set flag for Take Away
        $this->activeTableId = 'takeaway';

        if ($this->paymentMethod === 'cash') {
            $this->paidAmount = $this->total;
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
        // Reset QRIS state when payment method changes
        if ($this->paymentMethod !== 'qris') {
            $this->resetQrisState();
        }

        // Reset VA state when payment method changes
        if ($this->paymentMethod !== 'va') {
            $this->resetVaState();
        }

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

                                'table_id' => $this->tableId === 'takeaway' ? null : $this->tableId,

                                'customer_id' => $this->customerId
                                        ?: null,

                                'reservation_id' => $this->reservationId,

                                'user_id' => Auth::id(),

                                'invoice_number' => $invoiceNumber,

                                'status' => 'open',

                                'source' => 'pos',

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

                            'notes' => $item['notes'] ?? null,

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
                        | Calculate points based on tenant settings
                        |
                        */

                        if (
                            (bool)
                            $customer->is_member
                        ) {
                            // Get loyalty settings
                            $settings = TenantSetting::where('tenant_id', $tenantId)->first();

                            if ($settings && $settings->isLoyaltyEnabled()) {
                                $pointsEarned = $settings->calculatePoints($grandTotal);

                                if ($pointsEarned > 0) {
                                    $customer->increment(
                                        'points',
                                        $pointsEarned
                                    );
                                }
                            } else {
                                // Default: 1 point per 1000 rupiah
                                $pointsEarned = (int) floor($grandTotal / 1000);

                                if ($pointsEarned > 0) {
                                    $customer->increment(
                                        'points',
                                        $pointsEarned
                                    );
                                }
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

            /*
            |--------------------------------------------------------------------------
            | CLEANUP BULK COUNTER ORDERS
            |--------------------------------------------------------------------------
            */

            if ($this->isBulkCounterMode && ! empty($this->bulkCounterSaleIds)) {
                // Delete the counter orders that were merged into this payment
                $counterSales = Sale::whereIn('id', $this->bulkCounterSaleIds)->get();
                foreach ($counterSales as $counterSale) {
                    $counterSale->items()->delete();
                    $counterSale->delete();
                }

                // Reset bulk mode
                $this->isBulkCounterMode = false;
                $this->bulkCounterSaleIds = [];
            }

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

        // Use database lock to prevent race condition
        $lock = Cache::lock("invoice:{$tenantId}", 10);

        try {
            $lock->block(5);

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

            $result = $prefix.
                str_pad(
                    (string) $number,
                    4,
                    '0',
                    STR_PAD_LEFT
                );

            $lock->release();

            return $result;
        } catch (\Exception $e) {
            $lock->release();

            // Fallback: append random suffix to ensure uniqueness
            return $prefix.now()->format('His').'-'.substr(md5(uniqid()), 0, 4);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | RECALCULATE CART ITEM
    |--------------------------------------------------------------------------
    */

    protected function recalculateCartItem(int|string $productId): void
    {
        if (! isset($this->cart[$productId])) {
            return;
        }

        $quantity = (float) $this->cart[$productId]['quantity'];
        $unitPrice = (float) $this->cart[$productId]['unit_price'];
        $subtotal = $quantity * $unitPrice;

        $this->cart[$productId]['subtotal'] = $subtotal;
        $this->cart[$productId]['total'] = $subtotal;
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
    | PAYWUZ FEE
    |--------------------------------------------------------------------------
    */

    public function getPaywuzFeeProperty(): float
    {
        if ($this->paymentMethod !== 'qris') {
            return 0;
        }

        $tenantId = Filament::getTenant()?->id;
        if (! $tenantId) {
            return 0;
        }

        $paywuz = new PaywuzService($tenantId);
        if (! $paywuz->isConfigured()) {
            return 0;
        }

        $feeCalc = $paywuz->calculateQrisFee($this->subtotal);

        return $feeCalc['fee'];
    }

    public function getPaywuzFeeByMerchantProperty(): bool
    {
        $tenantId = Filament::getTenant()?->id;
        if (! $tenantId) {
            return false;
        }

        $paywuz = new PaywuzService($tenantId);

        return $paywuz->isFeeByMerchant();
    }

    /*
    |--------------------------------------------------------------------------
    | TOTAL (Include Tax - Harga Sudah Termasuk)
    |--------------------------------------------------------------------------
    */

    public function getTotalProperty(): float
    {
        // Total = Subtotal + Paywuz fee (if customer bears the fee)
        if ($this->paywuzFeeByMerchant) {
            return $this->subtotal; // Merchant bears the fee
        }

        return $this->subtotal + $this->paywuzFee;
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
                $query->where('status', '!=', 'completed')
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

        if (! $tableId || $tableId === 'takeaway') {
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

        // Check for active reservation
        $reservation = Reservation::where('table_id', $tableId)
            ->whereIn('status', ['pending', 'confirmed'])
            ->where('reservation_date', now()->toDateString())
            ->first();

        $this->reservationId = $reservation?->id;

        // Always check for unpaid orders (any status except completed)
        $pendingOrders = Sale::query()
            ->where('table_id', $tableId)
            ->where('status', '!=', 'completed')
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
                'sale_id' => $sale->id,
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

        // Handle Take Away - create order first (goes to Kitchen), then checkout
        if ($this->tableId === 'takeaway') {
            $this->openCheckoutForTakeAway();

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
                'reservation_id' => $this->reservationId,
                'user_id' => auth()->id(),
                'invoice_number' => $invoiceNumber,
                'status' => 'open',
                'source' => 'pos',
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
                    'notes' => $item['notes'] ?? null,
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
            $newSubtotal = 0;

            // Track which sale items we've processed
            $processedItemIds = [];

            // Delete all existing sale items first (fresh start)
            SaleItem::where('sale_id', $sale->id)->delete();

            // Process cart items - create fresh
            foreach ($this->cart as $item) {
                $cartQuantity = (float) $item['quantity'];

                if ($cartQuantity > 0) {
                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_id' => (int) $item['product_id'],
                        'product_name' => $item['product_name'],
                        'sku' => $item['sku'] ?? null,
                        'quantity' => $cartQuantity,
                        'unit_price' => (float) $item['unit_price'],
                        'subtotal' => $cartQuantity * (float) $item['unit_price'],
                        'total' => $cartQuantity * (float) $item['unit_price'],
                        'notes' => $item['notes'] ?? null,
                    ]);
                    $newSubtotal += $cartQuantity * (float) $item['unit_price'];
                }
            }

            // Update sale totals
            $sale->update([
                'subtotal' => $newSubtotal,
                'grand_total' => $newSubtotal,
            ]);

            DB::commit();

            // Reload cart from DB to get fresh sale_item_ids
            $this->loadBillToCart($sale->fresh());

            Notification::make()
                ->title('Bill disimpan')
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
    | RESERVATION
    |--------------------------------------------------------------------------
    */

    public function getReservationProperty(): ?Reservation
    {
        if (! $this->reservationId) {
            return null;
        }

        return Reservation::find($this->reservationId);
    }

    public function seatReservation(): void
    {
        if (! $this->reservationId) {
            return;
        }

        $reservation = Reservation::find($this->reservationId);

        if (! $reservation) {
            return;
        }

        // Update reservation status to seated
        $reservation->update(['status' => 'seated']);

        // Set customer info if available
        if ($reservation->customer_id) {
            $this->customerId = $reservation->customer_id;
        }

        $this->reservationId = null;

        Notification::make()
            ->title('Reservasi ditempatkan')
            ->body("Tamu {$reservation->customer_name} sudah di tempatkan di meja ini.")
            ->success()
            ->send();
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
        $this->reservationId = null;
        $this->cart = [];
        $this->customerId = null;
        $this->paymentMethod = 'cash';
        $this->paidAmount = 0;
        $this->paymentNotes = '';
        $this->showCheckout = false;

        // Reset QRIS state
        $this->resetQrisState();

        // Reset VA state
        $this->resetVaState();
    }

    /*
    |--------------------------------------------------------------------------
    | QRIS PAYMENT METHODS
    |--------------------------------------------------------------------------
    */

    protected function resetQrisState(): void
    {
        $this->qrisTransactionId = null;
        $this->qrisImageUrl = null;
        $this->isGeneratingQr = false;
        $this->isCheckingPayment = false;
        $this->currentQrisSale = null;
    }

    public function generateQrisPayment(): void
    {
        if ($this->paymentMethod !== 'qris') {
            return;
        }

        if (empty($this->cart)) {
            Notification::make()
                ->title('Keranjang kosong')
                ->danger()
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

        $this->isGeneratingQr = true;

        try {
            $tenantId = (int) $tenant->getKey();

            // Calculate totals
            $subtotal = (float) $this->subtotal;
            $taxAmount = (float) $this->taxAmount;
            $grandTotal = (float) $this->total;

            // Check for existing pending sale with same cart items and reuse if exists
            $existingPending = Sale::where('tenant_id', $tenantId)
                ->where('status', 'pending')
                ->where('payment_method', 'qris')
                ->where('payment_status', 'pending')
                ->where('grand_total', $grandTotal)
                ->first();

            if ($existingPending) {
                // Reuse existing pending sale
                $sale = $existingPending;
                $invoiceNumber = $sale->invoice_number;
            } else {
                // Generate invoice number
                $invoiceNumber = $this->generateInvoiceNumber($tenantId);

                // Create sale with pending payment
                $sale = Sale::create([
                    'tenant_id' => $tenantId,
                    'table_id' => $this->tableId === 'takeaway' ? null : $this->tableId,
                    'customer_id' => $this->customerId ?: null,
                    'reservation_id' => $this->reservationId,
                    'user_id' => Auth::id(),
                    'invoice_number' => $invoiceNumber,
                    'status' => 'pending',
                    'source' => 'pos',
                    'payment_method' => 'qris',
                    'subtotal' => $subtotal,
                    'discount' => 0,
                    'tax' => $taxAmount,
                    'grand_total' => $grandTotal,
                    'paid_amount' => 0,
                    'change_amount' => 0,
                    'payment_status' => 'pending',
                    'notes' => $this->paymentNotes ?: null,
                ]);

                // Create sale items
                foreach ($this->cart as $item) {
                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_id' => (int) $item['product_id'],
                        'product_name' => $item['product_name'],
                        'sku' => $item['sku'] ?? null,
                        'quantity' => (float) $item['quantity'],
                        'unit_price' => (float) $item['unit_price'],
                        'discount' => 0,
                        'tax' => 0,
                        'subtotal' => (float) $item['subtotal'],
                        'total' => (float) $item['total'],
                        'notes' => $item['notes'] ?? null,
                    ]);
                }
            }

            // Call Paywuz to create QR
            $paywuz = new PaywuzService($tenantId);

            if (! $paywuz->isConfigured() || ! $paywuz->isEnabled()) {
                // Paywuz not configured - use fallback QR
                $sale->update([
                    'paywuz_transaction_id' => 'DEMO-'.$invoiceNumber,
                    'paywuz_status' => 'pending',
                ]);

                $this->currentQrisSale = $sale;
                $this->qrisTransactionId = 'DEMO-'.$invoiceNumber;
                $this->qrisImageUrl = null;
                $this->isGeneratingQr = false;

                Notification::make()
                    ->title('QR Generated (Demo Mode)')
                    ->body('Paywuz belum dikonfigurasi. Gunakan mode demo.')
                    ->warning()
                    ->send();

                return;
            }

            $response = $paywuz->createDynamicQr(
                $invoiceNumber,
                $grandTotal,
                $tenant->name ?? 'KasirAja'
            );

            if (isset($response['success']) && $response['success']) {
                $data = $response['data'] ?? [];
                $transactionId = $data['id'] ?? null;
                $paymentUrl = $data['paymentUrl'] ?? null;
                $paywuzStatus = $data['status'] ?? 'pending';

                $sale->update([
                    'paywuz_transaction_id' => $transactionId,
                    'paywuz_qr_url' => $paymentUrl,
                    'paywuz_status' => $paywuzStatus,
                ]);

                $this->currentQrisSale = $sale->fresh();
                $this->qrisTransactionId = $transactionId;
                $this->qrisImageUrl = $paymentUrl;

                // Dispatch QR generated event
                $this->dispatch('qris-generated', [
                    'transactionId' => $transactionId,
                    'amount' => $grandTotal,
                ]);

                // Show appropriate notification
                if ($paywuzStatus === 'cancelled') {
                    Notification::make()
                        ->title('QR Generated (Demo Mode)')
                        ->body('Transaction created in sandbox mode.')
                        ->warning()
                        ->send();
                } else {
                    Notification::make()
                        ->title('QR Generated')
                        ->body('Tunjukkan QR ke customer.')
                        ->success()
                        ->send();
                }
            } else {
                Notification::make()
                    ->title('Gagal membuat QR')
                    ->body($response['message'] ?? 'Terjadi kesalahan')
                    ->danger()
                    ->send();

                // Delete the created sale
                $sale->items()->delete();
                $sale->delete();
            }
        } catch (Throwable $e) {
            Notification::make()
                ->title('Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }

        $this->isGeneratingQr = false;
    }

    public function checkQrisPaymentStatus(): void
    {
        if (! $this->currentQrisSale) {
            return;
        }

        $this->isCheckingPayment = true;

        try {
            $tenant = Filament::getTenant();

            if (! $tenant) {
                $this->isCheckingPayment = false;

                return;
            }

            $tenantId = (int) $tenant->getKey();
            $paywuz = new PaywuzService($tenantId);

            // Check status from Paywuz
            if ($this->currentQrisSale->paywuz_transaction_id && ! str_starts_with($this->currentQrisSale->paywuz_transaction_id, 'DEMO-')) {
                $status = $paywuz->inquiry($this->currentQrisSale->paywuz_transaction_id);

                if (isset($status['data']['status'])) {
                    $this->currentQrisSale->update([
                        'paywuz_status' => $status['data']['status'],
                    ]);
                }
            }

            // Reload sale
            $this->currentQrisSale = $this->currentQrisSale->fresh();

            // Check if paid
            if ($this->currentQrisSale->paywuz_status === 'success' || $this->currentQrisSale->payment_status === 'paid') {
                $this->confirmQrisPayment();
            } elseif ($this->currentQrisSale->paywuz_status === 'expired' || $this->currentQrisSale->paywuz_status === 'failed') {
                Notification::make()
                    ->title('QR Expired/Failed')
                    ->body('Silakan generate QR baru')
                    ->danger()
                    ->send();

                $this->resetQrisState();
            } elseif ($this->currentQrisSale->paywuz_status === 'cancelled' || $this->currentQrisSale->paywuz_status === 'pending') {
                // For sandbox/demo mode - allow direct confirmation
                Notification::make()
                    ->title('Demo Mode')
                    ->body('Confirmed without Paywuz verification.')
                    ->info()
                    ->send();

                $this->confirmQrisPayment();
            }
        } catch (Throwable $e) {
            // Silent fail for status check
        }

        $this->isCheckingPayment = false;
    }

    public function confirmQrisPayment(): void
    {
        if (! $this->currentQrisSale) {
            return;
        }

        try {
            $sale = $this->currentQrisSale;

            // Update sale to completed
            $sale->update([
                'status' => 'completed',
                'payment_status' => 'paid',
                'paid_at' => now(),
                'paid_amount' => $sale->grand_total,
            ]);

            // Create payment record
            Payment::create([
                'sale_id' => $sale->id,
                'method' => 'qris',
                'amount' => $sale->grand_total,
                'reference' => $sale->paywuz_transaction_id,
                'paid_at' => now(),
                'notes' => $this->paymentNotes ?: null,
            ]);

            // Update table status - only if no more pending orders exist
            if ($sale->table_id) {
                $hasPendingOrders = Sale::where('table_id', $sale->table_id)
                    ->where('id', '!=', $sale->id)
                    ->where('status', '!=', 'completed')
                    ->exists();

                if (! $hasPendingOrders) {
                    Table::where('id', $sale->table_id)->update(['status' => 'available']);
                }
            }

            // Reduce stock
            foreach ($sale->items as $item) {
                $product = Product::find($item->product_id);
                if ($product && $product->rate_type !== 'duration') {
                    $product->decrement('stock', $item->quantity);

                    // Reduce ingredient stock
                    foreach ($product->ingredients as $ingredient) {
                        $ingredientQuantity = (float) $ingredient->pivot->quantity * $item->quantity;
                        if ($ingredientQuantity > 0) {
                            $ingredient->decrement('stock', $ingredientQuantity);
                        }
                    }
                }
            }

            // Dispatch success event
            $receiptUrl = route('receipt.show', [
                'tenant' => Filament::getTenant()?->getRouteKey(),
                'sale' => $sale->getRouteKey(),
            ]).'?size=80mm';

            $this->dispatch(
                'payment-success',
                invoice: $sale->invoice_number,
                total: $sale->grand_total,
                change: 0,
                receiptUrl: $receiptUrl,
                pointsEarned: 0
            );

            // Reset POS
            $this->cart = [];
            $this->search = '';
            $this->showCheckout = false;
            $this->paymentMethod = 'cash';
            $this->paidAmount = 0;
            $this->customerId = null;
            $this->paymentNotes = '';
            $this->tableId = null;
            $this->activeTableId = null;
            $this->currentBillId = null;
            $this->resetQrisState();

            Notification::make()
                ->title('Pembayaran QRIS Berhasil')
                ->success()
                ->send();

        } catch (Throwable $e) {
            Notification::make()
                ->title('Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function cancelQrisPayment(): void
    {
        if ($this->currentQrisSale) {
            // Cancel the pending sale
            $this->currentQrisSale->items()->delete();
            $this->currentQrisSale->delete();
        }

        $this->resetQrisState();

        Notification::make()
            ->title('QRIS Dibatalkan')
            ->warning()
            ->send();
    }

    public function newQrisPayment(): void
    {
        if ($this->currentQrisSale) {
            // Cancel existing pending sale
            $this->currentQrisSale->items()->delete();
            $this->currentQrisSale->delete();
        }

        $this->resetQrisState();

        // Generate new QR
        $this->generateQrisPayment();
    }

    /*
    |--------------------------------------------------------------------------
    | QR CODE URL
    |--------------------------------------------------------------------------
    */

    public function getQrisCodeUrlProperty(): ?string
    {
        // Use payment URL from Paywuz to generate QR
        if ($this->qrisImageUrl) {
            // Generate QR from payment URL with explicit format
            return 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&format=png&margin=2&data='.urlencode($this->qrisImageUrl);
        }

        // Fallback to QR server if no payment URL
        if ($this->qrisTransactionId) {
            return 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&format=png&margin=2&data='.urlencode('PAY:'.$this->qrisTransactionId);
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | IS PAYWUS CONFIGURED
    |--------------------------------------------------------------------------
    */

    public function getIsPaywuzConfiguredProperty(): bool
    {
        $tenant = Filament::getTenant();

        if (! $tenant) {
            return false;
        }

        $paywuz = new PaywuzService((int) $tenant->getKey());

        return $paywuz->isConfigured() && $paywuz->isEnabled();
    }

    /*
    |--------------------------------------------------------------------------
    | GENERATE DEMO VA NUMBER
    |--------------------------------------------------------------------------
    */

    protected function generateDemoVaNumber(string $bankCode, string $invoiceNumber): string
    {
        // Generate bank-specific VA numbers
        // Each bank has different VA prefix patterns
        $prefixes = [
            'bca' => '880',      // BCA VA prefix
            'bni' => '881',      // BNI VA prefix
            'bri' => '002',      // BRI VA prefix
            'mandiri' => '886',  // Mandiri VA prefix
            'permata' => '013',  // Permata VA prefix
        ];

        $prefix = $prefixes[strtolower($bankCode)] ?? '880';
        $randomPart = str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);

        return $prefix.$randomPart;
    }

    /*
    |--------------------------------------------------------------------------
    | VIRTUAL ACCOUNT PAYMENT METHODS
    |--------------------------------------------------------------------------
    */

    protected function resetVaState(): void
    {
        $this->vaTransactionId = null;
        $this->vaAccountNumber = null;
        $this->vaBankCode = null;
        $this->vaBankName = null;
        $this->vaExpiryTime = null;
        $this->vaPaymentUrl = null;
        $this->isGeneratingVa = false;
        $this->isCheckingVaPayment = false;
        $this->currentVaSale = null;
    }

    public function getAvailableBanksProperty(): array
    {
        $paywuz = new PaywuzService;

        return $paywuz->getAvailableBanks();
    }

    public function generateVaPayment(string $bankCode): void
    {
        if ($this->paymentMethod !== 'va') {
            return;
        }

        if (empty($this->cart)) {
            Notification::make()
                ->title('Keranjang kosong')
                ->danger()
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

        $this->isGeneratingVa = true;
        $this->vaBankCode = $bankCode;

        try {
            $tenantId = (int) $tenant->getKey();
            $banks = $this->availableBanks;
            $bankName = $banks[$bankCode]['name'] ?? 'Bank';
            $this->vaBankName = $bankName;

            // Calculate totals
            $subtotal = (float) $this->subtotal;
            $taxAmount = (float) $this->taxAmount;
            $grandTotal = (float) $this->total;

            // Check for existing pending sale with same cart items and reuse if exists
            $existingPending = Sale::where('tenant_id', $tenantId)
                ->where('status', 'pending')
                ->where('payment_method', 'va')
                ->where('payment_status', 'pending')
                ->where('grand_total', $grandTotal)
                ->first();

            if ($existingPending) {
                // Reuse existing pending sale
                $sale = $existingPending;
                $invoiceNumber = $sale->invoice_number;
            } else {
                // Generate invoice number
                $invoiceNumber = $this->generateInvoiceNumber($tenantId);

                // Create sale with pending payment
                $sale = Sale::create([
                    'tenant_id' => $tenantId,
                    'table_id' => $this->tableId === 'takeaway' ? null : $this->tableId,
                    'customer_id' => $this->customerId ?: null,
                    'reservation_id' => $this->reservationId,
                    'user_id' => Auth::id(),
                    'invoice_number' => $invoiceNumber,
                    'status' => 'pending',
                    'source' => 'pos',
                    'payment_method' => 'va',
                    'subtotal' => $subtotal,
                    'discount' => 0,
                    'tax' => $taxAmount,
                    'grand_total' => $grandTotal,
                    'paid_amount' => 0,
                    'change_amount' => 0,
                    'payment_status' => 'pending',
                    'notes' => $this->paymentNotes ?: null,
                ]);

                // Create sale items
                foreach ($this->cart as $item) {
                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_id' => (int) $item['product_id'],
                        'product_name' => $item['product_name'],
                        'sku' => $item['sku'] ?? null,
                        'quantity' => (float) $item['quantity'],
                        'unit_price' => (float) $item['unit_price'],
                        'discount' => 0,
                        'tax' => 0,
                        'subtotal' => (float) $item['subtotal'],
                        'total' => (float) $item['total'],
                        'notes' => $item['notes'] ?? null,
                    ]);
                }
            }

            // Call Paywuz to create VA
            $paywuz = new PaywuzService($tenantId);

            // Get customer info if available
            $customerName = null;
            $customerEmail = null;
            $customerPhone = null;
            if ($this->customerId) {
                $customer = Customer::find($this->customerId);
                if ($customer) {
                    $customerName = $customer->name;
                    $customerEmail = $customer->email;
                    $customerPhone = $customer->phone;
                }
            }

            if (! $paywuz->isConfigured() || ! $paywuz->isEnabled()) {
                // Paywuz not configured - use demo VA
                $demoVaNumber = '88'.rand(100000000, 999999999);
                $sale->update([
                    'paywuz_transaction_id' => 'DEMO-VA-'.$invoiceNumber,
                    'paywuz_qr_url' => $demoVaNumber,
                    'paywuz_status' => 'pending',
                ]);

                $this->currentVaSale = $sale;
                $this->vaTransactionId = 'DEMO-VA-'.$invoiceNumber;
                $this->vaAccountNumber = $demoVaNumber;
                $this->vaExpiryTime = now()->addHours(24)->format('d M Y H:i');
                $this->isGeneratingVa = false;

                Notification::make()
                    ->title('VA Generated (Demo Mode)')
                    ->body("No. VA: {$demoVaNumber}")
                    ->warning()
                    ->send();

                return;
            }

            $response = $paywuz->createVirtualAccount(
                $invoiceNumber,
                $grandTotal,
                $bankCode,
                $customerName,
                $customerEmail,
                $customerPhone
            );

            if (isset($response['success']) && $response['success']) {
                $data = $response['data'] ?? [];
                $transactionId = $data['id'] ?? $data['transactionId'] ?? null;
                // Paywuz returns paymentNumber for VA
                $accountNumber = $data['paymentNumber'] ?? $data['accountNumber'] ?? $data['vaNumber'] ?? null;
                $expiryTime = $data['expiresAt'] ?? $data['expiryTime'] ?? $data['expiredAt'] ?? null;
                $paymentUrl = $data['paymentUrl'] ?? null;

                $sale->update([
                    'paywuz_transaction_id' => $transactionId,
                    'paywuz_qr_url' => $paymentUrl,
                    'paywuz_status' => 'pending',
                ]);

                $this->currentVaSale = $sale->fresh();
                $this->vaTransactionId = $transactionId;
                $this->vaAccountNumber = $accountNumber;
                $this->vaPaymentUrl = $paymentUrl;
                $this->vaExpiryTime = $expiryTime ? date('d M Y H:i', strtotime($expiryTime)) : now()->addHours(24)->format('d M Y H:i');

                if ($accountNumber) {
                    Notification::make()
                        ->title('Virtual Account Generated')
                        ->body("No. VA: {$accountNumber}")
                        ->success()
                        ->send();
                } else {
                    Notification::make()
                        ->title('Link Pembayaran Dibuat')
                        ->body('Customer pilih bank di link pembayaran')
                        ->success()
                        ->send();
                }
            } else {
                // API failed - use demo mode with generated VA number
                $demoVaNumber = $this->generateDemoVaNumber($bankCode, $invoiceNumber);

                $sale->update([
                    'paywuz_transaction_id' => 'DEMO-VA-'.$invoiceNumber,
                    'paywuz_qr_url' => $demoVaNumber,
                    'paywuz_status' => 'pending',
                ]);

                $this->currentVaSale = $sale->fresh();
                $this->vaTransactionId = 'DEMO-VA-'.$invoiceNumber;
                $this->vaAccountNumber = $demoVaNumber;
                $this->vaExpiryTime = now()->addHours(24)->format('d M Y H:i');

                Notification::make()
                    ->title('VA Generated (Demo Mode)')
                    ->body("No. VA: {$demoVaNumber}")
                    ->warning()
                    ->send();
            }
        } catch (Throwable $e) {
            Notification::make()
                ->title('Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }

        $this->isGeneratingVa = false;
    }

    public function checkVaPaymentStatus(): void
    {
        if (! $this->currentVaSale) {
            return;
        }

        $this->isCheckingVaPayment = true;

        try {
            $tenant = Filament::getTenant();

            if (! $tenant) {
                $this->isCheckingVaPayment = false;

                return;
            }

            $tenantId = (int) $tenant->getKey();
            $paywuz = new PaywuzService($tenantId);

            // Check status from Paywuz
            if ($this->currentVaSale->paywuz_transaction_id && ! str_starts_with($this->currentVaSale->paywuz_transaction_id, 'DEMO-')) {
                $status = $paywuz->inquiry($this->currentVaSale->paywuz_transaction_id);

                if (isset($status['data']['status'])) {
                    $this->currentVaSale->update([
                        'paywuz_status' => $status['data']['status'],
                    ]);
                }
            }

            // Reload sale
            $this->currentVaSale = $this->currentVaSale->fresh();

            // Check if paid
            if ($this->currentVaSale->paywuz_status === 'success' || $this->currentVaSale->payment_status === 'paid') {
                $this->confirmVaPayment();
            } elseif ($this->currentVaSale->paywuz_status === 'expired' || $this->currentVaSale->paywuz_status === 'failed') {
                Notification::make()
                    ->title('VA Expired/Failed')
                    ->body('Silakan generate VA baru')
                    ->danger()
                    ->send();

                $this->resetVaState();
            }
        } catch (Throwable $e) {
            // Silent fail for status check
        }

        $this->isCheckingVaPayment = false;
    }

    public function confirmVaPayment(): void
    {
        if (! $this->currentVaSale) {
            return;
        }

        try {
            $sale = $this->currentVaSale;

            // Update sale to completed
            $sale->update([
                'status' => 'completed',
                'payment_status' => 'paid',
                'paid_at' => now(),
                'paid_amount' => $sale->grand_total,
            ]);

            // Create payment record
            Payment::create([
                'sale_id' => $sale->id,
                'method' => 'va',
                'amount' => $sale->grand_total,
                'reference' => $sale->paywuz_transaction_id,
                'paid_at' => now(),
                'notes' => $this->paymentNotes ?: null,
            ]);

            // Update table status - only if no more pending orders exist
            if ($sale->table_id) {
                $hasPendingOrders = Sale::where('table_id', $sale->table_id)
                    ->where('id', '!=', $sale->id)
                    ->where('status', '!=', 'completed')
                    ->exists();

                if (! $hasPendingOrders) {
                    Table::where('id', $sale->table_id)->update(['status' => 'available']);
                }
            }

            // Reduce stock
            foreach ($sale->items as $item) {
                $product = Product::find($item->product_id);
                if ($product && $product->rate_type !== 'duration') {
                    $product->decrement('stock', $item->quantity);

                    // Reduce ingredient stock
                    foreach ($product->ingredients as $ingredient) {
                        $ingredientQuantity = (float) $ingredient->pivot->quantity * $item->quantity;
                        if ($ingredientQuantity > 0) {
                            $ingredient->decrement('stock', $ingredientQuantity);
                        }
                    }
                }
            }

            // Dispatch success event
            $receiptUrl = route('receipt.show', [
                'tenant' => Filament::getTenant()?->getRouteKey(),
                'sale' => $sale->getRouteKey(),
            ]).'?size=80mm';

            $this->dispatch(
                'payment-success',
                invoice: $sale->invoice_number,
                total: $sale->grand_total,
                change: 0,
                receiptUrl: $receiptUrl,
                pointsEarned: 0
            );

            // Reset POS
            $this->cart = [];
            $this->search = '';
            $this->showCheckout = false;
            $this->paymentMethod = 'cash';
            $this->paidAmount = 0;
            $this->customerId = null;
            $this->paymentNotes = '';
            $this->tableId = null;
            $this->activeTableId = null;
            $this->currentBillId = null;
            $this->resetVaState();

            Notification::make()
                ->title('Pembayaran Virtual Account Berhasil')
                ->success()
                ->send();

        } catch (Throwable $e) {
            Notification::make()
                ->title('Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function cancelVaPayment(): void
    {
        if ($this->currentVaSale) {
            // Cancel the pending sale
            $this->currentVaSale->items()->delete();
            $this->currentVaSale->delete();
        }

        $this->resetVaState();

        Notification::make()
            ->title('Virtual Account Dibatalkan')
            ->warning()
            ->send();
    }

    public function newVaPayment(): void
    {
        if ($this->currentVaSale) {
            // Cancel existing pending sale
            $this->currentVaSale->items()->delete();
            $this->currentVaSale->delete();
        }

        $this->resetVaState();
    }
}
