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
        Schema::table('sales', function (Blueprint $table) {
            $table->string('paywuz_transaction_id')->nullable()->after('payment_method');
            $table->string('paywuz_qr_url')->nullable()->after('paywuz_transaction_id');
            $table->string('paywuz_status')->nullable()->after('paywuz_qr_url');
            $table->string('payment_status')->default('pending')->after('paywuz_status');
            $table->timestamp('paid_at')->nullable()->after('payment_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn([
                'paywuz_transaction_id',
                'paywuz_qr_url',
                'paywuz_status',
                'payment_status',
                'paid_at',
            ]);
        });
    }
};
