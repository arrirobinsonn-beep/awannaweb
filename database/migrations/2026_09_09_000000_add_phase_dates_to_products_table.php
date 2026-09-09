<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fase iklan produk berbasis TANGGAL:
 *  - `start_testing` = kapan produk mulai fase testing (otomatis saat produk dibuat)
 *  - `start_running` = kapan produk di-toggle ke running (otomatis saat toggle,
 *                      bisa diedit manual oleh admin)
 *
 * Klasifikasi spending iklan membandingkan `spending_harians.tanggal` terhadap
 * kedua tanggal ini (bukan lagi `ad_status` saat ini) sehingga spending yang
 * dicatat saat produk masih testing TIDAK ikut pindah ke Running ketika produk
 * di-toggle belakangan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->date('start_testing')->nullable()->after('ad_status');
            $table->date('start_running')->nullable()->after('start_testing');
            $table->index('start_running', 'products_start_running_index');
        });

        // Backfill produk existing (seragam sesuai keputusan user):
        // semua produk dianggap testing sejak 1 Agustus & running sejak 1 September.
        DB::table('products')->update([
            'start_testing' => '2026-08-01',
            'start_running' => '2026-09-01',
        ]);
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_start_running_index');
            $table->dropColumn(['start_testing', 'start_running']);
        });
    }
};