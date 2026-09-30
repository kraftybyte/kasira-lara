<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add composite index on columns frequently used together in queries:
     * - closed_at (nullable) - filter by open/closed sales
     * - status - filter by sale status
     * - table_id - filter by table
     * - tenant_id - filter by tenant
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // Index for filtering open sales by table
            $table->index(['table_id', 'closed_at', 'status'], 'idx_sales_table_open');

            // Index for tenant-scoped queries
            $table->index(['tenant_id', 'closed_at'], 'idx_sales_tenant_open');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('idx_sales_table_open');
            $table->dropIndex('idx_sales_tenant_open');
        });
    }
};
