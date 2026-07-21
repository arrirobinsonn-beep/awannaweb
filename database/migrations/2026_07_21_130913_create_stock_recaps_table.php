<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_recaps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('bulan', 7);
            $table->integer('real_stok')->default(0);
            $table->integer('selisih')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'bulan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_recaps');
    }
};
