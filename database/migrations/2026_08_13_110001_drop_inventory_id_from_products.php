<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `products.inventory_id` (gudang induk tunggal) dihapus — relasi gudang
     * kini many-to-many via `product_inventory` (+ penanda `is_primary`).
     * Backfill ke pivot sudah dilakukan di migrasi 110000.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('inventory_id');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('inventory_id')->nullable()->constrained('inventories')->nullOnDelete();
        });
    }
};
