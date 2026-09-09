<?php

namespace App\Models;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = [
        'tenant_id',

        // Customer
        'name',
        'phone',
        'email',
        'address',
        'notes',
        'is_active',

        // Member
        'member_code',
        'is_member',
        'member_level',
        'tier',
        'tier_discount',
        'points',
        'total_spent',
        'total_transactions',
        'joined_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_member' => 'boolean',

            'points' => 'integer',

            'total_spent' => 'decimal:2',

            'total_transactions' => 'integer',

            'tier_discount' => 'decimal:2',

            'joined_at' => 'date',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | TENANT
    |--------------------------------------------------------------------------
    */

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(
            Tenant::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | SALES
    |--------------------------------------------------------------------------
    */

    public function sales(): HasMany
    {
        return $this->hasMany(
            Sale::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | BOOT
    |--------------------------------------------------------------------------
    */

    protected static function booted(): void
    {
        static::creating(
            function (Customer $customer) {

                /*
                 * Tenant otomatis
                 */
                if (blank($customer->tenant_id)) {

                    $tenant =
                        Filament::getTenant();

                    if ($tenant) {

                        $customer->tenant_id =
                            $tenant->id;
                    }
                }

                /*
                 * Default
                 */
                if (blank($customer->points)) {
                    $customer->points = 0;
                }

                if (blank($customer->total_spent)) {
                    $customer->total_spent = 0;
                }

                if (
                    blank(
                        $customer->total_transactions
                    )
                ) {
                    $customer->total_transactions = 0;
                }

                /*
                 * Member code
                 */
                if (
                    $customer->is_member
                    && blank($customer->member_code)
                ) {

                    $customer->member_code =
                        static::generateMemberCode(
                            $customer->tenant_id
                        );
                }

                /*
                 * Joined date
                 */
                if (
                    $customer->is_member
                    && blank($customer->joined_at)
                ) {

                    $customer->joined_at =
                        now()->toDateString();
                }

                /*
                 * Default level
                 */
                if (
                    $customer->is_member
                    && blank($customer->member_level)
                ) {

                    $customer->member_level =
                        'Bronze';
                }
            }
        );

        static::updating(
            function (Customer $customer) {

                /*
                 * Customer menjadi member
                 */
                if (
                    $customer->is_member
                    && blank($customer->member_code)
                ) {

                    $customer->member_code =
                        static::generateMemberCode(
                            $customer->tenant_id
                        );
                }

                /*
                 * Joined date
                 */
                if (
                    $customer->is_member
                    && blank($customer->joined_at)
                ) {

                    $customer->joined_at =
                        now()->toDateString();
                }

                /*
                 * Default level
                 */
                if (
                    $customer->is_member
                    && blank($customer->member_level)
                ) {

                    $customer->member_level =
                        'Bronze';
                }
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | GENERATE MEMBER CODE
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | TIER BENEFITS
    |--------------------------------------------------------------------------
    */

    public static function getTierDiscounts(): array
    {
        return [
            'bronze' => 0,
            'silver' => 5,
            'gold' => 10,
            'platinum' => 15,
        ];
    }

    public function getTierDiscountPercent(): float
    {
        return self::getTierDiscounts()[$this->tier] ?? 0;
    }

    public function calculateDiscount(float $amount): float
    {
        $discountPercent = $this->getTierDiscountPercent();

        return $amount * ($discountPercent / 100);
    }

    public function getTierBadgeColor(): string
    {
        return match ($this->tier) {
            'bronze' => 'amber',
            'silver' => 'gray',
            'gold' => 'yellow',
            'platinum' => 'sky',
            default => 'gray',
        };
    }

    /*
    |--------------------------------------------------------------------------
    | GENERATE MEMBER CODE
    |--------------------------------------------------------------------------
    */

    protected static function generateMemberCode(
        ?int $tenantId
    ): string {

        $lastMember = static::query()
            ->where(
                'tenant_id',
                $tenantId
            )
            ->whereNotNull(
                'member_code'
            )
            ->orderByDesc('id')
            ->first();

        if (
            ! $lastMember
            || ! $lastMember->member_code
        ) {

            $number = 1;

        } else {

            preg_match(
                '/(\d+)$/',
                $lastMember->member_code,
                $matches
            );

            $number =
                isset($matches[1])
                    ? ((int) $matches[1]) + 1
                    : 1;
        }

        return 'MBR-'.
            str_pad(
                (string) $number,
                5,
                '0',
                STR_PAD_LEFT
            );
    }
}
