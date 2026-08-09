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
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->foreignId('inventory_id')->nullable()->constrained('inventories')->nullOnDelete();
            $table->date('date');
            $table->enum('type', ['in', 'out']);
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 15, 2)->nullable();
            $table->enum('reference', ['purchase', 'shipment', 'adjustment', 'order_online'])->default('adjustment');
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->unique(['reference', 'reference_id', 'type'], 'stock_movements_ref_unique');
            $table->index('product_variant_id');
            $table->index('date');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
