<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whitelists', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('kode')->unique();           // kode unik whitelist
            $table->string('platform');                  // facebook, tiktok, google, dll
            $table->foreignId('user_id')                 // pemilik whitelist (advertiser)
                ->constrained('users')
                ->cascadeOnDelete();
            $table->date('tanggal')->useCurrent();       // tanggal ditambahkan
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $table->decimal('total_topup', 15, 2)->default(0);
            $table->decimal('total_spending', 15, 2)->default(0);
            $table->decimal('nominal_terakhir_topup', 15, 2)->default(0);
            $table->text('catatan')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whitelists');
    }
};
