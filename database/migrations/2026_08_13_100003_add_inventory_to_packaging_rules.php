<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Aturan kemasan kini bisa di-set PER GUDANG (inventory_id nullable):
     *  - NULL  = berlaku untuk SEMUA gudang (global/default)
     *  - terisi = khusus untuk gudang tersebut (menimpa rule global utk kombinasi yang sama)
     *
     * Idempotent: migrasi lama sempat ter-apply sebagian (kolom inventory_id sudah ada,
     * unique lama belum). Semua langkah di-guard supaya aman dijalankan ulang.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('packaging_rules', 'inventory_id')) {
            Schema::table('packaging_rules', function (Blueprint $table) {
                $table->foreignId('inventory_id')
                    ->nullable()
                    ->after('target_product_id')
                    ->constrained('inventories')
                    ->nullOnDelete()
                    ->index('packaging_rules_inventory_id_index');
            });
        }

        $indexNames = $this->indexNames();

        if (in_array('packaging_rules_source_product_id_target_product_id_unique', $indexNames, true)) {
            Schema::table('packaging_rules', function (Blueprint $table) {
                $table->dropUnique(['source_product_id', 'target_product_id']);
            });
        }

        if (! in_array('packaging_rules_combo_unique', $indexNames, true)) {
            Schema::table('packaging_rules', function (Blueprint $table) {
                $table->unique(['source_product_id', 'target_product_id', 'inventory_id'], 'packaging_rules_combo_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::table('packaging_rules', function (Blueprint $table) {
            $table->dropUnique('packaging_rules_combo_unique');
            $table->dropConstrainedForeignId('inventory_id');
            $table->unique(['source_product_id', 'target_product_id']);
        });
    }

    protected function indexNames(): array
    {
        return collect(DB::select('SHOW INDEX FROM packaging_rules'))
            ->pluck('Key_name')
            ->all();
    }
};
