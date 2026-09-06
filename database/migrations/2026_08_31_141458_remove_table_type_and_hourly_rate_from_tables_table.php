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
        Schema::table('tables', function (Blueprint $table) {
            $table->dropColumn(['table_type', 'hourly_rate']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tables', function (Blueprint $table) {
            $table->enum('table_type', ['regular', 'duration'])->default('regular')->after('status');
            $table->decimal('hourly_rate', 10, 2)->nullable()->after('table_type');
        });
    }
};
