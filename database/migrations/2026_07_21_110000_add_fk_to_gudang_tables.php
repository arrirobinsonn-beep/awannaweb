<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pembelian_barangs', function (Blueprint $table) {
            $table->foreignId('supplier_id')
                ->nullable()
                ->after('supplier')
                ->constrained('suppliers')
                ->nullOnDelete();
            $table->foreignId('product_id')
                ->nullable()
                ->after('sumber_produk')
                ->constrained('products')
                ->nullOnDelete();
        });

        Schema::table('rts_recaps', function (Blueprint $table) {
            $table->foreignId('product_id')
                ->nullable()
                ->after('nama_barang')
                ->constrained('products')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('rts_recaps', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropColumn('product_id');
        });

        Schema::table('pembelian_barangs', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropForeign(['product_id']);
            $table->dropColumn(['supplier_id', 'product_id']);
        });
    }
};
