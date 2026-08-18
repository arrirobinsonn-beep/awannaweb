<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nilai reference baru 'manual' dipakai penyesuaian stok manual dari halaman
     * Gudang (Barang Pasti — tambah/kurang stok oleh admin). Setiap penyesuaian
     * memakai reference_id acak sehingga tidak menimpa jurnal lain.
     */
    public function up(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->enum('reference', ['purchase', 'shipment', 'adjustment', 'order_online', 'manual'])
                ->default('adjustment')
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->enum('reference', ['purchase', 'shipment', 'adjustment', 'order_online'])
                ->default('adjustment')
                ->change();
        });
    }
};
