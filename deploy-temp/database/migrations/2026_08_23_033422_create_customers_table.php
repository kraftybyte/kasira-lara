<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->string('name');
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(
                ['tenant_id', 'name'],
                'customers_tenant_name_index'
            );

            $table->index(
                ['tenant_id', 'phone'],
                'customers_tenant_phone_index'
            );

            $table->index(
                ['tenant_id', 'is_active'],
                'customers_tenant_active_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
