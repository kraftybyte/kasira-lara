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
        Schema::table('tenant_receipt_settings', function (Blueprint $table) {
            $table->decimal('tax_rate', 5, 2)->default(11.00)->after('footer_text');
            $table->boolean('show_tax')->default(true)->after('tax_rate');
        });
    }

    public function down(): void
    {
        Schema::table('tenant_receipt_settings', function (Blueprint $table) {
            $table->dropColumn(['tax_rate', 'show_tax']);
        });
    }
};
