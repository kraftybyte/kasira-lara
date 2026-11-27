<?php

namespace App\Models;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Sale extends Model
{
    protected $fillable = [
        'tenant_id',
        'table_id',
        'customer_id',
        'user_id',
        'reservation_id',
        'invoice_number',
        'status',
        'started_at',
        'payment_method',
        'source',

        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',

        'subtotal',
        'discount',
        'tax',
        'grand_total',
        'paid_amount',
        'change_amount',
        'served_at',
        'notes',

        // Paywuz fields
        'paywuz_transaction_id',
        'paywuz_qr_url',
        'paywuz_status',
        'paywuz_fee',
        'paywuz_fee_by_merchant',
        'payment_status',
        'paid_at',
        'closed_at',
        'kitchen_completed_at',
        'stock_deducted_at',
        'loyalty_points_awarded',
        'customer_name',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'tax' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'change_amount' => 'decimal:2',

            'cancelled_at' => 'datetime',
            'started_at' => 'datetime',
            'served_at' => 'datetime',
            'paid_at' => 'datetime',
            'closed_at' => 'datetime',
            'kitchen_completed_at' => 'datetime',
            'stock_deducted_at' => 'datetime',
        ];
    }

    public function markAsServed(): void
    {
        $this->update(['served_at' => now()]);
    }

    public function markAsUnserved(): void
    {
        $this->update(['served_at' => null]);
    }

    protected static function booted(): void
    {
        static::creating(function (Sale $sale) {

            if (blank($sale->tenant_id)) {

                $tenant = Filament::getTenant();

                if ($tenant) {
                    $sale->tenant_id = $tenant->id;
                }
            }

            if (blank($sale->user_id)) {
                $sale->user_id = auth()->id();
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | TABLE
    |--------------------------------------------------------------------------
    */

    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class);
    }

    /*
    |--------------------------------------------------------------------------
    | RESERVATION
    |--------------------------------------------------------------------------
    */

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    /*
    |--------------------------------------------------------------------------
    | TENANT
    |--------------------------------------------------------------------------
    */

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /*
    |--------------------------------------------------------------------------
    | CUSTOMER
    |--------------------------------------------------------------------------
    */

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /*
    |--------------------------------------------------------------------------
    | CASHIER
    |--------------------------------------------------------------------------
    */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /*
    |--------------------------------------------------------------------------
    | CANCELLED BY
    |--------------------------------------------------------------------------
    */

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'cancelled_by'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | SALE ITEMS
    |--------------------------------------------------------------------------
    */

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    /*
    |--------------------------------------------------------------------------
    | PAYMENTS
    |--------------------------------------------------------------------------
    */

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Satu-satunya jalur menandai sale lunas untuk pembayaran non-tunai: status, catatan Payment, dan stok. Idempoten.
     */
    public function markPaid(string $method, ?string $reference = null, ?string $notes = null): void
    {
        DB::transaction(function () use ($method, $reference, $notes): void {
            $sale = static::query()->whereKey($this->id)->lockForUpdate()->firstOrFail();

            if ($sale->payment_status === 'paid') {
                return;
            }

            $sale->update([
                'status' => 'completed',
                'payment_status' => 'paid',
                'paid_at' => now(),
                'paid_amount' => $sale->grand_total,
                'change_amount' => 0,
            ]);

            Payment::create([
                'sale_id' => $sale->id,
                'method' => $method,
                'amount' => $sale->grand_total,
                'reference' => $reference,
                'paid_at' => now(),
                'notes' => $notes,
            ]);

            $sale->deductStock();
            $sale->awardLoyalty();
        });

        $this->refresh();
    }

    /**
     * Poin dan statistik member untuk sale ini, sekali saja; jumlah poin disimpan agar bisa dibalik saat batal.
     */
    public function awardLoyalty(): int
    {
        $points = DB::transaction(function (): int {
            $sale = static::query()->whereKey($this->id)->lockForUpdate()->firstOrFail();

            if (! $sale->customer_id || $sale->loyalty_points_awarded !== null) {
                return (int) $sale->loyalty_points_awarded;
            }

            $customer = Customer::query()->where('tenant_id', $sale->tenant_id)->whereKey($sale->customer_id)->lockForUpdate()->first();

            if (! $customer) {
                return 0;
            }

            $points = 0;

            if ($customer->is_member) {
                $settings = TenantSetting::where('tenant_id', $sale->tenant_id)->first();
                $points = $settings && $settings->isLoyaltyEnabled()
                    ? $settings->calculatePoints((float) $sale->grand_total)
                    : (int) floor((float) $sale->grand_total / 1000);

                if ($points > 0) {
                    $customer->increment('points', $points);
                }
            }

            $customer->increment('total_transactions');
            $customer->increment('total_spent', (float) $sale->grand_total);
            $sale->update(['loyalty_points_awarded' => $points]);

            return $points;
        });

        $this->refresh();

        return $points;
    }

    /**
     * Kurangi stok produk dan bahan untuk sale ini sekali saja; stock_deducted_at adalah penandanya.
     */
    public function deductStock(): void
    {
        DB::transaction(function (): void {
            $sale = static::query()->whereKey($this->id)->lockForUpdate()->firstOrFail();

            if ($sale->stock_deducted_at) {
                return;
            }

            foreach ($sale->items as $item) {
                $product = $item->product_id ? Product::query()->whereKey($item->product_id)->lockForUpdate()->first() : null;

                if (! $product || $product->rate_type === 'duration') {
                    continue;
                }

                $product->decrement('stock', (float) $item->quantity);

                foreach ($product->ingredients as $ingredient) {
                    $needed = (float) $ingredient->pivot->quantity * (float) $item->quantity;

                    if ($needed > 0) {
                        $ingredient->decrement('stock', $needed);
                    }
                }
            }

            $sale->update(['stock_deducted_at' => now()]);
        });

        $this->refresh();
    }
}
