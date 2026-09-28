<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Perbaikan migrasi split_mode: nilai `split_rules` butuh 11 karakter sedangkan
 * kolom sempat dibuat `string(10)` → SQLSTATE 22001 Data too long. Lebarkan ke 12.
 * Idempotent — hanya jalan bila kolom sudah ada (DB yang sudah ke-migrate
 * migrasi 2026_09_28_000000 dengan lebar lama).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('export_templates', 'split_mode')) {
            Schema::table('export_templates', function (Blueprint $table) {
                $table->string('split_mode', 12)->default('single')->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('export_templates', 'split_mode')) {
            Schema::table('export_templates', function (Blueprint $table) {
                $table->string('split_mode', 10)->default('single')->change();
            });
        }
    }
};
