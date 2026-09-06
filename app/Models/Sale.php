<?php

namespace App\Models;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    protected $fillable = [
        'tenant_id',
        'table_id',
        'customer_id',
        'user_id',
        'invoice_number',
        'status',
        'started_at',
        'payment_method',

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
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | HELPER METHODS
    |--------------------------------------------------------------------------
    */

    public function isPaid(): bool
    {
        return $this->status === 'completed';
    }

    public function isServed(): bool
    {
        return $this->served_at !== null;
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
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
}
