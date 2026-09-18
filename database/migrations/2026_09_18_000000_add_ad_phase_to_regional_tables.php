<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pemisahan lead/paid regional per FASE iklan produk (fitur 18 September 2026).
 *
 * Sebelumnya regional_reports hanya memuat lead/paid produk RUNNING (baris
 * testing di-skip saat import) dan regional_cs_stats mencampur semua baris.
 * Kini keduanya menyimpan fase asal baris:
 *  - `running` → tabel matriks utama (pembanding spending fase running)
 *  - `testing` → tabel matriks kedua (pembanding spending fase testing)
 *
 * Default `running` = semua data LAMA otomatis dianggap running (keputusan
 * user — konsisten dengan perilaku lama yang hanya menyimpan running).
 *
 * PENTING (MySQL/InnoDB): FK user_id/cs_user_id tidak punya index kiri-sendiri
 * — MySQL meminjam index yang MEMUAT kolom tsb. Karena itu:
 *  - Swap UNIQUE lama → baru dilakukan dlm SATU ALTER atomik (raw) agar FK
 *    tidak pernah tertinggal tanpa index.
 *  - Di down(), index fase di-drop DULU (FK fallback ke regional_unique),
 *    bukan setelah unique di-drop.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('regional_reports', function (Blueprint $table) {
            $table->string('ad_phase', 10)->default('running')->after('province');
            $table->index(['user_id', 'tanggal', 'ad_phase'], 'regional_reports_user_tanggal_phase_index');
        });

        // UNIQUE lama (tanggal, user_id, province) tidak memuat fase → baris
        // TESTING bentrok dgn baris RUNNING di (tanggal, province) sama.
        // Rebuild: tiap fase punya barisnya sendiri per (tanggal, province).
        DB::statement('ALTER TABLE regional_reports DROP INDEX regional_unique, ADD UNIQUE KEY regional_unique (tanggal, user_id, province, ad_phase)');

        Schema::table('regional_cs_stats', function (Blueprint $table) {
            $table->string('ad_phase', 10)->default('running')->after('cs_user_id');
            $table->index(['user_id', 'tanggal', 'ad_phase'], 'regional_cs_stats_user_tanggal_phase_index');
        });

        DB::statement('ALTER TABLE regional_cs_stats DROP INDEX cs_stats_unique, ADD UNIQUE KEY cs_stats_unique (tanggal, user_id, cs_panggilan, ad_phase)');
    }

    public function down(): void
    {
        // FK user_id/cs_user_id butuh index BERAWALAN kolom tsb —
        // regional_unique/cs_stats_unique berawalan `tanggal` TIDAK memenuhi.
        // Pastikan index polos user_id/cs_user_id ada SEBELUM drop index fase.
        $this->ensureIndex('regional_reports', 'user_id', 'regional_reports_user_id_index');
        $this->ensureIndex('regional_cs_stats', 'user_id', 'regional_cs_stats_user_id_index');
        $this->ensureIndex('regional_cs_stats', 'cs_user_id', 'regional_cs_stats_cs_user_id_index');

        Schema::table('regional_reports', function (Blueprint $table) {
            $table->dropIndex('regional_reports_user_tanggal_phase_index');
        });

        Schema::table('regional_cs_stats', function (Blueprint $table) {
            $table->dropIndex('regional_cs_stats_user_tanggal_phase_index');
        });

        // Swap balik ke unique 3 kolom lama — satu ALTER atomik.
        DB::statement('ALTER TABLE regional_reports DROP INDEX regional_unique, ADD UNIQUE KEY regional_unique (tanggal, user_id, province)');
        DB::statement('ALTER TABLE regional_cs_stats DROP INDEX cs_stats_unique, ADD UNIQUE KEY cs_stats_unique (tanggal, user_id, cs_panggilan)');

        Schema::table('regional_reports', function (Blueprint $table) {
            $table->dropColumn('ad_phase');
        });

        Schema::table('regional_cs_stats', function (Blueprint $table) {
            $table->dropColumn('ad_phase');
        });
    }

    /**
     * Pastikan index polos (berawalan kolom) ada — dipakai FK sebagai index
     * cadangan saat index fase di-drop. Idempotent (cek SHOW INDEX dulu).
     */
    private function ensureIndex(string $table, string $column, string $indexName): void
    {
        $exists = collect(DB::select("SHOW INDEX FROM {$table}"))
            ->contains(fn ($row) => $row->Key_name === $indexName);

        if (! $exists) {
            Schema::table($table, function (Blueprint $table) use ($column, $indexName) {
                $table->index($column, $indexName);
            });
        }
    }
};
