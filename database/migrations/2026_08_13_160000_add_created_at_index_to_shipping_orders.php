<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Index created_at shipping_orders — dipakai filter "hari ini" / rentang tanggal
 * di dashboard & laporan operasional (aturan AGENTS.md: setiap kolom di WHERE
 * wajib ber-index). Sebelumnya hanya last_synced_at yang ber-index.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipping_orders', function (Blueprint $table) {
            $table->index('created_at', 'shipping_orders_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('shipping_orders', function (Blueprint $table) {
            $table->dropIndex('shipping_orders_created_at_index');
        });
    }
};
