<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Varian produk: 1 produk induk memiliki beberapa varian harga jual
     * (misal kacamata dengan berbagai ukuran).
     * pcs_per_pack = berapa pcs yang keluar dalam 1x penjualan (mis. beli 1 dapat 2).
     */
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();
            $table->string('code')->unique();
            $table->string('name');
            $table->integer('stock')->default(0);
            $table->decimal('power', 5, 2)->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->index('product_id');
            $table->index(['product_id', 'power']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
