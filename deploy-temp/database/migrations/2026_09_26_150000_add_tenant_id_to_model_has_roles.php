<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add tenant_id back to model_has_roles because Spatie teams feature requires it
        Schema::table('model_has_roles', function (Blueprint $table) {
            if (! Schema::hasColumn('model_has_roles', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('model_id');
                $table->index('tenant_id');
            }
        });

        // Update primary key to include tenant_id
        Schema::table('model_has_roles', function (Blueprint $table) {
            // Drop existing foreign key if exists
            // Then recreate with tenant_id
        });
    }

    public function down(): void
    {
        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->dropIndex(['tenant_id']);
            $table->dropColumn('tenant_id');
        });
    }
};
