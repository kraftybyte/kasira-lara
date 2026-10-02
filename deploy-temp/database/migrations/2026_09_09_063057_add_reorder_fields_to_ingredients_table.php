<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ingredients', function (Blueprint $table) {
            $table->decimal('reorder_point', 10, 2)->default(0)->after('minimum_stock');
            $table->boolean('auto_reorder')->default(false)->after('reorder_point');
        });
    }

    public function down(): void
    {
        Schema::table('ingredients', function (Blueprint $table) {
            $table->dropColumn(['reorder_point', 'auto_reorder']);
        });
    }
};
