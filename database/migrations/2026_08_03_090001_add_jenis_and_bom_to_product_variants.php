<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jenis varian: 'varian' (unit dasar) atau 'paket' (gabungan beberapa varian dasar = BOM).
     * product_variant_items menyimpan komposisi paket: paket → (komponen varian dasar × qty).
     * Stok & modal paket diturunkan otomatis dari komponen (dihitung di model, bukan disimpan).
     */
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->string('jenis')->nullable()->after('name');
        });

        Schema::create('product_variant_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')
                ->constrained('product_variants')
                ->cascadeOnDelete();
            $table->foreignId('komponen_id')
                ->constrained('product_variants')
                ->cascadeOnDelete();
            $table->integer('qty')->default(1);
            $table->timestamps();

            $table->index('product_variant_id');
            $table->index('komponen_id');
            $table->unique(['product_variant_id', 'komponen_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variant_items');

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn('jenis');
        });
    }
};
