<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->foreignId('category_id')
                ->nullable()
                ->constrained('categories')
                ->nullOnDelete();

            $table->string('sku');
            $table->string('barcode')->nullable();

            $table->string('name');
            $table->text('description')->nullable();

            $table->decimal('cost_price', 15, 2)->default(0);
            $table->decimal('selling_price', 15, 2)->default(0);

            $table->decimal('stock', 15, 3)->default(0);
            $table->decimal('minimum_stock', 15, 3)->default(0);

            $table->string('unit', 50)->default('pcs');

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(
                ['tenant_id', 'sku'],
                'products_tenant_sku_unique'
            );

            $table->unique(
                ['tenant_id', 'barcode'],
                'products_tenant_barcode_unique'
            );

            $table->index(
                ['tenant_id', 'category_id'],
                'products_tenant_category_index'
            );

            $table->index(
                ['tenant_id', 'is_active'],
                'products_tenant_active_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
