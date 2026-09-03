<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mobile_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts');
            $table->string('name', 100);
            $table->string('token_hash', 255);
            $table->enum('status', ['active', 'revoked'])->default('active');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index('account_id');
            $table->index('token_hash');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_devices');
    }
};
