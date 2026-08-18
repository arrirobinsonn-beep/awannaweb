<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengiriman_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengiriman_id')->constrained('pengirimans')->cascadeOnDelete();
            $table->integer('user_id')->unsigned()->nullable()->index();
            $table->string('status')->nullable();
            $table->string('catatan_kurir')->nullable();
            $table->timestamp('dilihat')->nullable();
            $table->timestamps();

            $table->index('pengiriman_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengiriman_status_histories');
    }
};
