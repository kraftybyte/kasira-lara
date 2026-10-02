<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_receipt_settings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();

            // Identitas toko
            $table->string('store_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();

            // Logo
            $table->string('logo')->nullable();
            $table->boolean('show_logo')->default(true);

            // Header & footer
            $table->text('header_text')->nullable();
            $table->text('footer_text')->nullable();

            // Tampilan informasi
            $table->boolean('show_customer')->default(true);
            $table->boolean('show_cashier')->default(true);
            $table->boolean('show_payment_method')->default(true);

            // Ukuran kertas
            $table->string('paper_size')->default('80mm');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_receipt_settings');
    }
};
