<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah dimensi status produk (running/testing) ke regional_cs_stats.
     *
     * CS stats kini dipecah per status produk — satu CS boleh punya 2 baris per
     * tanggal (running & testing), jadi UNIQUE lama (tanggal, user_id, cs_panggilan)
     * diganti menyertakan product_status.
     *
     * Keputusan user: data lama (campuran running+testing tanpa status) dibersihkan —
     * user akan upload ulang file regional agar 2 tabel performa team terisi akurat.
     */
    public function up(): void
    {
        Schema::table('regional_cs_stats', function (Blueprint $table) {
            $table->dropUnique('cs_stats_unique');
        });

        Schema::table('regional_cs_stats', function (Blueprint $table) {
            $table->string('product_status', 20)->default('running')->after('paid');
            $table->index('product_status');
            $table->unique(['tanggal', 'user_id', 'cs_panggilan', 'product_status'], 'cs_stats_status_unique');
        });

        // Bersihkan data lama (belum punya pemisahan status) — user upload ulang.
        DB::table('regional_cs_stats')->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('regional_cs_stats', function (Blueprint $table) {
            $table->dropUnique('cs_stats_status_unique');
            $table->dropIndex('regional_cs_stats_product_status_index');
            $table->dropColumn('product_status');
            $table->unique(['tanggal', 'user_id', 'cs_panggilan'], 'cs_stats_unique');
        });
    }
};