<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('paywuz_fee', 10, 2)->default(0)->after('paywuz_status');
            $table->boolean('paywuz_fee_by_merchant')->default(false)->after('paywuz_fee');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['paywuz_fee', 'paywuz_fee_by_merchant']);
        });
    }
};
