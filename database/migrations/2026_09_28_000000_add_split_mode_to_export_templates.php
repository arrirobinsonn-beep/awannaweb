<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Aturan file export per template (halaman /export-mapping/{id}/edit):
 *  - `single`      → 1 file langsung
 *  - `split_csv`   → dipisah per gudang berdasarkan kolom `warehouse` data mentah (CSV)
 *  - `split_rules` → dipisah per gudang berdasarkan aturan `warehouse_rules`
 *                    (halaman /warehouse-rules); produk tanpa rule → grup 'LAINNYA'
 *
 * Backfill mempertahankan perilaku hardcoded lama (`in_array(template, ['sicepat','spx'])`):
 * sicepat & spx = split_csv, lainnya = single.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('export_templates', 'split_mode')) {
            Schema::table('export_templates', function (Blueprint $table) {
                $table->string('split_mode', 12)->default('single')->after('couriers');
                $table->index('split_mode');
            });
        }

        // Backfill idempoten — hanya menyentuh 2 template bawaan yang dulu selalu split
        DB::table('export_templates')
            ->whereIn('key', ['sicepat', 'spx'])
            ->where('split_mode', 'single')
            ->update(['split_mode' => 'split_csv']);
    }

    public function down(): void
    {
        if (Schema::hasColumn('export_templates', 'split_mode')) {
            Schema::table('export_templates', function (Blueprint $table) {
                $table->dropIndex(['split_mode']);
                $table->dropColumn('split_mode');
            });
        }
    }
};
