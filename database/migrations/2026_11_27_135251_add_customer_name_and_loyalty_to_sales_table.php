<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('customer_name')->nullable()->after('customer_id');
            $table->unsignedInteger('loyalty_points_awarded')->nullable()->after('stock_deducted_at');
            // Sale baru tidak boleh diam-diam lahir sebagai "lunas"
            $table->string('status')->default('pending')->change();
        });

        // Penjualan tunai POS lama tersimpan completed/pending walau ada catatan Payment: samakan artinya.
        DB::statement(<<<'SQL'
            UPDATE sales s
            SET s.payment_status = 'paid',
                s.paid_at = COALESCE(s.paid_at, (SELECT MIN(p.paid_at) FROM payments p WHERE p.sale_id = s.id), s.updated_at),
                s.stock_deducted_at = CASE WHEN s.source = 'pos' THEN COALESCE(s.stock_deducted_at, s.updated_at) ELSE s.stock_deducted_at END
            WHERE s.status = 'completed'
              AND s.payment_status = 'pending'
              AND EXISTS (SELECT 1 FROM payments p WHERE p.sale_id = s.id)
        SQL);
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['customer_name', 'loyalty_points_awarded']);
            $table->string('status')->default('completed')->change();
        });
    }
};
