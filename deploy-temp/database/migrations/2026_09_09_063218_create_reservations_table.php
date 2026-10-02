<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('table_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_name');
            $table->string('customer_phone', 20)->nullable();
            $table->string('customer_email')->nullable();
            $table->date('reservation_date');
            $table->time('reservation_time');
            $table->integer('guest_count')->default(1);
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamps();

            $table->index(['tenant_id', 'reservation_date']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
