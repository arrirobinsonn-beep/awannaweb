<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pembelian_barangs', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->string('supplier');
            $table->string('sumber_produk')->nullable();
            $table->integer('qty')->default(0);
            $table->decimal('harga_satuan', 15, 2)->default(0);
            $table->decimal('total_belanja', 15, 2)->default(0);
            $table->decimal('ongkir', 15, 2)->default(0);
            $table->string('keterangan')->default('MASUK STOK');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pembelian_barangs');
    }
};
