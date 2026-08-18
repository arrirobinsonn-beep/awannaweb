<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropUnique('stock_movements_ref_unique');
            $table->unique(['reference', 'reference_id', 'type', 'product_variant_id'], 'stock_movements_ref_unique');
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropUnique('stock_movements_ref_unique');
            $table->unique(['reference', 'reference_id', 'type'], 'stock_movements_ref_unique');
        });
    }
};
