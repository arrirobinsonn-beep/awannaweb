<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Label gudang UTAMA (`is_primary`) HANYA berlaku untuk Barang Inti (core).
     * Barang Pasti (consumable) ada di semua gudang dan Barang Additional mengikuti
     * barang inti — keduanya TIDAK punya gudang utama. Bersihkan data lama yang
     * sempat ter-backfill dengan is_primary=1 untuk produk non-core.
     */
    public function up(): void
    {
        DB::table('product_inventory')
            ->join('products', 'products.id', '=', 'product_inventory.product_id')
            ->where('products.goods_type', '!=', 'core')
            ->update(['product_inventory.is_primary' => false]);
    }

    public function down(): void
    {
        // Tidak ada pembalikan otomatis yang aman (data is_primary lama sudah dihitung ulang seeder).
    }
};
