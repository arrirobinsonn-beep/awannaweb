<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamp('email_verified_at')->nullable();

            // ─── Data profil (diisi saat complete-profile) ───
            $table->string('nama')->nullable();           // nama lengkap
            $table->string('panggilan')->nullable();      // nama panggilan
            $table->string('role')->nullable();           // mentor, keuangan, advertiser, cs, admin, dll
            $table->string('nohp')->nullable();           // nomor HP
            $table->text('alamat')->nullable();           // alamat lengkap
            $table->string('bank')->nullable();           // nama bank
            $table->string('norek')->nullable();          // nomor rekening
            $table->string('avatar')->nullable();

            // ─── Status ───────────────────────────────────────
            $table->boolean('is_active')->default(true);
            $table->boolean('is_profile_complete')->default(false);

            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
