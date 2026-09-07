<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mapping header CSV dashboard aggregator → kolom yang diisi di database.
 *
 * Pola export template (fitur N): admin upload file dashboard asli per sumber,
 * lalu mencocokkan tiap header CSV ke kolom yang ingin diisi (awb, phone,
 * address, product_name, quantity, status, problem, delivered_date).
 * `AggregatorTrackingImportService::mapHeaders()` memakai mapping ini saat
 * import tracking (DB menang atas alias hardcoded, sisanya fallback alias).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracking_header_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('source', 20);
            $table->string('header', 191);
            $table->string('db_column', 50);
            $table->timestamps();

            // Nama pendek eksplisit (< 64 char): default melebihi batas MySQL
            $table->unique(['source', 'header'], 'tracking_header_mappings_combo_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracking_header_mappings');
    }
};
