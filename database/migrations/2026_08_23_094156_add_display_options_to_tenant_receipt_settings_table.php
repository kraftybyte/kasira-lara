<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_receipt_settings', function (Blueprint $table) {
            $table->boolean('show_address')
                ->default(true)
                ->after('address');

            $table->boolean('show_phone')
                ->default(true)
                ->after('phone');

            $table->boolean('show_invoice_number')
                ->default(true)
                ->after('show_phone');

            $table->boolean('show_footer')
                ->default(true)
                ->after('footer_text');
        });
    }

    public function down(): void
    {
        Schema::table('tenant_receipt_settings', function (Blueprint $table) {
            $table->dropColumn([
                'show_address',
                'show_phone',
                'show_invoice_number',
                'show_footer',
            ]);
        });
    }
};
