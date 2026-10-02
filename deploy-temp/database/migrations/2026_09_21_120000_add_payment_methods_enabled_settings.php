<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tenant_settings', function (Blueprint $table) {
            // POS Payment Methods
            $table->boolean('payment_qris_auto')->default(true)->after('paywuz_fee_by_merchant');
            $table->boolean('payment_va')->default(true)->after('payment_qris_auto');
            $table->boolean('payment_transfer')->default(true)->after('payment_va');
            $table->boolean('payment_qris_manual')->default(true)->after('payment_transfer');
            $table->boolean('payment_cash')->default(true)->after('payment_qris_manual');

            // QR Meja (Table QR) Payment Methods
            $table->boolean('table_qr_qris_auto')->default(true)->after('payment_cash');
            $table->boolean('table_qr_va')->default(true)->after('table_qr_qris_auto');
            $table->boolean('table_qr_pay_at_counter')->default(true)->after('table_qr_va');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenant_settings', function (Blueprint $table) {
            $table->dropColumn([
                'payment_qris_auto',
                'payment_va',
                'payment_transfer',
                'payment_qris_manual',
                'payment_cash',
                'table_qr_qris_auto',
                'table_qr_va',
                'table_qr_pay_at_counter',
            ]);
        });
    }
};
