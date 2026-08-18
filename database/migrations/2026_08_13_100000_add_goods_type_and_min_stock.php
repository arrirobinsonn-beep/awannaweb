<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kategori barang gudang:
     *  - consumable  = Barang Pasti (Kertas Thermal, Lakban, Bubble Wrap) — stok dikelola manual admin
     *  - core        = Barang Inti (kacamata, herbal, dll) — berkurang otomatis saat pengiriman
     *  - additional  = Barang Additional (BOX, LAP) — berkurang mengikuti barang inti via packaging_rules
     *
     * min_stock = acuan re-stock per produk: muncul peringatan "Perlu Restock"
     * di halaman Gudang saat total stok produk ≤ min_stock.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->enum('goods_type', ['consumable', 'core', 'additional'])->default('core')->after('category');
            $table->unsignedInteger('min_stock')->default(0)->after('goods_type');
            $table->index('goods_type');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['goods_type']);
            $table->dropColumn(['goods_type', 'min_stock']);
        });
    }
};
