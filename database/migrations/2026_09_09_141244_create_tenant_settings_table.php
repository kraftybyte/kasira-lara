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
        Schema::create('tenant_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            // Store Info
            $table->string('store_name')->nullable();
            $table->string('logo')->nullable();
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();

            // Receipt Settings
            $table->boolean('show_logo')->default(true);
            $table->boolean('show_address')->default(true);
            $table->boolean('show_phone')->default(true);
            $table->boolean('show_customer')->default(true);
            $table->boolean('show_cashier')->default(true);
            $table->boolean('show_invoice_number')->default(true);
            $table->boolean('show_payment_method')->default(true);
            $table->boolean('show_footer')->default(true);
            $table->text('footer_text')->nullable();
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->boolean('show_tax')->default(false);

            // Paywuz Settings
            $table->boolean('paywuz_enabled')->default(false);
            $table->string('paywuz_merchant_name')->nullable();
            $table->string('paywuz_api_key')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_settings');
    }
};
