<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom `shipping_orders.order_at` — tanggal ORDER asli dari CSV (header
 * `created_at` file toko), BUKAN waktu import.
 *
 * Latar belakang: import 1 bulan data mentah menulis semua baris dengan
 * `created_at = now()` (waktu import), sehingga grafik tren order harian,
 * filter tanggal, dashboard "hari ini", dan laporan operasional hanya melihat
 * 1 hari. Kolom `order_at` memisahkan tanggal bisnis (order) dari timestamp
 * audit `created_at` (kapan data masuk sistem — dipakai window deteksi duplikat
 * 14 hari di OrderOnlineImportService, TIDAK ikut diubah).
 *
 * Backfill: parse `raw_payload['created_at']` (nilai mentah header CSV,
 * contoh "29-07-2026 - 23:38"); fallback ke `created_at` bila payload tidak
 * ada / tanggal tak ter-parse → order_at tidak pernah null untuk baris lama.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('shipping_orders', 'order_at')) {
            Schema::table('shipping_orders', function (Blueprint $table) {
                $table->dateTime('order_at')->nullable()->after('delivered_at');
                $table->index('order_at', 'shipping_orders_order_at_index');
            });
        }

        DB::table('shipping_orders')
            ->select(['id', 'raw_payload', 'created_at'])
            ->orderBy('id')
            ->chunkById(500, function ($orders) {
                foreach ($orders as $order) {
                    $parsed = null;
                    $payload = json_decode((string) $order->raw_payload, true);
                    if (is_array($payload) && ! empty($payload['created_at'])) {
                        $parsed = self::parseOrderDate((string) $payload['created_at']);
                    }
                    DB::table('shipping_orders')
                        ->where('id', $order->id)
                        ->update(['order_at' => $parsed ?? $order->created_at]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('shipping_orders', function (Blueprint $table) {
            $table->dropIndex('shipping_orders_order_at_index');
            $table->dropColumn('order_at');
        });
    }

    /**
     * Parse tanggal order dari string mentah CSV (day-first, format toko).
     * Contoh: "29-07-2026 - 23:38" → "2026-07-29 23:38:00".
     */
    private static function parseOrderDate(string $dateStr): ?string
    {
        $dateStr = trim($dateStr);
        if ($dateStr === '') {
            return null;
        }

        // "29-07-2026 - 23:38" → "29-07-2026 23:38"; ISO "T" → spasi
        $clean = str_replace('T', ' ', $dateStr);
        $clean = preg_replace('/\s*-\s*(\d{1,2}:\d{2})/', ' $1', $clean);
        $clean = trim((string) $clean);

        $formats = [
            'd-m-Y H:i:s', 'd-m-Y H:i', 'd/m/Y H:i:s', 'd/m/Y H:i',
            'd.m.Y H:i', 'Y-m-d H:i:s', 'Y-m-d H:i', 'Y/m/d H:i',
            'd-m-Y', 'd/m/Y', 'Y-m-d', 'Y/m/d', 'd.m.Y',
        ];

        foreach ($formats as $format) {
            $dt = \DateTime::createFromFormat($format, $clean);
            if ($dt && $dt->format($format) === $clean) {
                return $dt->format('Y-m-d H:i:s');
            }
        }

        try {
            return Carbon::parse($clean)->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }
};