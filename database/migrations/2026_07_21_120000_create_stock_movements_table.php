<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->date('tanggal');
            $table->integer('masuk_belanja')->default(0);
            $table->integer('masuk_rts')->default(0);
            $table->integer('masuk_repair')->default(0);
            $table->integer('barang_rusak')->default(0);
            $table->integer('barang_keluar')->default(0);
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
