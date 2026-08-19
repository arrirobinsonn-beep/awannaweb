<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Konfigurasi kecil per dashboard tracking (FLIK / SiCepat / SPX):
 * `phone_format` — format nomor HP yang ada di FILE dashboard, agar tetap
 * bisa dicocokkan dengan `shipping_orders.phone_normalized` (berawalan 62).
 *
 *   - auto (default): normalisasi otomatis via OrderOnlineImportService
 *     (0/8/62 → 62) — perilaku bawaan, sudah menangani SPX 8xxxxx.
 *   - 8  : nomor file berawalan 8 (SPX) → tambah 62 di depan.
 *   - 0  : nomor file berawalan 0 → ganti dengan 62.
 *   - 62 : nomor file sudah berawalan 62 → dipakai apa adanya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracking_source_configs', function (Blueprint $table) {
            $table->id();
            $table->string('source', 20)->unique();
            $table->string('phone_format', 20)->default('auto');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracking_source_configs');
    }
};
