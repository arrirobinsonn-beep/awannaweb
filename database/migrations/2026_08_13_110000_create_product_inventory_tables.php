<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Relasi produk ↔ gudang diubah dari 1-ke-banyak (products.inventory_id)
     * menjadi many-to-many via tabel pivot:
     *
     *  - `product_inventory`          : keanggotaan produk di gudang + penanda
     *                                   gudang UTAMA (`is_primary`) yang dipakai
     *                                   export/fulfillment & pencatatan stok otomatis.
     *  - `product_variant_inventory`  : stok per VARIAN per GUDANG (cache turunan
     *                                   dari jurnal `stock_movements`, disinkronkan
     *                                   StockService — jurnal tetap sumber kebenaran).
     *
     * Backfill dari data lama:
     *  - `products.inventory_id`  → baris pivot (is_primary=1). Barang Pasti
     *    (consumable) yang dulu "ada di semua gudang" di-attach ke semua inventory.
     *  - `stock_movements`        → stok per (varian, gudang); movement dengan
     *    inventory_id NULL dianggap milik gudang utama produknya.
     */
    public function up(): void
    {
        Schema::create('product_inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('inventory_id')->constrained('inventories')->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            $table->unique(['product_id', 'inventory_id'], 'product_inventory_combo_unique');
            $table->index('inventory_id');
        });

        Schema::create('product_variant_inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->foreignId('inventory_id')->constrained('inventories')->cascadeOnDelete();
            $table->integer('stock')->default(0);
            $table->timestamps();
            $table->unique(['product_variant_id', 'inventory_id'], 'pvi_combo_unique');
            $table->index('inventory_id');
        });

        // ── Backfill keanggotaan ────────────────────────────────────────────
        $inventories = DB::table('inventories')->orderBy('id')->pluck('id')->all();
        $products = DB::table('products')->select('id', 'goods_type', 'inventory_id')->get();

        foreach ($products as $product) {
            $primaryId = $product->inventory_id !== null ? (int) $product->inventory_id : null;
            $ids = $primaryId !== null ? [$primaryId] : [];

            // Barang Pasti dulu "ada di semua gudang" → pertahankan perilaku itu.
            if ($product->goods_type === 'consumable' && $primaryId !== null) {
                $ids = array_values(array_unique(array_merge($ids, $inventories)));
            }

            foreach ($ids as $inventoryId) {
                DB::table('product_inventory')->insert([
                    'product_id' => $product->id,
                    'inventory_id' => $inventoryId,
                    'is_primary' => $inventoryId === $primaryId ? 1 : 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // ── Backfill stok per (varian, gudang) dari jurnal ──────────────────
        $variantToProduct = DB::table('product_variants')->pluck('product_id', 'id');
        $primaryByProduct = DB::table('product_inventory')
            ->where('is_primary', 1)
            ->pluck('inventory_id', 'product_id');

        $rows = DB::table('stock_movements')
            ->selectRaw('product_variant_id, COALESCE(inventory_id, 0) as inventory_id,
                         SUM(CASE WHEN type = "in" THEN quantity ELSE 0 END) as masuk,
                         SUM(CASE WHEN type = "out" THEN quantity ELSE 0 END) as keluar')
            ->groupBy('product_variant_id', 'inventory_id')
            ->get();

        foreach ($rows as $row) {
            $inventoryId = (int) $row->inventory_id;
            if ($inventoryId === 0) {
                $productId = $variantToProduct[$row->product_variant_id] ?? null;
                $inventoryId = $productId !== null ? (int) ($primaryByProduct[$productId] ?? 0) : 0;
            }
            if ($inventoryId <= 0) {
                continue;
            }

            DB::table('product_variant_inventory')->insert([
                'product_variant_id' => $row->product_variant_id,
                'inventory_id' => $inventoryId,
                'stock' => max(0, (int) $row->masuk - (int) $row->keluar),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variant_inventory');
        Schema::dropIfExists('product_inventory');
    }
};
