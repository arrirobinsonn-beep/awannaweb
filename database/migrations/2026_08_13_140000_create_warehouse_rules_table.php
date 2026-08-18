<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Aturan dinamis kode produk → kode gudang (nama pengirim/warehouse pada
 * export template). Menggantikan konstanta hardcoded `WAREHOUSE_BY_PRODUCT`
 * (SH→GTM, KSP→Aurora) — admin kelola lewat halaman Aturan Gudang.
 *
 * Satu kode produk = satu rule (product_code unique). Nonaktifkan rule bila
 * produk harus jatuh ke gudang utama produk (pivot is_primary) / sender.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_rules', function (Blueprint $table) {
            $table->id();
            $table->string('product_code')->unique();
            $table->string('warehouse');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_rules');
    }
};
