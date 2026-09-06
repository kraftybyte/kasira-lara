<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('member_code')->nullable()->after('name');
            $table->boolean('is_member')->default(false)->after('is_active');
            $table->string('member_level')->nullable()->after('is_member');
            $table->unsignedInteger('points')->default(0)->after('member_level');
            $table->decimal('total_spent', 15, 2)->default(0)->after('points');
            $table->unsignedInteger('total_transactions')->default(0)->after('total_spent');
            $table->date('joined_at')->nullable()->after('total_transactions');

            $table->unique(
                ['tenant_id', 'member_code'],
                'customers_tenant_member_code_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique('customers_tenant_member_code_unique');

            $table->dropColumn([
                'member_code',
                'is_member',
                'member_level',
                'points',
                'total_spent',
                'total_transactions',
                'joined_at',
            ]);
        });
    }
};
