<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Aturan kemasan dinamis: "setiap `qty_per` unit barang inti (source_product)
     * → 1 unit barang additional (target_product) ikut keluar saat pengiriman".
     *
     * Contoh: KMP → BOX qty_per=2 berarti tiap 2 pcs KMP terkirim, 1 BOX keluar.
     * Di-seed default dari ProductSeeder; admin bisa ubah rasio / tambah rule
     * lewat halaman Gudang.
     */
    public function up(): void
    {
        Schema::create('packaging_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('target_product_id')->constrained('products')->cascadeOnDelete();
            $table->unsignedInteger('qty_per')->default(2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['source_product_id', 'target_product_id']);
            $table->index('source_product_id');
            $table->index('target_product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packaging_rules');
    }
};
