<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jenis aturan kemasan:
     *  - `additional`: tiap `qty_per` barang inti terkirim → 1 barang additional ikut keluar
     *    (target pakai varian DEFAULT). Contoh: KMP → BOX (2:1).
     *  - `split`: barang inti dipecah — main keluar `ceil(qty/qty_per)`, target keluar
     *    `floor(qty/qty_per)` dengan varian target POWER SAMA (fallback default).
     *    Contoh promo "Beli 1 Dapat 2": KMP → KDF (2:1) → order qty 2 = 1 KMP + 1 KDF keluar.
     */
    public function up(): void
    {
        Schema::table('packaging_rules', function (Blueprint $table) {
            $table->string('rule_type', 20)->default('additional')->after('qty_per');
            $table->index('rule_type');
        });
    }

    public function down(): void
    {
        Schema::table('packaging_rules', function (Blueprint $table) {
            $table->dropIndex(['rule_type']);
            $table->dropColumn('rule_type');
        });
    }
};
