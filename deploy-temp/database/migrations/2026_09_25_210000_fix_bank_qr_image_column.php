<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_settings', function (Blueprint $table) {
            $table->dropColumn('bank_qr_image');
        });

        Schema::table('tenant_settings', function (Blueprint $table) {
            $table->string('bank_qr_image')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tenant_settings', function (Blueprint $table) {
            $table->dropColumn('bank_qr_image');
        });

        Schema::table('tenant_settings', function (Blueprint $table) {
            $table->json('bank_qr_image')->nullable();
        });
    }
};
