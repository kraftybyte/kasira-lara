<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Sale;

class MemberPointService
{
    /**
     * Point yang didapat dari transaksi.
     *
     * Default:
     * Rp 1.000 = 1 point
     */
    protected int $amountPerPoint = 1000;

    /**
     * Proses point setelah transaksi selesai.
     */
    public function processSale(Sale $sale): int
    {
        if (! $sale->customer_id) {
            return 0;
        }

        $customer = Customer::query()
            ->where('tenant_id', $sale->tenant_id)
            ->where('id', $sale->customer_id)
            ->lockForUpdate()
            ->first();

        if (! $customer) {
            return 0;
        }

        /*
         * Customer bukan member
         * tidak mendapatkan point.
         */
        if (! $customer->is_member) {
            return 0;
        }

        $grandTotal = (float) $sale->grand_total;

        /*
         * Hitung point.
         *
         * Contoh:
         * Rp125.000 / Rp1.000 = 125 point
         */
        $pointsEarned = (int) floor(
            $grandTotal / $this->amountPerPoint
        );

        /*
         * Update statistik member.
         */
        $customer->points =
            (int) $customer->points + $pointsEarned;

        $customer->total_spent =
            (float) $customer->total_spent + $grandTotal;

        $customer->total_transactions =
            (int) $customer->total_transactions + 1;

        /*
         * Update level member.
         */
        $customer->member_level =
            $this->calculateMemberLevel(
                (float) $customer->total_spent
            );

        $customer->save();

        return $pointsEarned;
    }

    /**
     * Tentukan level member berdasarkan
     * total spending.
     */
    public function calculateMemberLevel(
        float $totalSpent
    ): string {

        if ($totalSpent >= 10000000) {
            return 'Platinum';
        }

        if ($totalSpent >= 5000000) {
            return 'Gold';
        }

        if ($totalSpent >= 1000000) {
            return 'Silver';
        }

        return 'Bronze';
    }

    /**
     * Mendapatkan jumlah point dari nominal.
     */
    public function calculatePoints(
        float $amount
    ): int {

        return (int) floor(
            $amount / $this->amountPerPoint
        );
    }

    /**
     * Point rate.
     */
    public function getAmountPerPoint(): int
    {
        return $this->amountPerPoint;
    }
}
