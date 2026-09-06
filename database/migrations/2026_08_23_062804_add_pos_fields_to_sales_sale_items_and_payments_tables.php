<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * SALES
         */
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('tenant_id')
                ->after('id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->foreignId('customer_id')
                ->nullable()
                ->after('tenant_id')
                ->constrained('customers')
                ->nullOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->after('customer_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->string('invoice_number')
                ->after('user_id');

            $table->string('status')
                ->default('completed')
                ->after('invoice_number');

            $table->decimal('subtotal', 15, 2)
                ->default(0)
                ->after('status');

            $table->decimal('discount', 15, 2)
                ->default(0)
                ->after('subtotal');

            $table->decimal('tax', 15, 2)
                ->default(0)
                ->after('discount');

            $table->decimal('grand_total', 15, 2)
                ->default(0)
                ->after('tax');

            $table->decimal('paid_amount', 15, 2)
                ->default(0)
                ->after('grand_total');

            $table->decimal('change_amount', 15, 2)
                ->default(0)
                ->after('paid_amount');

            $table->text('notes')
                ->nullable()
                ->after('change_amount');

            $table->unique(
                ['tenant_id', 'invoice_number'],
                'sales_tenant_invoice_unique'
            );

            $table->index(
                ['tenant_id', 'status'],
                'sales_tenant_status_index'
            );

            $table->index(
                ['tenant_id', 'created_at'],
                'sales_tenant_created_index'
            );
        });

        /*
         * SALE ITEMS
         */
        Schema::table('sale_items', function (Blueprint $table) {
            $table->foreignId('sale_id')
                ->after('id')
                ->constrained('sales')
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->nullable()
                ->after('sale_id')
                ->constrained('products')
                ->nullOnDelete();

            // Snapshot product saat transaksi.
            // Jadi jika nama/SKU produk berubah, histori transaksi tetap aman.
            $table->string('product_name')
                ->after('product_id');

            $table->string('sku')
                ->nullable()
                ->after('product_name');

            $table->decimal('quantity', 15, 3)
                ->default(1)
                ->after('sku');

            $table->decimal('unit_price', 15, 2)
                ->default(0)
                ->after('quantity');

            $table->decimal('discount', 15, 2)
                ->default(0)
                ->after('unit_price');

            $table->decimal('tax', 15, 2)
                ->default(0)
                ->after('discount');

            $table->decimal('subtotal', 15, 2)
                ->default(0)
                ->after('tax');

            $table->decimal('total', 15, 2)
                ->default(0)
                ->after('subtotal');

            $table->index(
                ['sale_id', 'product_id'],
                'sale_items_sale_product_index'
            );
        });

        /*
         * PAYMENTS
         */
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('sale_id')
                ->after('id')
                ->constrained('sales')
                ->cascadeOnDelete();

            $table->string('method')
                ->after('sale_id');

            $table->decimal('amount', 15, 2)
                ->default(0)
                ->after('method');

            $table->string('reference')
                ->nullable()
                ->after('amount');

            $table->timestamp('paid_at')
                ->nullable()
                ->after('reference');

            $table->text('notes')
                ->nullable()
                ->after('paid_at');

            $table->index(
                ['sale_id', 'method'],
                'payments_sale_method_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_sale_method_index');
            $table->dropForeign(['sale_id']);

            $table->dropColumn([
                'sale_id',
                'method',
                'amount',
                'reference',
                'paid_at',
                'notes',
            ]);
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropIndex('sale_items_sale_product_index');
            $table->dropForeign(['sale_id']);
            $table->dropForeign(['product_id']);

            $table->dropColumn([
                'sale_id',
                'product_id',
                'product_name',
                'sku',
                'quantity',
                'unit_price',
                'discount',
                'tax',
                'subtotal',
                'total',
            ]);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropUnique('sales_tenant_invoice_unique');
            $table->dropIndex('sales_tenant_status_index');
            $table->dropIndex('sales_tenant_created_index');

            $table->dropForeign(['tenant_id']);
            $table->dropForeign(['customer_id']);
            $table->dropForeign(['user_id']);

            $table->dropColumn([
                'tenant_id',
                'customer_id',
                'user_id',
                'invoice_number',
                'status',
                'subtotal',
                'discount',
                'tax',
                'grand_total',
                'paid_amount',
                'change_amount',
                'notes',
            ]);
        });
    }
};
