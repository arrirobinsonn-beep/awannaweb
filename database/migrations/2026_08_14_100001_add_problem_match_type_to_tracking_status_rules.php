<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah `problem_match_type` pada tracking_status_rules — cara mencocokkan
 * KOLOM MASALAH terhadap keyword:
 *
 *   - contains    : kolom masalah MENGANDUNG keyword (default, perilaku lama)
 *   - starts_with : kolom masalah DIAWALI keyword — kasus FLIK: kolom 3PL
 *                   "Status Terakhir dari 3PL" untuk paket bermasalah SELALU
 *                   diawali "Problem..." (kolom status terpisah, header beda).
 *
 * Semua data-driven: aggregator baru cukup menambah rule dengan match_type ini,
 * tanpa mengubah kode.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tracking_status_rules', function (Blueprint $table) {
            $table->string('problem_match_type', 20)->default('contains')->after('problem_keyword');
        });
    }

    public function down(): void
    {
        Schema::table('tracking_status_rules', function (Blueprint $table) {
            $table->dropColumn('problem_match_type');
        });
    }
};
