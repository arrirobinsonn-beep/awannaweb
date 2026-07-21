<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('regional_cs_stats', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->foreignId('user_id') // advertiser yang punya data
                ->constrained('users')
                ->cascadeOnDelete();
            $table->string('cs_panggilan', 100); // nilai dari kolom handled_by
            $table->foreignId('cs_user_id') // FK ke users (cocok via panggilan)
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->integer('lead')->default(0);
            $table->integer('paid')->default(0);
            $table->timestamps();

            // Satu CS cuma boleh 1x per tanggal per advertiser
            $table->unique(['tanggal', 'user_id', 'cs_panggilan'], 'cs_stats_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('regional_cs_stats');
    }
};
