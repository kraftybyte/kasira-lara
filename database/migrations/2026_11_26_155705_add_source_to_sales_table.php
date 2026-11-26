<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Database lama sudah punya kolom ini karena ditambah manual, bukan lewat migrasi.
        if (Schema::hasColumn('sales', 'source')) {
            return;
        }

        Schema::table('sales', function (Blueprint $table) {
            $table->string('source', 50)->nullable()->default('pos')->after('closed_at');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
