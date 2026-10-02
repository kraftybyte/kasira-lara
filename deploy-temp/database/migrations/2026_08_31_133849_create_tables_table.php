<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // Meja A, Meja B, 1, 2, etc.
            $table->string('table_number')->nullable(); // Alternative number
            $table->enum('status', ['available', 'active', 'reserved'])->default('available');
            $table->enum('table_type', ['regular', 'duration'])->default('regular'); // regular = regular billing, duration = billiard etc.
            $table->decimal('hourly_rate', 10, 2)->nullable(); // For duration type tables
            $table->integer('capacity')->default(1); // How many people
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tables');
    }
};
