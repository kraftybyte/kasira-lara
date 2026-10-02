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
        if (! Schema::hasColumn('tenant_settings', 'payment_qris_manual')) {
            Schema::table('tenant_settings', function (Blueprint $table) {
                $table->boolean('payment_qris_manual')->default(true)->after('payment_transfer');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenant_settings', function (Blueprint $table) {
            $table->dropColumn('payment_qris_manual');
        });
    }
};
