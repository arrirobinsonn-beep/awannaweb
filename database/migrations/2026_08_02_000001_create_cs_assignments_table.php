<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Riwayat penempatan CS Utama per bulan (skema rotasi bulanan).
     * Satu CS = satu advertiser per bulan → unique (cs_user_id, bulan).
     */
    public function up(): void
    {
        Schema::create('cs_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cs_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('advertiser_id')->constrained('users')->cascadeOnDelete();
            $table->string('bulan', 7); // '2026-08' — periode berlaku
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['cs_user_id', 'bulan'], 'uniq_cs_assignments_cs_bulan');
            $table->index(['advertiser_id', 'bulan'], 'idx_cs_assignments_adv_bulan');
            $table->index('bulan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cs_assignments');
    }
};
