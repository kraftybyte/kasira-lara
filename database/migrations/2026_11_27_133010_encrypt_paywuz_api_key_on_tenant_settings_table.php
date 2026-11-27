<?php

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Nilai terenkripsi lebih panjang dari 255 karakter
        Schema::table('tenant_settings', function (Blueprint $table) {
            $table->text('paywuz_api_key')->nullable()->change();
        });

        foreach ($this->keys() as $row) {
            try {
                Crypt::decryptString($row->paywuz_api_key);

                continue; // sudah terenkripsi
            } catch (DecryptException) {
            }

            DB::table('tenant_settings')->where('id', $row->id)->update([
                'paywuz_api_key' => Crypt::encryptString($row->paywuz_api_key),
            ]);
        }
    }

    public function down(): void
    {
        foreach ($this->keys() as $row) {
            try {
                DB::table('tenant_settings')->where('id', $row->id)->update([
                    'paywuz_api_key' => Crypt::decryptString($row->paywuz_api_key),
                ]);
            } catch (DecryptException) {
            }
        }

        Schema::table('tenant_settings', function (Blueprint $table) {
            $table->string('paywuz_api_key')->nullable()->change();
        });
    }

    /**
     * @return Collection<int, object{id: int, paywuz_api_key: string}>
     */
    private function keys(): Collection
    {
        return DB::table('tenant_settings')
            ->whereNotNull('paywuz_api_key')
            ->where('paywuz_api_key', '!=', '')
            ->get(['id', 'paywuz_api_key']);
    }
};
