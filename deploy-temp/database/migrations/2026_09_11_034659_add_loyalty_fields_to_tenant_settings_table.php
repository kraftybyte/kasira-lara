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
            $table->boolean('loyalty_enabled')->default(false)->after('paywuz_api_key');
            $table->decimal('loyalty_points_per_rupiah', 10, 2)->default(1000)->after('loyalty_enabled');
            $table->decimal('loyalty_points_value', 10, 2)->default(1)->after('loyalty_points_per_rupiah');
            $table->integer('loyalty_minimum_redeem')->default(100)->after('loyalty_points_value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenant_settings', function (Blueprint $table) {
            $table->dropColumn([
                'loyalty_enabled',
                'loyalty_points_per_rupiah',
                'loyalty_points_value',
                'loyalty_minimum_redeem',
            ]);
        });
    }
};
