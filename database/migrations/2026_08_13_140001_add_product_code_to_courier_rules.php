<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah `product_code` (nullable) ke tabel `courier_rules` — rule courier
 * yang berlaku khusus untuk SATU kode produk (mis. SH → selalu flix-tf,
 * tidak terpengaruh aturan provinsi). `product_code` null = berlaku umum
 * (semua produk), perilaku lama tetap.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courier_rules', function (Blueprint $table) {
            $table->string('product_code')->nullable()->after('province');
            $table->index('product_code');
        });
    }

    public function down(): void
    {
        Schema::table('courier_rules', function (Blueprint $table) {
            $table->dropIndex(['product_code']);
            $table->dropColumn('product_code');
        });
    }
};
