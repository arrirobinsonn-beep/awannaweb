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
 * ── MIGRASI INI IDEMPOTENT (PENTING) ────────────────────────────────────────
 * DDL MySQL TIDAK transaksional: kalau salah satu langkah gagal, langkah
 * sebelumnya tetap permanen sementara baris `migrations` belum dibuat → run
 * berikutnya mati di "1060 Duplicate column name 'ad_phase'". Karena itu tiap
 * langkah di sini dijaga (cek kolom/index dulu) dan aman dijalankan berulang.
 *
 * Skenario yang sudah terjadi di DB lokal (jangan diulang):
 *  DB memuat kolom `ad_phase` + index fase + `regional_unique` 4 kolom, TETAPI
 *  langkah terakhir (`DROP INDEX cs_stats_unique`) gagal 1091 karena index itu
 *  sudah diganti `cs_stats_status_unique` oleh implementasi paralel `product_status`
 *  (migrasi 2026_09_10_000000 yang dihapus saat rebase tapi efeknya masih ada).
 *  → `alignUnique()` di bawah membuang unique LEGACY apa pun (termasuk
 *    `cs_stats_status_unique` yang menyertakan `product_status`) dan memasang
 *    unique fase yang benar, sehingga DB hibrida ikut sembuh.
 *
 * PENTING (MySQL/InnoDB): FK user_id/cs_user_id butuh index berawalan kolom tsb.
 *  - Swap UNIQUE lama → baru dilakukan dlm SATU ALTER atomik (raw).
 *  - `ensureForeignKeyIndexes()` memastikan index polos FK ada SEBELUM unique
 *    apa pun di-drop, jadi FK tidak pernah tertinggal tanpa index.
 */
return new class extends Migration
{
    /**
     * Definisi per tabel: kolom fase, unique target, & kolom pendukung.
     *
     * `base` = kolom identitas baris TANPA fase (dari unique lama). Unique target
     * selalu `base` + `ad_phase` (fase di posisi terakhir, sama dgn versi asli).
     */
    private const TABLES = [
        'regional_reports' => [
            'phase_after' => 'province',
            'phase_index' => 'regional_reports_user_tanggal_phase_index',
            'unique' => 'regional_unique',
            'base' => ['tanggal', 'user_id', 'province'],
            'foreign_keys' => ['user_id'],
        ],
        'regional_cs_stats' => [
            'phase_after' => 'cs_user_id',
            'phase_index' => 'regional_cs_stats_user_tanggal_phase_index',
            'unique' => 'cs_stats_unique',
            'base' => ['tanggal', 'user_id', 'cs_panggilan'],
            'foreign_keys' => ['user_id', 'cs_user_id'],
        ],
    ];

    public function up(): void
    {
        foreach (self::TABLES as $table => $config) {
            $columns = $this->targetColumns($config);

            if (! Schema::hasColumn($table, 'ad_phase')) {
                Schema::table($table, function (Blueprint $blueprint) use ($config) {
                    $blueprint->string('ad_phase', 10)->default('running')
                        ->after($config['phase_after']);
                });
            }

            $this->ensureIndex($table, ['user_id', 'tanggal', 'ad_phase'], $config['phase_index']);
            $this->ensureForeignKeyIndexes($table, $config['foreign_keys']);

            // Baris ganda pada kombinasi identitas+fase (dulu mungkin lolos saat
            // unique-nya salah kolom) digabung dulu — kalau tidak, ADD UNIQUE
            // gagal 1062 dengan pesan yang membingungkan.
            $this->mergeDuplicates($table, $columns);

            $this->alignUnique($table, $config['unique'], $columns, $config['base']);
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table => $config) {
            // Index fase (berawalan user_id) biasanya satu-satunya penyangga FK di
            // titik ini — MySQL MENOLAK drop index tersebut (1553) bila tidak ada
            // index lain berawalan kolom FK. Jadi buat index polos dgn NAMA tetap
            // lebih dulu (dicek per nama, bukan "ada index berawalan kolom").
            foreach ($config['foreign_keys'] as $column) {
                $this->ensureNamedColumnIndex($table, $column, "{$table}_{$column}_index");
            }

            if ($this->indexExists($table, $config['phase_index'])) {
                Schema::table($table, function (Blueprint $blueprint) use ($config) {
                    $blueprint->dropIndex($config['phase_index']);
                });
            }

            if (! Schema::hasColumn($table, 'ad_phase')) {
                continue;
            }

            // Kembalikan unique 3 kolom lama: baris running & testing di tanggal
            // yang sama harus digabung lebih dulu agar unique-nya bisa dipasang.
            $this->mergeDuplicates($table, $config['base']);
            $this->alignUnique($table, $config['unique'], $config['base'], $config['base']);

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropColumn('ad_phase');
            });
        }
    }

    // ─── Schema inspector ───────────────────────────────────────────────────

    /** Kolom unique target = kolom identitas + fase (fase di posisi terakhir). */
    private function targetColumns(array $config): array
    {
        return [...$config['base'], 'ad_phase'];
    }

    /**
     * Daftar index sebuah tabel: [nama => ['unique' => bool, 'columns' => [urut]]].
     *
     * @return array<string, array{unique: bool, columns: array<int, string>}>
     */
    private function indexes(string $table): array
    {
        $rows = collect(DB::select("SHOW INDEX FROM `{$table}`"))
            ->sortBy(fn ($row) => $row->Key_name.'|'.str_pad((string) $row->Seq_in_index, 3, '0', STR_PAD_LEFT));

        $indexes = [];
        foreach ($rows as $row) {
            $indexes[$row->Key_name]['unique'] = ((int) $row->Non_unique) === 0;
            $indexes[$row->Key_name]['columns'][] = $row->Column_name;
        }

        return $indexes;
    }

    private function indexExists(string $table, string $name): bool
    {
        return array_key_exists($name, $this->indexes($table));
    }

    /** True bila $indexColumns memuat SEMUA kolom $subset (urutan tidak penting). */
    private function covers(array $indexColumns, array $subset): bool
    {
        return array_diff($subset, $indexColumns) === [];
    }

    // ─── Perubahan skema ────────────────────────────────────────────────────

    /** Buat index (non-unique) bila belum ada — dicek per nama DAN per kolom. */
    private function ensureIndex(string $table, array $columns, string $name): void
    {
        foreach ($this->indexes($table) as $existingName => $index) {
            if ($existingName === $name || $index['columns'] === $columns) {
                return;
            }
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $name) {
            $blueprint->index($columns, $name);
        });
    }

    /**
     * Buat index polos bernama tetap bila belum ada (dipakai `down()` agar FK tetap
     * punya penyangga sebelum index fase di-drop).
     */
    private function ensureNamedColumnIndex(string $table, string $column, string $name): void
    {
        if ($this->indexExists($table, $name)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column, $name) {
            $blueprint->index($column, $name);
        });
    }

    /**
     * Pastikan tiap kolom FK punya index polos (berawalan kolom itu). Tanpa ini,
     * drop index apa pun bisa menyisakan FK tanpa index → MySQL menolak ALTER.
     *
     * @param  array<int, string>  $foreignKeys
     */
    private function ensureForeignKeyIndexes(string $table, array $foreignKeys): void
    {
        $firstColumns = array_map(
            fn (array $index) => $index['columns'][0] ?? null,
            $this->indexes($table)
        );

        foreach ($foreignKeys as $column) {
            if (in_array($column, $firstColumns, true)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table, $column) {
                $blueprint->index($column, "{$table}_{$column}_index");
            });
        }
    }

    /**
     * Selaraskan unique: buang SEMUA unique legacy yang memuat kolom `base`
     * (mis. versi 3 kolom lama atau versi `product_status` dari branch origin),
     * lalu pasang unique target dalam SATU ALTER atomik. Idempotent — kalau
     * unique target sudah benar, tidak ada DDL yang dijalankan.
     *
     * @param  array<int, string>  $columns  kolom unique target
     * @param  array<int, string>  $base     kolom identitas (tanpa fase)
     */
    private function alignUnique(string $table, string $name, array $columns, array $base): void
    {
        $indexes = $this->indexes($table);
        $target = $indexes[$name] ?? null;

        if ($target && $target['columns'] === $columns) {
            return; // sudah sesuai
        }

        $drops = [];
        foreach ($indexes as $indexName => $index) {
            if ($indexName === 'PRIMARY' || ! $index['unique']) {
                continue;
            }

            // Index bernama sama tapi kolomnya beda → harus dibuang agar bisa dibuat ulang.
            if ($indexName === $name || $this->covers($index['columns'], $base)) {
                $drops[] = $indexName;
            }
        }

        $clauses = array_map(fn (string $index) => "DROP INDEX `{$index}`", $drops);
        $quoted = implode(', ', array_map(fn (string $column) => "`{$column}`", $columns));
        $clauses[] = "ADD UNIQUE KEY `{$name}` ({$quoted})";

        DB::statement("ALTER TABLE `{$table}` ".implode(', ', $clauses));
    }

    /**
     * Gabungkan baris ganda pada kombinasi $columns: lead/paid dijumlahkan ke
     * baris id terkecil, sisanya dihapus (paid_ratio dihitung ulang bila ada).
     * Aman & no-op saat tidak ada ganda — di DB normal tidak pernah ada ganda.
     *
     * @param  array<int, string>  $columns
     */
    private function mergeDuplicates(string $table, array $columns): void
    {
        $grouped = implode(', ', array_map(fn (string $column) => "`{$column}`", $columns));

        $duplicates = DB::table($table)
            ->selectRaw("{$grouped}, COUNT(*) as total")
            ->groupBy($columns)
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicates->isEmpty()) {
            return;
        }

        $hasRatio = Schema::hasColumn($table, 'paid_ratio');
        $merged = 0;

        foreach ($duplicates as $duplicate) {
            $query = DB::table($table);
            foreach ($columns as $column) {
                $query->where($column, $duplicate->{$column});
            }

            $ids = $query->orderBy('id')->pluck('id');
            $keepId = $ids->first();

            // Dijumlahkan di PHP, BUKAN SUM() raw: `lead` reserved word di MySQL
            // dan tetap ilegal sebagai ALIAS tanpa backtick (`... as lead` → 1064).
            // Query builder membungkus nama kolom dgn benar, jadi aman.
            $totals = DB::table($table)->whereIn('id', $ids->all())->get(['lead', 'paid']);

            $values = [
                'lead' => (int) $totals->sum('lead'),
                'paid' => (int) $totals->sum('paid'),
            ];
            if ($hasRatio) {
                $values['paid_ratio'] = $values['lead'] > 0
                    ? round($values['paid'] / $values['lead'] * 100, 2)
                    : 0;
            }

            DB::table($table)->where('id', $keepId)->update($values);
            DB::table($table)->whereIn('id', $ids->slice(1)->all())->delete();
            $merged++;
        }

        echo "    ⚠ {$table}: {$merged} kombinasi ganda digabung (lead/paid dijumlahkan) sebelum unique dipasang.\n";
    }
};
