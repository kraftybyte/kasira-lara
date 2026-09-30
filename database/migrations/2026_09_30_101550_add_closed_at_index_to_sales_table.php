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
        Schema::table('sales', function (Blueprint $table) {
            // Index for closed_at to improve filtering performance on reports and lists
            $table->index('closed_at', 'idx_sales_closed_at');

            // Composite index for common query pattern: tenant + status + closed_at
            $table->index(['tenant_id', 'status', 'closed_at'], 'idx_sales_tenant_status_closed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('idx_sales_closed_at');
            $table->dropIndex('idx_sales_tenant_status_closed');
        });
    }
};
