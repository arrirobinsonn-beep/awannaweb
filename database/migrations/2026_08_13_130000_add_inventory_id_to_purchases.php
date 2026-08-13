<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Barang Masuk kini bisa menentukan GUDANG TUJUAN stok (many-to-many produk↔gudang,
     * fitur P/Q). Sebelumnya stok selalu tercatat ke gudang utama produk.
     * Nullable agar data lama tetap valid; form mengharuskan pilihan.
     */
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->foreignId('inventory_id')
                ->nullable()
                ->after('product_variant_id')
                ->constrained('inventories')
                ->nullOnDelete();
            $table->index('inventory_id');
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropIndex(['inventory_id']);
            $table->dropConstrainedForeignId('inventory_id');
        });
    }
};
