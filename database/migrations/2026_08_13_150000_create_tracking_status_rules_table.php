<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Aturan dinamis raw status dashboard aggregator → aggregator_status
 * (pengganti map hardcoded di AggregatorTrackingImportService::mapStatus).
 *
 * - source         : flik / sicepat / spx
 * - raw_status     : teks status mentah yang dicocokkan (disimpan lowercase)
 * - match_type     : exact (sama persis) / contains (mengandung)
 * - status         : nilai aggregator_status Inggris (ShippingOrder::TRACKING_STATUSES)
 * - problem_mode   : none (rule biasa) / required (hanya berlaku bila kolom
 *                    masalah terpenuhi — FLIK 3PL "Problem...", SPX OnHold reason)
 * - problem_keyword: null/'' = kolom masalah TIDAK kosong; selain itu = harus
 *                    MENGANDUNG keyword (case-insensitive)
 * - sort_order     : evaluasi urut dari kecil (rule problem harus di atas rule biasa)
 *
 * Contoh: FLIK "Dikonfirmasi" → waiting_pickup (problem_mode none),
 *         FLIK "Dikonfirmasi" + 3PL berisi "problem" → problem (mode required, sort kecil).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracking_status_rules', function (Blueprint $table) {
            $table->id();
            $table->string('source');
            $table->string('raw_status');
            $table->string('match_type')->default('exact');
            $table->string('status');
            $table->string('problem_mode')->default('none');
            $table->string('problem_keyword')->nullable();
            $table->unsignedInteger('sort_order')->default(100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(
                ['source', 'raw_status', 'match_type', 'problem_mode', 'status'],
                'tracking_status_rules_combo_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracking_status_rules');
    }
};
