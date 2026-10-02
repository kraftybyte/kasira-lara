<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('table_id')->nullable()->after('tenant_id')->constrained()->nullOnDelete();
            $table->timestamp('started_at')->nullable()->after('status');
            $table->string('payment_method', 50)->nullable()->after('change_amount');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['table_id']);
            $table->dropColumn(['table_id', 'started_at', 'payment_method']);
        });
    }
};
